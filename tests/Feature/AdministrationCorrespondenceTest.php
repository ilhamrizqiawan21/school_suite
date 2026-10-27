<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdministrationCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $operator;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Route::middleware('web')->group(app_path('Modules/Administration/Interface/Routes/correspondence.php'));
        Route::getRoutes()->refreshNameLookups();

        $this->school = School::create(['name' => 'Madrasah Persuratan', 'npsn' => '77553311', 'level' => 'MTs']);
        $roles = collect(['admin', 'operator', 'teacher'])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name, 'label' => ucfirst($name), 'is_system' => true]),
        ]);
        $this->admin = $this->makeUser($roles['admin'], 'admin@persuratan.test', 'Admin Persuratan');
        $this->operator = $this->makeUser($roles['operator'], 'operator@persuratan.test', 'Operator Persuratan');
        $this->teacher = $this->makeUser($roles['teacher'], 'teacher@persuratan.test', 'Guru Persuratan');
    }

    public function test_admin_and_operator_can_view_correspondence_but_teacher_cannot(): void
    {
        $this->actingAs($this->admin)
            ->get(route('administration.correspondence.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Administration/Correspondence/Index')
                ->has('records', 0));

        $this->actingAs($this->operator)->get(route('administration.correspondence.index'))->assertOk();
        $this->actingAs($this->teacher)->get(route('administration.correspondence.index'))->assertForbidden();
    }

    public function test_incoming_letter_gets_school_scoped_agenda_number_audit_and_private_attachment(): void
    {
        $response = $this->actingAs($this->operator)->post(route('administration.correspondence.store'), [
            'letter_type' => 'incoming',
            'letter_number' => 'EXT/12/2026',
            'letter_date' => '2026-10-20',
            'received_at' => '2026-10-22',
            'sender' => 'Kantor Wilayah',
            'subject' => 'Undangan rapat',
            'summary' => 'Rapat koordinasi sekolah.',
            'attachment' => UploadedFile::fake()->create('undangan.pdf', 20, 'application/pdf'),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $record = CorrespondenceRecord::firstOrFail();
        $this->assertSame($this->school->id, $record->school_id);
        $this->assertSame($this->operator->id, $record->created_by);
        $this->assertSame('received', $record->status);
        $this->assertSame('IN/2026/000001', $record->agenda_number);
        Storage::disk('local')->assertExists($record->attachment_path);
        $this->assertStringStartsWith('correspondence/'.$this->school->id.'/', $record->attachment_path);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'actor_id' => $this->operator->id,
            'event_type' => 'administration.correspondence.created',
            'target_id' => (string) $record->id,
        ]);

        $this->actingAs($this->operator)
            ->get(route('administration.correspondence.attachment', $record->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_outgoing_letter_requires_user_entered_number_and_supports_sent_then_archive_flow(): void
    {
        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Pengantar dokumen',
        ])->assertSessionHasErrors('letter_number');

        $this->actingAs($this->admin)->post(route('administration.correspondence.store'), [
            'letter_type' => 'outgoing',
            'letter_number' => '001/ADM/2026',
            'letter_date' => '2026-10-22',
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Pengantar dokumen',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $record = CorrespondenceRecord::firstOrFail();
        $this->assertSame('draft', $record->status);
        $this->actingAs($this->admin)
            ->patch(route('administration.correspondence.status', $record), ['status' => 'archived'])
            ->assertSessionHasErrors('status');

        $this->patch(route('administration.correspondence.status', $record), ['status' => 'sent'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('administration.correspondence.status', $record->fresh()), ['status' => 'archived'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('archived', $record->fresh()->status);
        $this->assertNotNull($record->fresh()->archived_at);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'event_type' => 'administration.correspondence.status_changed',
            'target_id' => (string) $record->id,
        ]);
        $this->get(route('administration.correspondence.index', ['filter' => 'archived']))
            ->assertInertia(fn ($page) => $page->has('records', 1)->where('records.0.status', 'archived'));
    }

    public function test_incoming_letter_requires_disposition_before_advancing_and_rejects_invalid_attachment(): void
    {
        $this->actingAs($this->operator)->post(route('administration.correspondence.store'), [
            'letter_type' => 'incoming',
            'received_at' => '2026-10-22',
            'sender' => 'Pengirim',
            'subject' => 'Surat dengan lampiran tidak valid',
            'attachment' => UploadedFile::fake()->create('script.php', 5, 'application/x-php'),
        ])->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('correspondence_records', 0);

        $this->actingAs($this->operator)->post(route('administration.correspondence.store'), [
            'letter_type' => 'incoming',
            'received_at' => '2026-10-22',
            'sender' => 'Pengirim',
            'subject' => 'Surat masuk',
        ])->assertRedirect();
        $record = CorrespondenceRecord::firstOrFail();

        $this->patch(route('administration.correspondence.status', $record), ['status' => 'disposition'])
            ->assertSessionHasErrors('disposition');
        $this->patch(route('administration.correspondence.status', $record), [
            'status' => 'disposition',
            'disposition' => 'Tindak lanjuti oleh bagian kurikulum.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('administration.correspondence.status', $record->fresh()), ['status' => 'done'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('done', $record->fresh()->status);
        $this->assertSame('Tindak lanjuti oleh bagian kurikulum.', $record->fresh()->disposition);
    }

    public function test_cross_school_records_and_attachments_are_not_visible_or_mutable(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '77553312', 'level' => 'MTs']);
        $record = CorrespondenceRecord::create([
            'school_id' => $otherSchool->id,
            'created_by' => $this->admin->id,
            'letter_type' => 'incoming',
            'agenda_number' => 'IN/2026/000999',
            'received_at' => '2026-10-22',
            'sender' => 'Sekolah Lain',
            'subject' => 'Rahasia sekolah lain',
            'status' => 'received',
            'attachment_path' => 'correspondence/999/secret.pdf',
            'attachment_name' => 'secret.pdf',
        ]);
        Storage::disk('local')->put($record->attachment_path, 'PRIVATE');

        $this->actingAs($this->admin)
            ->get(route('administration.correspondence.index'))
            ->assertInertia(fn ($page) => $page->has('records', 0));
        $this->patch(route('administration.correspondence.status', $record), ['status' => 'disposition'])
            ->assertNotFound();
        $this->get(route('administration.correspondence.attachment', $record->id))
            ->assertNotFound();
    }

    private function makeUser(Role $role, string $email, string $name): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => $name,
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }
}
