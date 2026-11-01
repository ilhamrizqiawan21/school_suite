<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolInstallCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('SCHOOL_INSTALL_ADMIN_PASSWORD');

        parent::tearDown();
    }

    public function test_it_creates_a_school_roles_permissions_and_first_admin_without_demo_data(): void
    {
        putenv('SCHOOL_INSTALL_ADMIN_PASSWORD=school-admin-123');

        $this->artisan('school:install', [
            '--name' => 'SMA Nusantara',
            '--npsn' => '12345678',
            '--level' => 'SMA',
            '--admin-name' => 'Admin Nusantara',
            '--admin-email' => 'admin@nusantara.sch.id',
            '--admin-username' => 'admin.nusantara',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $school = School::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@nusantara.sch.id')->firstOrFail();

        $this->assertSame('SMA Nusantara', $school->name);
        $this->assertSame('12345678', $school->npsn);
        $this->assertSame($school->id, $admin->school_id);
        $this->assertSame('admin', $admin->role?->name);
        $this->assertFalse($admin->must_change_password);
        $this->assertTrue(Hash::check('school-admin-123', $admin->password));
        $this->assertSame(1, School::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertNotNull(Role::query()->where('name', 'operator')->first());
        $this->assertNotNull(Permission::query()->where('name', 'core.users.create')->first());
    }

    public function test_it_refuses_to_install_a_second_school(): void
    {
        School::query()->create([
            'name' => 'Sekolah Pertama',
            'npsn' => '87654321',
            'level' => 'SMA',
        ]);

        $this->artisan('school:install', [
            '--name' => 'Sekolah Kedua',
            '--npsn' => '12345678',
            '--admin-name' => 'Admin Kedua',
            '--admin-email' => 'admin@kedua.sch.id',
            '--admin-username' => 'admin.kedua',
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame(1, School::query()->count());
        $this->assertDatabaseMissing('users', ['email' => 'admin@kedua.sch.id']);
    }

    public function test_non_interactive_install_requires_a_password_from_the_environment(): void
    {
        $this->artisan('school:install', [
            '--name' => 'SMA Tanpa Password',
            '--npsn' => '12345678',
            '--admin-name' => 'Admin Tanpa Password',
            '--admin-email' => 'admin@tanpa-password.sch.id',
            '--admin-username' => 'admin.tanpa-password',
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame(0, School::query()->count());
        $this->assertSame(0, User::query()->count());
    }
}
