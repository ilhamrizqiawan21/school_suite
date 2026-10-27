<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Application\Services\UserService;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoreAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private Role $adminRole;

    private Role $teacherRole;

    private Role $operatorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = School::create([
            'name' => 'SMA Sintetis 1',
            'npsn' => '11111111',
            'level' => 'SMA',
        ]);

        $this->schoolB = School::create([
            'name' => 'SMA Sintetis 2',
            'npsn' => '22222222',
            'level' => 'SMA',
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

        $this->operatorRole = Role::create([
            'name' => RoleEnum::OPERATOR->value,
            'label' => RoleEnum::OPERATOR->label(),
            'is_system' => true,
        ]);
    }

    /**
     * Test Rule 6: Server-side Default Deny for users without permissions.
     */
    public function test_default_deny_user_without_permissions_is_denied(): void
    {
        $teacher = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Biasa',
            'email' => 'guru@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $otherUser = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Lain',
            'email' => 'other@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        // Default Deny:
        $this->assertFalse(Gate::forUser($teacher)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('view', $otherUser));
        $this->assertFalse(Gate::forUser($teacher)->allows('create', User::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherUser));
        $this->assertFalse(Gate::forUser($teacher)->allows('delete', $otherUser));
    }

    /**
     * Test users can view and update their own profile regardless of role permissions.
     */
    public function test_users_can_view_and_update_their_own_profile(): void
    {
        $user = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Mandiri',
            'email' => 'mandiri@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $user));
        $this->assertTrue(Gate::forUser($user)->allows('update', $user));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $user)); // cannot delete self
    }

    /**
     * Invariant: Cross-school access is strictly forbidden (School isolation).
     */
    public function test_cross_school_access_is_strictly_forbidden(): void
    {
        // Grant operator in School A permissions to view and update users
        $permView = Permission::create([
            'name' => PermissionEnum::USERS_VIEW->value,
            'label' => 'Lihat Pengguna',
            'module' => 'Core',
        ]);
        $permUpdate = Permission::create([
            'name' => PermissionEnum::USERS_UPDATE->value,
            'label' => 'Ubah Pengguna',
            'module' => 'Core',
        ]);
        $this->operatorRole->permissions()->attach([$permView->id, $permUpdate->id]);

        $operatorA = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->operatorRole->id,
            'name' => 'Operator Sekolah A',
            'email' => 'operator@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $userSchoolA = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Sekolah A',
            'email' => 'guru@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $userSchoolB = User::create([
            'school_id' => $this->schoolB->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Sekolah B',
            'email' => 'guru@schoolb.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        // Allowed within School A
        $this->assertTrue(Gate::forUser($operatorA)->allows('view', $userSchoolA));
        $this->assertTrue(Gate::forUser($operatorA)->allows('update', $userSchoolA));

        // FORBIDDEN across schools: School A operator cannot inspect or modify School B user
        $this->assertFalse(Gate::forUser($operatorA)->allows('view', $userSchoolB));
        $this->assertFalse(Gate::forUser($operatorA)->allows('update', $userSchoolB));
        $this->assertFalse(Gate::forUser($operatorA)->allows('delete', $userSchoolB));
    }

    /**
     * Invariant: Self-deletion is forbidden even for an Administrator.
     */
    public function test_self_deletion_is_forbidden_even_for_admin(): void
    {
        $admin = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin Utama',
            'email' => 'admin@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $targetUser = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Target Guru',
            'email' => 'target@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        // Admin can delete another user in their school
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $targetUser));

        // Admin cannot delete themselves
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
    }

    public function test_admin_cannot_update_a_user_from_another_school(): void
    {
        $admin = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin A',
            'email' => 'admin.boundary@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $foreignUser = User::create([
            'school_id' => $this->schoolB->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru B',
            'email' => 'guru.boundary@schoolb.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put("/pengaturan/pengguna/{$foreignUser->id}", [
                'name' => 'Nama yang tidak boleh berubah',
                'email' => $foreignUser->email,
                'role_id' => $this->teacherRole->id,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertSame('Guru B', $foreignUser->fresh()->name);
    }

    public function test_non_admin_with_user_create_permission_cannot_assign_admin_role(): void
    {
        $operator = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->operatorRole->id,
            'name' => 'Operator A',
            'email' => 'operator.role@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $permission = Permission::create([
            'name' => PermissionEnum::USERS_CREATE->value,
            'label' => 'Buat Pengguna',
            'module' => 'Core',
        ]);
        $this->operatorRole->permissions()->attach($permission->id);

        try {
            (new UserService)->createUser([
                'name' => 'Admin Ilegal',
                'email' => 'admin.illegal@schoola.sch.id',
                'password' => 'secret123',
                'role_id' => $this->adminRole->id,
            ], $operator);
            $this->fail('A non-admin actor must not assign the admin role.');
        } catch (AuthorizationException) {
            $this->assertDatabaseMissing('users', ['email' => 'admin.illegal@schoola.sch.id']);
        }

        try {
            (new UserService)->updateUser($operator, ['role_id' => $this->adminRole->id], $operator);
            $this->fail('A non-admin actor must not promote their own account.');
        } catch (ValidationException) {
            $this->assertSame($this->operatorRole->id, $operator->fresh()->role_id);
        }
    }

    public function test_non_admin_user_management_page_hides_privileged_roles_and_actions(): void
    {
        $operator = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->operatorRole->id,
            'name' => 'Operator A',
            'email' => 'operator.page@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $view = Permission::create([
            'name' => PermissionEnum::USERS_VIEW->value,
            'label' => 'Lihat Pengguna',
            'module' => 'Core',
        ]);
        $create = Permission::create([
            'name' => PermissionEnum::USERS_CREATE->value,
            'label' => 'Buat Pengguna',
            'module' => 'Core',
        ]);
        $this->operatorRole->permissions()->attach([$view->id, $create->id]);

        $this->actingAs($operator)->get('/pengaturan/pengguna')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Users/Index')
                ->where('can_create', true)
                ->where('can_update', false)
                ->where('can_delete', false)
                ->where('can_manage_roles', false)
                ->has('roles', 1)
                ->where('roles.0.name', 'teacher')
            );
    }

    public function test_deactivated_admin_session_is_logged_out_on_next_request(): void
    {
        $admin = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin Nonaktif',
            'email' => 'admin.inactive@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/')->assertOk();
        $admin->update(['is_active' => false]);

        $this->get('/pengaturan/pengguna')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Test UserService enforces authorization policies server-side.
     */
    public function test_user_service_enforces_authorization_policies(): void
    {
        $service = new UserService;

        $unauthorizedActor = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Guru Tanpa Hak',
            'email' => 'unauth@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $admin = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin2@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        // 1. Unauthorized actor cannot create user
        $this->expectException(AuthorizationException::class);
        $service->createUser([
            'name' => 'Siswa Baru',
            'email' => 'siswa@schoola.sch.id',
            'password' => 'secret123',
        ], $unauthorizedActor);
    }

    /**
     * Test UserService successfully executes operations for authorized actor.
     */
    public function test_user_service_executes_for_authorized_actor(): void
    {
        $service = new UserService;

        $admin = User::create([
            'school_id' => $this->schoolA->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin Sekolah A',
            'email' => 'admin3@schoola.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        // Admin creates user
        $newUser = $service->createUser([
            'name' => 'Guru Baru',
            'username' => 'guru.baru',
            'email' => 'guru.baru@schoola.sch.id',
            'password' => 'secret123',
        ], $admin);

        $this->assertDatabaseHas('users', ['email' => 'guru.baru@schoola.sch.id']);
        $this->assertSame($this->schoolA->id, $newUser->school_id);

        // Admin updates user
        $updated = $service->updateUser($newUser, ['name' => 'Guru Baru Direvisi'], $admin);
        $this->assertSame('Guru Baru Direvisi', $updated->name);

        // Admin deletes user
        $deleted = $service->deleteUser($newUser, $admin);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('users', ['email' => 'guru.baru@schoola.sch.id']);
    }
}
