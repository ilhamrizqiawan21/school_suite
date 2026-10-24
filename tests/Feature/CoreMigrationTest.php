<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all Core tables exist and have expected columns.
     */
    public function test_core_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('schools'), 'Table schools must exist');
        $this->assertTrue(Schema::hasColumns('schools', [
            'id', 'name', 'npsn', 'level', 'address', 'phone', 'email', 'logo_path', 'settings',
        ]));

        $this->assertTrue(Schema::hasTable('roles'), 'Table roles must exist');
        $this->assertTrue(Schema::hasColumns('roles', [
            'id', 'name', 'label', 'description', 'is_system',
        ]));

        $this->assertTrue(Schema::hasTable('permissions'), 'Table permissions must exist');
        $this->assertTrue(Schema::hasColumns('permissions', [
            'id', 'name', 'label', 'module',
        ]));

        $this->assertTrue(Schema::hasTable('role_permissions'), 'Table role_permissions must exist');
        $this->assertTrue(Schema::hasColumns('role_permissions', [
            'role_id', 'permission_id',
        ]));

        $this->assertTrue(Schema::hasTable('users'), 'Table users must exist');
        $this->assertTrue(Schema::hasColumns('users', [
            'id', 'school_id', 'role_id', 'username', 'email', 'phone', 'is_active',
        ]));

        $this->assertTrue(Schema::hasTable('audit_events'), 'Table audit_events must exist');
        $this->assertTrue(Schema::hasColumns('audit_events', [
            'id', 'school_id', 'actor_id', 'event_type', 'target_type', 'target_id', 'old_values', 'new_values', 'ip_address', 'created_at',
        ]));
    }

    /**
     * Test school, role, and permission relationships with User.
     */
    public function test_school_and_role_associations_work_properly(): void
    {
        // 1. Create School
        $school = School::create([
            'name' => 'SMA Sintetis Indonesia 1',
            'npsn' => '10101010',
            'level' => 'SMA',
            'address' => 'Jl. Pendidikan No. 10',
            'email' => 'info@smasintetis.sch.id',
            'phone' => '021-5551234',
        ]);

        $this->assertDatabaseHas('schools', ['npsn' => '10101010']);

        // 2. Create Roles
        $adminRole = Role::create([
            'name' => 'admin',
            'label' => 'Administrator',
            'is_system' => true,
        ]);

        $teacherRole = Role::create([
            'name' => 'teacher',
            'label' => 'Guru Mata Pelajaran',
            'is_system' => true,
        ]);

        // 3. Create Permissions
        $permManage = Permission::create([
            'name' => 'core.users.manage',
            'label' => 'Kelola Pengguna',
            'module' => 'Core',
        ]);

        $permGrade = Permission::create([
            'name' => 'assessment.scores.input',
            'label' => 'Input Nilai Siswa',
            'module' => 'Assessment',
        ]);

        // 4. Attach Permissions to Roles
        $adminRole->permissions()->attach([$permManage->id, $permGrade->id]);
        $teacherRole->permissions()->attach([$permGrade->id]);

        $this->assertCount(2, $adminRole->permissions);
        $this->assertCount(1, $teacherRole->permissions);

        // 5. Create Users
        $admin = User::create([
            'school_id' => $school->id,
            'role_id' => $adminRole->id,
            'name' => 'Budi Administrator',
            'username' => 'admin.budi',
            'email' => 'admin.budi@smasintetis.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $teacher = User::create([
            'school_id' => $school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Siti Guru',
            'username' => 'guru.siti',
            'email' => 'siti@smasintetis.sch.id',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        // 6. Test Model Relationships & Helper Methods
        $this->assertSame('SMA Sintetis Indonesia 1', $admin->school->name);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->hasRole('teacher'));
        $this->assertTrue($admin->hasPermission('core.users.manage'));
        $this->assertTrue($admin->hasPermission('any.random.permission')); // admin has blanket permission

        $this->assertTrue($teacher->hasRole('teacher'));
        $this->assertFalse($teacher->hasRole('admin'));
        $this->assertTrue($teacher->hasPermission('assessment.scores.input'));
        $this->assertFalse($teacher->hasPermission('core.users.manage'));

        // 7. Test Audit Event logging
        $audit = AuditEvent::create([
            'school_id' => $school->id,
            'actor_id' => $admin->id,
            'event_type' => 'core.user.created',
            'target_type' => 'User',
            'target_id' => (string) $teacher->id,
            'new_values' => ['name' => 'Siti Guru', 'role' => 'teacher'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'id' => $audit->id,
            'event_type' => 'core.user.created',
            'actor_id' => $admin->id,
        ]);
        $this->assertSame('Budi Administrator', $audit->actor->name);
    }
}
