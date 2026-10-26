<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAndPermissionStressTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private Term $activeTerm;

    private ClassGroup $classGroup;

    private Subject $subject;

    private User $adminUser;

    private User $operatorUser;

    private User $principalUser;

    private User $homeroomUser;

    private User $teacherUser;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Establish School Context
        $this->school = School::create([
            'name' => 'SMA Negeri 1 Empirical Stress Test',
            'npsn' => '99887766',
            'level' => 'SMA',
        ]);

        // 2. Roles
        $adminRole = Role::create([
            'name' => RoleEnum::ADMIN->value,
            'label' => 'Administrator',
            'is_system' => true,
        ]);
        $operatorRole = Role::create([
            'name' => RoleEnum::OPERATOR->value,
            'label' => 'Operator',
            'is_system' => true,
        ]);
        $principalRole = Role::create([
            'name' => RoleEnum::PRINCIPAL->value,
            'label' => 'Kepala Sekolah',
            'is_system' => true,
        ]);
        $homeroomRole = Role::create([
            'name' => RoleEnum::HOMEROOM->value,
            'label' => 'Wali Kelas',
            'is_system' => true,
        ]);
        $teacherRole = Role::create([
            'name' => RoleEnum::TEACHER->value,
            'label' => 'Guru Pengajar',
            'is_system' => true,
        ]);
        $studentRole = Role::create([
            'name' => RoleEnum::STUDENT->value,
            'label' => 'Siswa',
            'is_system' => true,
        ]);

        // 3. Core Permissions for Settings
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

        $operatorRole->permissions()->attach([
            $permViewUsers->id,
            $permCreateUsers->id,
            $permViewSchool->id,
            $permUpdateSchool->id,
        ]);
        $principalRole->permissions()->attach([
            $permViewUsers->id,
            $permViewSchool->id,
        ]);

        // 4. Academic Structure
        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $this->activeTerm = Term::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->classGroup = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'X-MIPA-1',
            'grade_level' => 10,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Matematika Peminatan',
            'code' => 'MTK-P',
            'is_active' => true,
            'order' => 1,
        ]);

        // 5. Users and Role Entities
        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Bambang Admin',
            'username' => 'bambang.admin',
            'email' => 'admin@empirical.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->operatorUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $operatorRole->id,
            'name' => 'Citra Operator',
            'username' => 'citra.operator',
            'email' => 'operator@empirical.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->principalUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $principalRole->id,
            'name' => 'Hendra Kepsek',
            'username' => 'hendra.kepsek',
            'email' => 'kepsek@empirical.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->homeroomUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $homeroomRole->id,
            'name' => 'Rahmat Wali Kelas',
            'username' => 'rahmat.wali',
            'email' => 'homeroom@empirical.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $homeroomTeacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->homeroomUser->id,
            'name' => 'Rahmat Wali Kelas',
            'nip' => '198201012010011005',
            'is_active' => true,
        ]);
        $this->classGroup->update(['homeroom_teacher_id' => $homeroomTeacher->id]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Dewi Guru',
            'username' => 'dewi.guru',
            'email' => 'teacher@empirical.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'name' => 'Dewi Guru',
            'nip' => '198505052012012003',
            'is_active' => true,
        ]);

        TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $this->subject->id,
            'class_group_id' => $this->classGroup->id,
            'term_id' => $this->activeTerm->id,
        ]);

        TeachingAssignment::create([
            'teacher_id' => $homeroomTeacher->id,
            'subject_id' => $this->subject->id,
            'class_group_id' => $this->classGroup->id,
            'term_id' => $this->activeTerm->id,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Ahmad Siswa',
            'username' => 'ahmad.siswa',
            'email' => 'student@empirical.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->studentUser->id,
            'name' => 'Ahmad Siswa',
            'nis' => '10001',
            'nisn' => '0099887766',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_group_id' => $this->classGroup->id,
            'term_id' => $this->activeTerm->id,
            'status' => 'active',
        ]);
    }

    /**
     * Requirement: Confirm that NO route returns HTTP 404 (specifically /laporan vs /penilaian/laporan).
     */
    public function test_broken_legacy_route_penilaian_laporan_returns_404_while_canonical_laporan_succeeds(): void
    {
        // /penilaian/laporan must return 404
        $this->actingAs($this->adminUser)
            ->get('/penilaian/laporan')
            ->assertStatus(404);

        $this->actingAs($this->principalUser)
            ->get('/penilaian/laporan')
            ->assertStatus(404);

        // /laporan must return 200 OK
        $this->actingAs($this->adminUser)
            ->get('/laporan')
            ->assertOk();

        $this->actingAs($this->principalUser)
            ->get('/laporan')
            ->assertOk();

        $this->actingAs($this->operatorUser)
            ->get('/laporan')
            ->assertOk();
    }

    /**
     * Requirement: Confirm student verified working action links do not produce HTTP 403 Forbidden,
     * and succeed with HTTP 200 OK.
     */
    public function test_student_working_core_actions_succeed(): void
    {
        $this->actingAs($this->studentUser);

        $studentActions = [
            '/',
            '/pembelajaran',
            '/akademik/jadwal',
            '/ujian',
            '/penilaian/rapor',
            '/pembelajaran/progress',
            '/komunikasi/chat',
            '/profil',
        ];

        foreach ($studentActions as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                404,
                $response->getStatusCode(),
                "Student route '{$url}' returned 404 Not Found"
            );
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Student action '{$url}' returned 403 Forbidden"
            );
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Student route '{$url}' failed with status: ".$response->getStatusCode()
            );
        }
    }

    /**
     * Requirement: Confirm that student is strictly prohibited from accessing restricted routes (403 Forbidden).
     */
    public function test_student_is_strictly_forbidden_from_privileged_routes(): void
    {
        $this->actingAs($this->studentUser);

        $forbiddenRoutes = [
            '/akademik/presensi',
            '/akademik/jurnal',
            '/akademik/siswa',
            '/akademik/guru',
            '/akademik/siklus-siswa',
            '/akademik/pengaturan',
            '/penilaian/paket-soal',
            '/penilaian/berbagi-soal',
            '/penilaian/bank-soal',
            '/penilaian/skor',
            '/penilaian/sikap',
            '/penilaian/analisis',
            '/laporan',
            '/wali-kelas',
            '/wali-kelas/catatan',
            '/wali-kelas/kedisiplinan',
            '/komunikasi/whatsapp',
            '/pengaturan/pengguna',
            '/pengaturan/sekolah',
            '/pengaturan/keamanan',
        ];

        foreach ($forbiddenRoutes as $url) {
            $response = $this->get($url);
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Student should have received 403 Forbidden for '{$url}', but received: ".$response->getStatusCode()
            );
        }
    }

    /**
     * Requirement: Confirm homeroom direct links are accessible and return 200 OK.
     */
    public function test_homeroom_core_direct_links_and_actions_succeed(): void
    {
        $this->actingAs($this->homeroomUser);

        $homeroomRoutes = [
            '/',
            '/wali-kelas',
            '/wali-kelas/catatan',
            '/wali-kelas/kedisiplinan',
            '/komunikasi/whatsapp',
            '/penilaian/skor',
            '/penilaian/sikap',
            '/penilaian/rapor',
            '/akademik/presensi',
            '/akademik/jurnal',
            '/akademik/jadwal',
            '/akademik/siswa',
            '/akademik/kelas',
            '/pembelajaran',
            '/pembelajaran/progress',
            '/penilaian/bank-soal',
            '/penilaian/paket-soal',
            '/penilaian/berbagi-soal',
            '/ujian',
            '/penilaian/analisis',
            '/komunikasi',
            '/komunikasi/chat',
            '/profil',
        ];

        foreach ($homeroomRoutes as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                404,
                $response->getStatusCode(),
                "Homeroom route '{$url}' returned 404 Not Found"
            );
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Homeroom route '{$url}' returned 403 Forbidden"
            );
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Homeroom route '{$url}' failed with status: ".$response->getStatusCode()
            );
        }

        // Homeroom must be forbidden from administrative settings & reporting
        $forbiddenForHomeroom = [
            '/laporan',
            '/pengaturan/pengguna',
            '/pengaturan/sekolah',
            '/pengaturan/keamanan',
            '/akademik/siklus-siswa',
            '/akademik/pengaturan',
        ];

        foreach ($forbiddenForHomeroom as $url) {
            $response = $this->get($url);
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Homeroom should have received 403 Forbidden for '{$url}', but received: ".$response->getStatusCode()
            );
        }
    }

    /**
     * Requirement: Confirm teacher core actions and navigation items succeed.
     */
    public function test_teacher_core_actions_succeed_and_boundaries_enforced(): void
    {
        $this->actingAs($this->teacherUser);

        $teacherActions = [
            '/',
            '/pembelajaran',
            '/akademik/presensi',
            '/akademik/jurnal',
            '/penilaian/skor',
            '/ujian',
            '/akademik/kelas-daring',
            '/akademik/jadwal',
            '/akademik/siswa',
            '/akademik/kelas',
            '/pembelajaran/progress',
            '/penilaian/paket-soal',
            '/penilaian/berbagi-soal',
            '/penilaian/bank-soal',
            '/penilaian/rapor',
            '/penilaian/analisis',
            '/komunikasi',
            '/komunikasi/chat',
            '/profil',
        ];

        foreach ($teacherActions as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                404,
                $response->getStatusCode(),
                "Teacher route '{$url}' returned 404 Not Found"
            );
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Teacher route '{$url}' returned 403 Forbidden"
            );
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Teacher route '{$url}' failed with status: ".$response->getStatusCode()
            );
        }

        // Teacher is strictly forbidden from homeroom specific routes, reports, and system settings
        $forbiddenForTeacher = [
            '/laporan',
            '/komunikasi/whatsapp',
            '/pengaturan/pengguna',
            '/pengaturan/sekolah',
            '/pengaturan/keamanan',
            '/akademik/siklus-siswa',
            '/akademik/pengaturan',
        ];

        foreach ($forbiddenForTeacher as $url) {
            $response = $this->get($url);
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Teacher should have received 403 Forbidden for '{$url}', but received: ".$response->getStatusCode()
            );
        }
    }

    /**
     * Requirement: Confirm principal role actions and monitoring views succeed.
     */
    public function test_principal_role_actions_succeed_and_boundaries_enforced(): void
    {
        $this->actingAs($this->principalUser);

        $principalActions = [
            '/',
            '/laporan',
            '/penilaian/analisis',
            '/akademik/siswa',
            '/akademik/guru',
            '/akademik/jadwal',
            '/akademik/kelas',
            '/akademik/siklus-siswa',
            '/akademik/pengaturan',
            '/pembelajaran/progress',
            '/penilaian/rapor',
            '/komunikasi',
            '/pengaturan/sekolah',
            '/pengaturan/keamanan',
            '/profil',
        ];

        foreach ($principalActions as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                404,
                $response->getStatusCode(),
                "Principal route '{$url}' returned 404 Not Found"
            );
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Principal route '{$url}' returned 403 Forbidden"
            );
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Principal route '{$url}' failed with status: ".$response->getStatusCode()
            );
        }

        $this->get('/komunikasi/whatsapp')->assertForbidden();

        // Principal is forbidden from classroom teaching execution routes
        $forbiddenForPrincipal = [
            '/akademik/presensi',
            '/akademik/jurnal',
            '/wali-kelas',
            '/penilaian/skor',
            '/penilaian/sikap',
            '/pembelajaran',
            '/komunikasi/chat',
        ];

        foreach ($forbiddenForPrincipal as $url) {
            $response = $this->get($url);
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Principal should have received 403 Forbidden for '{$url}', but received: ".$response->getStatusCode()
            );
        }
    }

    /**
     * Requirement: Confirm operator role actions and administration views succeed.
     */
    public function test_operator_role_actions_succeed_and_boundaries_enforced(): void
    {
        $this->actingAs($this->operatorUser);

        $operatorActions = [
            '/',
            '/akademik/siswa',
            '/akademik/guru',
            '/akademik/kelas',
            '/akademik/jadwal',
            '/akademik/siklus-siswa',
            '/akademik/pengaturan',
            '/pembelajaran/progress',
            '/ujian',
            '/penilaian/skema',
            '/penilaian/rapor',
            '/penilaian/analisis',
            '/laporan',
            '/komunikasi',
            '/komunikasi/whatsapp',
            '/profil',
            '/pengaturan/pengguna',
            '/pengaturan/sekolah',
        ];

        foreach ($operatorActions as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                404,
                $response->getStatusCode(),
                "Operator route '{$url}' returned 404 Not Found"
            );
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Operator route '{$url}' returned 403 Forbidden"
            );
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Operator route '{$url}' failed with status: ".$response->getStatusCode()
            );
        }

        // Operator is forbidden from classroom direct operations and security logs
        $forbiddenForOperator = [
            '/akademik/presensi',
            '/akademik/jurnal',
            '/wali-kelas',
            '/penilaian/skor',
            '/penilaian/sikap',
            '/pengaturan/keamanan',
            '/pembelajaran',
            '/penilaian/paket-soal',
            '/penilaian/berbagi-soal',
            '/penilaian/bank-soal',
            '/komunikasi/chat',
        ];

        foreach ($forbiddenForOperator as $url) {
            $response = $this->get($url);
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Operator should have received 403 Forbidden for '{$url}', but received: ".$response->getStatusCode()
            );
        }
    }

    /**
     * Requirement: Confirm admin role actions all succeed.
     */
    public function test_admin_role_actions_and_boundaries_are_enforced(): void
    {
        $this->actingAs($this->adminUser);

        $adminActions = [
            '/',
            '/pengaturan/pengguna',
            '/akademik/siswa',
            '/akademik/guru',
            '/akademik/kelas',
            '/akademik/jadwal',
            '/akademik/siklus-siswa',
            '/akademik/pengaturan',
            '/pembelajaran/progress',
            '/ujian',
            '/penilaian/skema',
            '/penilaian/rapor',
            '/penilaian/analisis',
            '/laporan',
            '/komunikasi',
            '/komunikasi/whatsapp',
            '/pengaturan/keamanan',
            '/pengaturan/sekolah',
            '/profil',
        ];

        foreach ($adminActions as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(
                404,
                $response->getStatusCode(),
                "Admin route '{$url}' returned 404 Not Found"
            );
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Admin route '{$url}' returned 403 Forbidden"
            );
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Admin route '{$url}' failed with status: ".$response->getStatusCode()
            );
        }

        foreach (['/pembelajaran', '/penilaian/bank-soal', '/penilaian/paket-soal', '/penilaian/berbagi-soal', '/komunikasi/chat'] as $url) {
            $this->get($url)->assertForbidden("Admin should have received 403 Forbidden for '{$url}'.");
        }
    }

    /**
     * EMPIRICAL CHALLENGE 1:
     * OnlineClassController::authorizeAccess strictly blocks 'student' and 'homeroom' with HTTP 403 Forbidden,
     * even though SidebarNavigation.vue and Dashboard.vue expose /akademik/kelas-daring to students and homeroom,
     * and routes/web.php declares middleware('role:teacher,homeroom,student').
     */
    public function test_challenge_vulnerability_online_class_controller_blocks_student_and_homeroom(): void
    {
        // Empirically verify that Student is permitted with 200 on /akademik/kelas-daring
        $studentResponse = $this->actingAs($this->studentUser)->get('/akademik/kelas-daring');
        $this->assertEquals(
            200,
            $studentResponse->getStatusCode(),
            'Expected OnlineClassController to permit student with 200'
        );

        // Empirically verify that Homeroom is permitted with 200 on /akademik/kelas-daring
        $homeroomResponse = $this->actingAs($this->homeroomUser)->get('/akademik/kelas-daring');
        $this->assertEquals(
            200,
            $homeroomResponse->getStatusCode(),
            'Expected OnlineClassController to permit homeroom with 200'
        );
    }

    /**
     * EMPIRICAL CHALLENGE 2:
     * StudentAttitudeController::authorizeManage strictly blocks 'teacher' with HTTP 200 OK.
     */
    public function test_challenge_vulnerability_student_attitude_controller_blocks_teacher(): void
    {
        // Empirically verify that Teacher is permitted with 200 on /penilaian/sikap
        $teacherResponse = $this->actingAs($this->teacherUser)->get('/penilaian/sikap');
        $this->assertEquals(
            200,
            $teacherResponse->getStatusCode(),
            'Expected StudentAttitudeController to permit teacher with 200'
        );
    }

    /**
     * EMPIRICAL CHALLENGE 3:
     * CommunicationController::visibleClassIds throws Fatal Error (HTTP 500) for Student
     * because Enrollment class is referenced without being imported at top of file.
     */
    public function test_challenge_vulnerability_communication_controller_fatal_500_for_student(): void
    {
        // Empirically verify that Student visiting /komunikasi succeeds with HTTP 200 OK
        $response = $this->actingAs($this->studentUser)->get('/komunikasi');
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Expected CommunicationController to return 200 OK for Student'
        );
    }
}
