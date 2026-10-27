<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\FacilityAsset;
use App\Modules\Administration\Infrastructure\Models\FacilityLoan;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilitiesControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $operator;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('12345678', 'Sekolah Utama');
        $this->admin = $this->makeUser($this->school, 'admin', 'admin@utama.test');
        $this->operator = $this->makeUser($this->school, 'operator', 'operator@utama.test');
        $this->teacher = $this->makeUser($this->school, 'teacher', 'teacher@utama.test');
    }

    public function test_inventory_is_scoped_and_only_admin_or_operator_can_access_it(): void
    {
        $otherSchool = $this->makeSchool('87654321', 'Sekolah Lain');
        $otherAdmin = $this->makeUser($otherSchool, 'admin', 'admin@lain.test');
        $foreignAsset = $this->makeAsset($otherSchool, $otherAdmin, 'INV-LAIN-01');

        $this->actingAs($this->admin)
            ->get('/administrasi/sarpras')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Administration/Facilities/Index')
                ->has('assets', 0));

        $this->actingAs($this->teacher)->get('/administrasi/sarpras')->assertForbidden();
        $this->actingAs($this->admin)->put("/administrasi/sarpras/aset/{$foreignAsset->id}", [
            'inventory_code' => 'INV-LAIN-01', 'name' => 'Diedit', 'category' => 'Elektronik', 'condition' => 'good', 'status' => 'available',
        ])->assertNotFound();
    }

    public function test_operator_can_create_asset_and_validation_rejects_duplicate_school_code(): void
    {
        $payload = [
            'inventory_code' => 'INV-001', 'name' => 'Proyektor', 'category' => 'Elektronik',
            'location' => 'Ruang Guru', 'condition' => 'good', 'status' => 'available', 'notes' => 'Unit baru',
        ];

        $this->actingAs($this->operator)->post('/administrasi/sarpras/aset', $payload)->assertRedirect();
        $this->assertDatabaseHas('facility_assets', [
            'school_id' => $this->school->id,
            'created_by' => $this->operator->id,
            'inventory_code' => 'INV-001',
            'name' => 'Proyektor',
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'event_type' => 'administration.facility_asset.created',
        ]);

        $this->actingAs($this->admin)->from('/administrasi/sarpras')
            ->post('/administrasi/sarpras/aset', $payload)
            ->assertSessionHasErrors('inventory_code');
    }

    public function test_damaged_asset_cannot_be_marked_available_or_borrowed(): void
    {
        $this->actingAs($this->operator)
            ->from('/administrasi/sarpras')
            ->post('/administrasi/sarpras/aset', [
                'inventory_code' => 'INV-RUSAK', 'name' => 'Proyektor rusak', 'category' => 'Elektronik',
                'condition' => 'damaged', 'status' => 'available',
            ])
            ->assertRedirect('/administrasi/sarpras')->assertSessionHasErrors('status');

        $asset = $this->makeAsset($this->school, $this->admin, 'INV-RUSAK-EXISTING');
        $asset->update(['condition' => 'damaged']);

        $this->actingAs($this->operator)
            ->from('/administrasi/sarpras')
            ->post('/administrasi/sarpras/peminjaman', [
                'asset_id' => $asset->id,
                'borrower_name' => 'Guru',
                'borrowed_at' => '2026-10-27',
            ])
            ->assertSessionHasErrors('asset_id');

        $this->assertDatabaseCount('facility_loans', 0);
    }

    public function test_asset_pagination_does_not_hide_borrowable_options_when_filtering(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeAsset($this->school, $this->admin, 'INV-PAGE-'.$i);
        }
        $this->actingAs($this->admin)->get('/administrasi/sarpras?status=maintenance')
            ->assertOk()->assertInertia(fn ($page) => $page->has('assets', 0)->has('available_assets', 12));
        $this->actingAs($this->admin)->get('/administrasi/sarpras')
            ->assertOk()->assertInertia(fn ($page) => $page->has('assets', 10)->where('asset_pages.total', 12)->where('asset_pages.last', 2));
    }

    public function test_open_loan_remains_visible_ahead_of_newer_returned_loans(): void
    {
        $asset = $this->makeAsset($this->school, $this->admin, 'INV-OPEN');
        $open = FacilityLoan::create(['school_id' => $this->school->id, 'asset_id' => $asset->id, 'recorded_by' => $this->admin->id, 'borrower_name' => 'Peminjam aktif', 'borrowed_at' => today()]);
        for ($i = 0; $i < 12; $i++) {
            FacilityLoan::create(['school_id' => $this->school->id, 'asset_id' => $asset->id, 'recorded_by' => $this->admin->id, 'borrower_name' => 'Sudah kembali', 'borrowed_at' => today(), 'returned_at' => today(), 'return_condition' => 'good']);
        }
        $this->actingAs($this->admin)->get('/administrasi/sarpras')->assertOk()
            ->assertInertia(fn ($page) => $page->has('loans', 10)->where('loans.0.id', $open->id)->where('loan_pages.total', 13));
    }

    public function test_asset_loan_return_workflow_updates_availability_and_records_audit(): void
    {
        $asset = $this->makeAsset($this->school, $this->admin, 'INV-PROY-01');

        $this->actingAs($this->operator)->post('/administrasi/sarpras/peminjaman', [
            'asset_id' => $asset->id,
            'borrower_name' => 'Guru Matematika',
            'borrower_reference' => 'NIP-001',
            'borrowed_at' => '2026-10-27',
            'due_at' => '2026-10-30',
        ])->assertRedirect();

        $loan = FacilityLoan::where('school_id', $this->school->id)->firstOrFail();
        $this->assertDatabaseHas('facility_assets', ['id' => $asset->id, 'status' => 'borrowed']);

        $this->actingAs($this->admin)->from('/administrasi/sarpras')
            ->post('/administrasi/sarpras/peminjaman', [
                'asset_id' => $asset->id,
                'borrower_name' => 'Guru Lain',
                'borrowed_at' => '2026-10-27',
            ])->assertSessionHasErrors('asset_id');

        $this->actingAs($this->operator)->post("/administrasi/sarpras/peminjaman/{$loan->id}/kembali", [
            'return_condition' => 'damaged',
            'notes' => 'Perlu dicek teknisi',
        ])->assertRedirect();

        $loan->refresh();
        $this->assertSame('2026-10-27', $loan->returned_at?->toDateString());
        $this->assertSame('damaged', $loan->return_condition);
        $this->assertDatabaseHas('facility_assets', [
            'id' => $asset->id,
            'status' => 'maintenance',
            'condition' => 'damaged',
        ]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'administration.facility_asset.borrowed']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'administration.facility_asset.returned']);
    }

    public function test_cannot_borrow_asset_from_another_school(): void
    {
        $otherSchool = $this->makeSchool('56781234', 'Sekolah Asing');
        $otherAdmin = $this->makeUser($otherSchool, 'admin', 'admin@asing.test');
        $foreignAsset = $this->makeAsset($otherSchool, $otherAdmin, 'INV-FOREIGN');

        $this->actingAs($this->operator)->from('/administrasi/sarpras')
            ->post('/administrasi/sarpras/peminjaman', [
                'asset_id' => $foreignAsset->id,
                'borrower_name' => 'Peminjam',
                'borrowed_at' => '2026-10-27',
            ])->assertSessionHasErrors('asset_id');

        $this->assertDatabaseCount('facility_loans', 0);
        $this->assertDatabaseHas('facility_assets', ['id' => $foreignAsset->id, 'status' => 'available']);
    }

    private function makeSchool(string $npsn, string $name): School
    {
        return School::create(['npsn' => $npsn, 'name' => $name, 'status' => 'active']);
    }

    private function makeUser(School $school, string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName), 'is_system' => true]);

        return User::create([
            'school_id' => $school->id,
            'role_id' => $role->id,
            'name' => ucfirst($roleName),
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'password' => bcrypt('Secret123!'),
            'is_active' => true,
        ]);
    }

    private function makeAsset(School $school, User $actor, string $code): FacilityAsset
    {
        return FacilityAsset::create([
            'school_id' => $school->id,
            'created_by' => $actor->id,
            'inventory_code' => $code,
            'name' => 'Proyektor',
            'category' => 'Elektronik',
            'condition' => 'good',
            'status' => 'available',
        ]);
    }
}
