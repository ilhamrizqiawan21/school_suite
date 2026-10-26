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
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrincipalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_principal_overview_has_null_rates_and_no_invented_activity(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay()->addHours(12));
        [$principal] = $this->principalFixture();

        $this->actingAs($principal)->get('/?period=30d')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.principal_overview.period', '30d')
            ->where('stats.principal_overview.summary.attendance_rate', null)
            ->where('stats.principal_overview.summary.tasks_rate', null)
            ->where('stats.principal_overview.summary.exams_live', 0)
            ->where('stats.principal_overview.activity.teacher.sessions_total', 0)
            ->where('stats.latest_login_logs', [])
            ->has('stats.principal_overview.attendance_trend', 5)
            ->where('stats.principal_overview.attendance_trend.0.rate', null)
            ->where('stats.principal_overview.learning_by_grade', [])
        );
    }

    public function test_principal_metrics_are_scoped_to_school_term_and_selected_window(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay()->addHours(12));
        [$principal, $school, $term] = $this->principalFixture();
        $class = ClassGroup::create(['school_id' => $school->id, 'academic_year_id' => $term->academic_year_id, 'name' => 'VII-A', 'grade_level' => 7]);
        ClassGroup::create(['school_id' => $school->id, 'academic_year_id' => $term->academic_year_id, 'name' => 'IX-A', 'grade_level' => 9]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Siswa A', 'nis' => '1001', 'status' => 'active']);
        $enrollment = Enrollment::create(['term_id' => $term->id, 'class_group_id' => $class->id, 'student_id' => $student->id, 'status' => 'active']);
        $teacher = Teacher::create(['school_id' => $school->id, 'name' => 'Guru A', 'is_active' => true]);
        $subject = Subject::create(['school_id' => $school->id, 'code' => 'IPA', 'name' => 'IPA']);
        $teaching = TeachingAssignment::create(['term_id' => $term->id, 'class_group_id' => $class->id, 'teacher_id' => $teacher->id, 'subject_id' => $subject->id]);

        foreach ([['2026-10-20', 'present'], ['2026-10-21', 'absent'], ['2026-08-02', 'present']] as [$date, $status]) {
            DB::table('attendance_records')->insert(['school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'date' => $date, 'type' => 'daily', 'status' => $status]);
        }
        $recentTask = DB::table('learning_assignments')->insertGetId(['school_id' => $school->id, 'teaching_assignment_id' => $teaching->id, 'created_by' => $principal->id, 'title' => 'Tugas terbaru', 'published_at' => '2026-10-20 08:00:00']);
        DB::table('assignment_submissions')->insert(['learning_assignment_id' => $recentTask, 'enrollment_id' => $enrollment->id, 'status' => 'submitted', 'submitted_at' => '2026-10-22 09:00:00']);
        DB::table('learning_assignments')->insert(['school_id' => $school->id, 'teaching_assignment_id' => $teaching->id, 'created_by' => $principal->id, 'title' => 'Tugas lama', 'published_at' => '2026-08-01 08:00:00']);
        foreach ([['2026-10-25 08:00:00', '2026-10-27 08:00:00'], ['2026-10-01 08:00:00', '2026-10-02 08:00:00'], ['2026-10-27 08:00:00', '2026-10-29 08:00:00']] as [$start, $end]) {
            DB::table('exams')->insert(['school_id' => $school->id, 'teaching_assignment_id' => $teaching->id, 'created_by' => $principal->id, 'title' => 'Ujian', 'starts_at' => $start, 'ends_at' => $end, 'duration_minutes' => 60, 'status' => 'published']);
        }
        $session = DB::table('lesson_sessions')->insertGetId(['school_id' => $school->id, 'term_id' => $term->id, 'class_group_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'date' => '2026-10-20', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'status' => 'completed']);
        DB::table('teaching_journals')->insert(['lesson_session_id' => $session, 'topic' => 'Ekosistem']);
        DB::table('report_snapshots')->insert(['school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'term_id' => $term->id, 'class_group_id' => $class->id, 'student_id' => $student->id, 'status' => 'published', 'scheme_snapshot' => '{}', 'grades_snapshot' => '{}', 'attendance_snapshot' => '{}', 'notes_snapshot' => '{}', 'checksum' => str_repeat('a', 64)]);
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '99990000', 'level' => 'SMP']);
        Student::create(['school_id' => $otherSchool->id, 'name' => 'Siswa sekolah lain', 'nis' => '2001', 'status' => 'active']);
        foreach ([
            [$school->id, 'Berlaku', '2026-10-20 09:00:00', '2026-10-30 09:00:00'],
            [$school->id, 'Terjadwal', '2026-10-27 09:00:00', null],
            [$school->id, 'Kedaluwarsa', '2026-10-01 09:00:00', '2026-10-02 09:00:00'],
            [$otherSchool->id, 'Sekolah lain', '2026-10-20 09:00:00', null],
        ] as [$announcementSchoolId, $title, $publishedAt, $expiresAt]) {
            Announcement::create(['school_id' => $announcementSchoolId, 'author_id' => $principal->id, 'title' => $title, 'body' => 'Informasi sekolah.', 'published_at' => $publishedAt, 'expires_at' => $expiresAt]);
        }

        $this->actingAs($principal)->get('/?period=30d')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.principal_overview.summary.students', 1)
            ->where('stats.principal_overview.summary.teachers', 1)
            ->where('stats.principal_overview.summary.attendance_total', 2)
            ->where('stats.principal_overview.summary.attendance_present', 1)
            ->where('stats.principal_overview.summary.attendance_rate', 50)
            ->where('stats.principal_overview.summary.tasks_total', 1)
            ->where('stats.principal_overview.summary.tasks_submitted', 1)
            ->where('stats.principal_overview.summary.exams_live', 1)
            ->where('stats.principal_overview.summary.reports_total', 1)
            ->where('stats.principal_overview.summary.reports_published', 1)
            ->where('stats.role_metrics.active_exams', 1)
            ->has('stats.principal_overview.attendance_trend', 5)
            ->where('stats.principal_overview.activity.admin.missing_homeroom', 2)
            ->has('stats.principal_overview.learning_by_grade', 2)
            ->where('stats.principal_overview.learning_by_grade.0.label', 'Kelas VII')
            ->where('stats.principal_overview.learning_by_grade.1.label', 'Kelas IX')
            ->where('stats.principal_overview.learning_by_grade.1.rate', null)
            ->where('stats.principal_overview.activity.teacher.sessions_total', 1)
            ->where('stats.principal_overview.activity.teacher.journals_completed', 1)
            ->has('stats.latest_announcements', 1)
            ->where('stats.latest_announcements.0.title', 'Berlaku')
        );

        $this->actingAs($principal)->get('/?period=term')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('stats.principal_overview.summary.attendance_total', 3)
            ->where('stats.principal_overview.summary.tasks_total', 2)
            ->has('stats.principal_overview.attendance_trend', 4)
        );
    }

    /** @return array{User, School, Term} */
    private function principalFixture(): array
    {
        $school = School::create(['name' => 'Sekolah Utama', 'npsn' => '88880000', 'level' => 'SMP']);
        $role = Role::create(['name' => RoleEnum::PRINCIPAL->value, 'label' => 'Kepala Sekolah', 'is_system' => true]);
        $principal = User::create(['school_id' => $school->id, 'role_id' => $role->id, 'name' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'kepsek@school.test', 'password' => 'secret123', 'is_active' => true]);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true]);

        return [$principal, $school, $term];
    }
}
