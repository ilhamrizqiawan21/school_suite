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
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Role $adminRole;

    private Role $operatorRole;

    private Role $principalRole;

    private Role $homeroomRole;

    private Role $teacherRole;

    private Role $studentRole;

    private Term $activeTerm;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMA Negeri 1 Uji',
            'npsn' => '88889999',
            'level' => 'SMA',
        ]);

        $this->adminRole = Role::create([
            'name' => RoleEnum::ADMIN->value,
            'label' => 'Administrator',
            'is_system' => true,
        ]);

        $this->operatorRole = Role::create([
            'name' => RoleEnum::OPERATOR->value,
            'label' => 'Operator',
            'is_system' => true,
        ]);

        $this->principalRole = Role::create([
            'name' => RoleEnum::PRINCIPAL->value,
            'label' => 'Kepala Sekolah',
            'is_system' => true,
        ]);

        $this->homeroomRole = Role::create([
            'name' => RoleEnum::HOMEROOM->value,
            'label' => 'Wali Kelas',
            'is_system' => true,
        ]);

        $this->teacherRole = Role::create([
            'name' => RoleEnum::TEACHER->value,
            'label' => 'Guru Pengajar',
            'is_system' => true,
        ]);

        $this->studentRole = Role::create([
            'name' => RoleEnum::STUDENT->value,
            'label' => 'Siswa',
            'is_system' => true,
        ]);

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
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_administrator_accesses_dashboard_with_admin_metrics(): void
    {
        $admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Budi Administrator',
            'username' => 'admin.budi',
            'email' => 'admin@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Student::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Sintetis 1',
            'nis' => '1001',
            'nisn' => '0011223344',
            'status' => 'active',
        ]);

        Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $admin->id,
            'name' => 'Guru Sintetis 1',
            'nip' => '198501012010011001',
            'is_active' => true,
        ]);

        Announcement::create([
            'school_id' => $this->school->id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Uji',
            'body' => 'Isi pengumuman uji.',
            'category' => 'academic',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->where('stats.modules_ready', 9)
                ->where('stats.role_metrics.students', 1)
                ->where('stats.role_metrics.active_users', 1)
                ->where('stats.role_metrics.recent_announcements', 1)
                ->where('stats.role_metrics.teachers', 1)
            );
    }

    public function test_teacher_accesses_dashboard_with_teacher_metrics(): void
    {
        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->teacherRole->id,
            'name' => 'Dewi Guru',
            'username' => 'guru.dewi',
            'email' => 'dewi@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
            'name' => 'Dewi Guru',
            'nip' => '198901012015012002',
            'is_active' => true,
        ]);

        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'X-A',
            'grade_level' => 10,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Matematika',
            'code' => 'MTK',
        ]);

        TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_group_id' => $class->id,
            'term_id' => $this->activeTerm->id,
        ]);

        ExamQuestion::create([
            'school_id' => $this->school->id,
            'owner_id' => $teacherUser->id,
            'prompt' => 'Berapa hasil dari 1 + 1?',
            'type' => 'single_choice',
            'points' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($teacherUser)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->where('stats.role_metrics.teaching_assignments', 1)
                ->where('stats.role_metrics.active_questions', 1)
            );
    }

    public function test_student_accesses_dashboard_with_student_metrics(): void
    {
        $studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->studentRole->id,
            'name' => 'Ahmad Siswa',
            'username' => 'siswa.ahmad',
            'email' => 'ahmad@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'name' => 'Ahmad Siswa',
            'nis' => '2001',
            'nisn' => '0099887766',
            'status' => 'active',
        ]);

        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'X-B',
            'grade_level' => 10,
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_group_id' => $class->id,
            'term_id' => $this->activeTerm->id,
            'status' => 'active',
        ]);

        $this->actingAs($studentUser)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->where('stats.role_metrics.active_enrollments', 1)
            );
    }

    public function test_operator_accesses_dashboard_with_operator_metrics(): void
    {
        $operator = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->operatorRole->id,
            'name' => 'Citra Operator',
            'username' => 'operator.citra',
            'email' => 'operator@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Student::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Operator 1',
            'nis' => '1002',
            'nisn' => '0011223345',
            'status' => 'active',
        ]);

        ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'X-C',
            'grade_level' => 10,
        ]);

        $this->actingAs($operator)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->where('stats.role_metrics.students', 1)
                ->where('stats.role_metrics.active_classes', 1)
                ->where('stats.role_metrics.active_users', 1)
            );
    }

    public function test_principal_accesses_dashboard_with_principal_metrics(): void
    {
        $principal = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->principalRole->id,
            'name' => 'Hendra Kepsek',
            'username' => 'kepsek.hendra',
            'email' => 'kepsek@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Student::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Kepsek 1',
            'nis' => '1003',
            'nisn' => '0011223346',
            'status' => 'active',
        ]);

        Announcement::create([
            'school_id' => $this->school->id,
            'author_id' => $principal->id,
            'title' => 'Pengumuman Kepsek',
            'body' => 'Isi arahan kepala sekolah.',
            'category' => 'academic',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($principal)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->where('stats.role_metrics.students', 1)
                ->where('stats.role_metrics.recent_announcements', 1)
            );
    }

    public function test_homeroom_accesses_dashboard_with_homeroom_metrics(): void
    {
        $homeroomUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->homeroomRole->id,
            'name' => 'Rahmat Wali Kelas',
            'username' => 'wali.rahmat',
            'email' => 'rahmat@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $homeroomUser->id,
            'name' => 'Rahmat Wali Kelas',
            'nip' => '198701012012011003',
            'is_active' => true,
        ]);

        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'XI-A',
            'grade_level' => 11,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Fisika',
            'code' => 'FIS',
        ]);

        TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_group_id' => $class->id,
            'term_id' => $this->activeTerm->id,
        ]);

        ExamQuestion::create([
            'school_id' => $this->school->id,
            'owner_id' => $homeroomUser->id,
            'prompt' => 'Apa rumus kecepatan?',
            'type' => 'essay',
            'points' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($homeroomUser)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->where('stats.role_metrics.teaching_assignments', 1)
                ->where('stats.role_metrics.active_questions', 1)
            );
    }
}
