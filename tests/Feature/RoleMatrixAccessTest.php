<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleMatrixAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    /** @var array<string, User> */
    private array $usersByRole = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMA Matrix Access Test',
            'npsn' => '66667777',
            'level' => 'SMA',
        ]);

        $rolesData = [
            RoleEnum::ADMIN->value => RoleEnum::ADMIN->label(),
            RoleEnum::OPERATOR->value => RoleEnum::OPERATOR->label(),
            RoleEnum::PRINCIPAL->value => RoleEnum::PRINCIPAL->label(),
            RoleEnum::TEACHER->value => RoleEnum::TEACHER->label(),
            RoleEnum::HOMEROOM->value => RoleEnum::HOMEROOM->label(),
            RoleEnum::STUDENT->value => RoleEnum::STUDENT->label(),
        ];

        // Core permissions
        $permViewUsers = Permission::create([
            'name' => PermissionEnum::USERS_VIEW->value,
            'label' => 'Lihat Pengguna',
            'module' => 'Core',
        ]);
        $permCreateUsers = Permission::create([
            'name' => PermissionEnum::USERS_CREATE->value,
            'label' => 'Tambah Pengguna',
            'module' => 'Core',
        ]);
        $permViewSchool = Permission::create([
            'name' => PermissionEnum::SCHOOL_VIEW->value,
            'label' => 'Lihat Sekolah',
            'module' => 'Core',
        ]);
        $permUpdateSchool = Permission::create([
            'name' => PermissionEnum::SCHOOL_UPDATE->value,
            'label' => 'Ubah Sekolah',
            'module' => 'Core',
        ]);

        foreach ($rolesData as $name => $label) {
            $role = Role::create([
                'name' => $name,
                'label' => $label,
                'is_system' => true,
            ]);

            // Assign specific permissions per role for testing
            if ($name === RoleEnum::OPERATOR->value) {
                $role->permissions()->attach([$permViewUsers->id, $permCreateUsers->id, $permViewSchool->id, $permUpdateSchool->id]);
            } elseif ($name === RoleEnum::PRINCIPAL->value) {
                $role->permissions()->attach([$permViewUsers->id, $permViewSchool->id]);
            }

            $user = User::create([
                'school_id' => $this->school->id,
                'role_id' => $role->id,
                'name' => "User {$label}",
                'username' => "user.{$name}",
                'email' => "{$name}@matrix.test",
                'password' => 'secret123',
                'is_active' => true,
            ]);

            $this->usersByRole[$name] = $user;
        }
    }

    /**
     * Matriks Uji 1: Administrator memiliki hak akses penuh ke seluruh endpoint Core.
     */
    public function test_administrator_has_full_core_access(): void
    {
        $admin = $this->usersByRole[RoleEnum::ADMIN->value];
        $this->actingAs($admin);

        $this->get('/pengaturan/pengguna')->assertStatus(200);
        $this->get('/pengaturan/sekolah')->assertStatus(200);
    }

    public function test_whatsapp_routes_are_unavailable(): void
    {
        $this->actingAs($this->usersByRole[RoleEnum::ADMIN->value]);

        $this->get('/komunikasi/whatsapp')->assertNotFound();
        $this->post('/komunikasi/whatsapp', [])->assertNotFound();
        $this->post('/komunikasi/whatsapp/1/terkirim')->assertNotFound();
    }

    public function test_sidebar_visibility_is_shared_by_the_server_for_each_role(): void
    {
        $admin = $this->usersByRole[RoleEnum::ADMIN->value];
        $this->actingAs($admin)->get('/profil')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('navigation.visible_keys', fn ($keys) => $keys->contains('students')
                && $keys->contains('users')
                && $keys->contains('security')
                && ! $keys->contains('journals'))
        );

        $teacher = $this->usersByRole[RoleEnum::TEACHER->value];
        $this->actingAs($teacher)->get('/profil')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('navigation.visible_keys', fn ($keys) => $keys->contains('journals')
                && $keys->contains('learning-resources')
                && ! $keys->contains('student-ledger')
                && ! $keys->contains('security')
                && ! $keys->contains('admissions'))
        );
    }

    /**
     * Matriks Uji 2: Operator memiliki izin melihat dan mengelola pengguna serta profil sekolah.
     */
    public function test_operator_has_granted_core_access(): void
    {
        $operator = $this->usersByRole[RoleEnum::OPERATOR->value];
        $this->actingAs($operator);

        $this->get('/pengaturan/pengguna')->assertStatus(200);
        $this->get('/pengaturan/sekolah')->assertStatus(200);
    }

    /**
     * Matriks Uji 3: Kepala Sekolah dapat melihat daftar pengguna dan profil sekolah (Read Only).
     */
    public function test_principal_can_view_users_and_school_but_cannot_create_user(): void
    {
        $principal = $this->usersByRole[RoleEnum::PRINCIPAL->value];
        $this->actingAs($principal);

        // Can view
        $this->get('/pengaturan/pengguna')->assertStatus(200);
        $this->get('/pengaturan/sekolah')->assertStatus(200);

        // Cannot create user (no core.users.create permission)
        $this->post('/pengaturan/pengguna', [
            'name' => 'Siswa Ilegal',
            'email' => 'ilegal@matrix.test',
            'role_id' => 1,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    /**
     * Matriks Uji 4: Guru Mata Pelajaran dilarang keras mengakses manajemen pengguna Core (403 Forbidden).
     */
    public function test_teacher_is_forbidden_from_core_settings(): void
    {
        $teacher = $this->usersByRole[RoleEnum::TEACHER->value];
        $this->actingAs($teacher);

        $this->get('/pengaturan/pengguna')->assertStatus(403);
        $this->get('/pengaturan/sekolah')->assertStatus(403);
    }

    /**
     * Matriks Uji 5: Wali Kelas dilarang keras mengakses manajemen pengguna Core (403 Forbidden).
     */
    public function test_homeroom_is_forbidden_from_core_settings(): void
    {
        $homeroom = $this->usersByRole[RoleEnum::HOMEROOM->value];
        $this->actingAs($homeroom);

        $this->get('/pengaturan/pengguna')->assertStatus(403);
        $this->get('/pengaturan/sekolah')->assertStatus(403);
    }

    /**
     * Matriks Uji 6: Siswa dilarang keras mengakses manajemen pengguna Core (403 Forbidden).
     */
    public function test_student_is_forbidden_from_core_settings(): void
    {
        $student = $this->usersByRole[RoleEnum::STUDENT->value];
        $this->actingAs($student);

        $this->get('/pengaturan/pengguna')->assertStatus(403);
        $this->get('/pengaturan/sekolah')->assertStatus(403);
    }

    public function test_principal_data_pages_are_read_only_even_if_manage_permissions_are_assigned(): void
    {
        $principal = $this->usersByRole[RoleEnum::PRINCIPAL->value];
        $permissions = collect([
            PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value,
            PermissionEnum::ACADEMIC_CLASSES_MANAGE->value,
            PermissionEnum::ACADEMIC_SCHEDULE_MANAGE->value,
            PermissionEnum::ACADEMIC_ATTENDANCE_RECORD->value,
        ])->map(fn (string $name) => Permission::firstOrCreate(
            ['name' => $name],
            ['label' => $name, 'module' => 'Academic']
        ));
        $principal->role->permissions()->attach($permissions->pluck('id'));
        $this->actingAs($principal);

        $this->get('/akademik/siswa')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Students/Index')
            ->where('can_manage', false));
        $this->get('/akademik/guru')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Teachers/Index')
            ->where('can_manage', false));
        $this->get('/akademik/kelas')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Classes/Index')
            ->where('can_manage', false));
        $this->get('/akademik/jadwal')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Schedules/Index')
            ->where('can_manage', false));
        $this->get('/akademik/siklus-siswa')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Lifecycle/Index')
            ->where('can_manage', false));
        $this->get('/akademik/pengaturan')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Academic/Settings/Index')
            ->where('can_manage', false));
        $this->get('/laporan')->assertOk();
        $this->get('/akademik/presensi/rekap')->assertOk();
        $this->get('/penilaian/skema')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Schemes/Index')
            ->where('can_manage', false));
        $this->get('/penilaian/rapor')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Reports/Index')
            ->where('can_publish', false));

        $this->post('/akademik/siswa', [])->assertForbidden();
        $this->post('/akademik/guru', [])->assertForbidden();
        $this->post('/akademik/kelas', [])->assertForbidden();
        $this->post('/akademik/jadwal', [])->assertForbidden();
        $this->post('/akademik/presensi', [])->assertForbidden();
        $this->post('/akademik/siklus-siswa', [])->assertForbidden();
        $this->post('/akademik/pengaturan/tahun-ajaran', [])->assertForbidden();
        $this->post('/penilaian/skema', [])->assertForbidden();
        $this->post('/penilaian/rapor/generate-batch', [])->assertForbidden();
        $this->post('/komunikasi/pengumuman', [])->assertForbidden();
        $this->post('/komunikasi/kalender', [])->assertForbidden();
        $this->post('/penilaian/sikap', [])->assertForbidden();
        $this->post('/akademik/kelas-daring', [])->assertForbidden();
    }
}
