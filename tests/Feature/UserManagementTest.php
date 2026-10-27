<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Application\Services\UserService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Role $adminRole;

    private Role $teacherRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMA Negeri 1 Testing',
            'npsn' => '55554444',
            'level' => 'SMA',
            'address' => 'Jl. Gatot Subroto No. 1',
            'phone' => '021-1234567',
            'email' => 'info@sman1testing.sch.id',
        ]);

        $this->adminRole = Role::create([
            'name' => RoleEnum::ADMIN->value,
            'label' => RoleEnum::ADMIN->label(),
            'is_system' => true,
        ]);

        $this->teacherRole = Role::create([
            'name' => RoleEnum::TEACHER->value,
            'label' => RoleEnum::TEACHER->label(),
            'is_system' => true,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@sman1testing.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/pengaturan/pengguna');
        $response->assertRedirect('/login');

        $responseSchool = $this->get('/pengaturan/sekolah');
        $responseSchool->assertRedirect('/login');
    }

    public function test_admin_can_view_user_list(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/pengaturan/pengguna');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Core/Users/Index')
            ->has('users.data')
            ->has('roles')
        );
    }

    public function test_admin_can_create_user_via_controller(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/pengaturan/pengguna', [
            'name' => 'Guru Matematika',
            'email' => 'guru.mtk@sman1testing.sch.id',
            'username' => 'guru.mtk',
            'role_id' => $this->teacherRole->id,
            'phone' => '08123456789',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response->assertRedirect('/pengaturan/pengguna');
        $response->assertSessionHas('message', 'Pengguna berhasil ditambahkan.');

        $this->assertDatabaseHas('users', [
            'email' => 'guru.mtk@sman1testing.sch.id',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_admin_can_update_user_via_controller(): void
    {
        $this->actingAs($this->admin);

        $targetUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Awal',
            'email' => 'awal@sman1testing.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $response = $this->put("/pengaturan/pengguna/{$targetUser->id}", [
            'name' => 'Guru Awal Diperbarui',
            'email' => 'awal@sman1testing.sch.id',
            'role_id' => $this->teacherRole->id,
            'is_active' => false,
        ]);

        $response->assertRedirect('/pengaturan/pengguna');
        $response->assertSessionHas('message', 'Data pengguna berhasil diperbarui.');

        $targetUser->refresh();
        $this->assertSame('Guru Awal Diperbarui', $targetUser->name);
        $this->assertFalse($targetUser->is_active);
    }

    public function test_self_password_cannot_be_changed_through_user_management(): void
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'role_id' => $this->teacherRole->id, 'is_active' => true, 'must_change_password' => false]);
        $originalHash = $user->password;

        $this->actingAs($user)->put('/pengaturan/pengguna/'.$user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'abcdefgh',
        ])->assertSessionHasErrors('password');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_user_service_rejects_self_password_reset_even_for_admin(): void
    {
        $originalHash = $this->admin->password;

        try {
            app(UserService::class)->updateUser($this->admin, ['password' => 'abcdefgh'], $this->admin);
            $this->fail('Self-service password changes must use the password-change flow.');
        } catch (AuthorizationException) {
            $this->assertSame($originalHash, $this->admin->fresh()->password);
        }
    }

    public function test_admin_can_reset_another_users_password(): void
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'role_id' => $this->teacherRole->id, 'is_active' => true, 'must_change_password' => false]);

        $this->actingAs($this->admin)->put('/pengaturan/pengguna/'.$user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'ResetPassword123',
        ])->assertSessionHasNoErrors()->assertRedirect('/pengaturan/pengguna');

        $this->assertTrue(Hash::check('ResetPassword123', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_admin_can_delete_user_via_controller(): void
    {
        $this->actingAs($this->admin);

        $targetUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Hapus',
            'email' => 'hapus@sman1testing.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $response = $this->delete("/pengaturan/pengguna/{$targetUser->id}");

        $response->assertRedirect('/pengaturan/pengguna');
        $response->assertSessionHas('message', 'Pengguna berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_admin_can_view_and_update_school_profile(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/pengaturan/sekolah');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Core/School/Edit')
            ->where('school.name', 'SMA Negeri 1 Testing')
        );

        $updateResponse = $this->put('/pengaturan/sekolah', [
            'name' => 'SMA Negeri 1 Testing Unggulan',
            'npsn' => '55554444',
            'level' => 'SMA',
            'address' => 'Jl. Baru No. 99',
            'phone' => '021-9999999',
            'email' => 'info.baru@sman1testing.sch.id',
        ]);

        $updateResponse->assertRedirect('/pengaturan/sekolah');
        $updateResponse->assertSessionHas('message', 'Profil sekolah berhasil diperbarui.');

        $this->school->refresh();
        $this->assertSame('SMA Negeri 1 Testing Unggulan', $this->school->name);
        $this->assertSame('Jl. Baru No. 99', $this->school->address);

        // Verify audit event
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'core.school.updated',
            'target_type' => 'School',
            'target_id' => (string) $this->school->id,
        ]);
    }
}
