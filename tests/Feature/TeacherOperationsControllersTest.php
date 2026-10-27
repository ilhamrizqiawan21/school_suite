<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeacherAttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\TeacherLeaveRequest;
use App\Modules\Academic\Infrastructure\Models\TeacherSubstituteAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherOperationsControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacherUser;

    private User $homeroomUser;

    private Teacher $teacher;

    private Teacher $homeroomTeacher;

    private Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Carbon::setTestNow('2026-10-27 10:00:00');
        if (! Route::has('academic.teacher-attendance.index')) {
            require base_path('app/Modules/Academic/Interface/Routes/teacher_operations.php');
        }

        $this->school = School::create(['npsn' => '12345678', 'name' => 'SMP Teladan', 'status' => 'active']);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $teacherRole = Role::create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);
        $homeroomRole = Role::create(['name' => 'homeroom', 'label' => 'Wali Kelas', 'is_system' => true]);
        $this->admin = $this->makeUser($adminRole, 'admin');
        $this->teacherUser = $this->makeUser($teacherRole, 'teacher');
        $this->homeroomUser = $this->makeUser($homeroomRole, 'homeroom');

        $this->teacher = $this->makeTeacher($this->teacherUser, 'Guru Satu');
        $this->homeroomTeacher = $this->makeTeacher($this->homeroomUser, 'Guru Dua');

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-25',
            'is_active' => true,
        ]);
        $this->term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_homeroom_attendance_and_recap_are_self_scoped_and_cannot_edit_another_teacher(): void
    {
        TeacherAttendanceRecord::create(['school_id' => $this->school->id, 'teacher_id' => $this->teacher->id, 'date' => '2026-10-26', 'status' => 'absent']);
        TeacherAttendanceRecord::create(['school_id' => $this->school->id, 'teacher_id' => $this->homeroomTeacher->id, 'date' => '2026-10-26', 'status' => 'sick']);

        $this->actingAs($this->homeroomUser)->get('/akademik/absensi-guru?date=2026-10-26')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Academic/TeacherOperations/Attendance')
                ->has('rows', 1)
                ->where('rows.0.teacher_id', $this->homeroomTeacher->id)
            );
        $this->actingAs($this->homeroomUser)->get('/akademik/absensi-guru/rekap?month=2026-10')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('rows', 1)->where('rows.0.teacher_id', $this->homeroomTeacher->id));
        $this->actingAs($this->homeroomUser)->get('/akademik/absensi-guru/rekap/pdf?month=2026-10')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'actor_id' => $this->homeroomUser->id,
            'event_type' => 'academic.teacher_attendance_recap.exported',
            'target_id' => '2026-10',
        ]);

        $this->actingAs($this->homeroomUser)->post('/akademik/absensi-guru', [
            'date' => '2026-10-26',
            'records' => [['teacher_id' => $this->teacher->id, 'status' => 'present']],
        ])->assertForbidden();
    }

    public function test_leave_requests_enforce_inclusive_sixty_day_limit_and_approval_records_teacher_attendance(): void
    {
        $this->actingAs($this->teacherUser)->get('/akademik/izin-guru?status=all')->assertOk();
        TeacherAttendanceRecord::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-10-28',
            'status' => 'present',
        ]);
        $this->actingAs($this->teacherUser)->post('/akademik/izin-guru', [
            'type' => 'sick',
            'start_date' => '2026-10-27',
            'end_date' => '2026-12-25',
            'reason' => 'Pengobatan dan pemulihan',
        ])->assertRedirect();
        $leave = TeacherLeaveRequest::firstOrFail();

        $this->actingAs($this->admin)->patch('/akademik/izin-guru/'.$leave->id.'/keputusan', [
            'decision' => 'approve',
            'note' => 'Disetujui',
        ])->assertRedirect();
        $this->assertDatabaseHas('teacher_leave_requests', ['id' => $leave->id, 'status' => 'approved']);

        // The approved sickness is added to the separate teacher table; Sundays are skipped.
        $this->assertTrue(TeacherAttendanceRecord::query()->where('teacher_id', $this->teacher->id)->whereDate('date', '2026-10-27')->where('status', 'sick')->exists());
        $this->assertTrue(TeacherAttendanceRecord::query()->where('teacher_id', $this->teacher->id)->whereDate('date', '2026-10-28')->where('status', 'present')->exists());
        $this->assertTrue(TeacherAttendanceRecord::query()->where('teacher_id', $this->teacher->id)->whereDate('date', '2026-10-29')->where('status', 'sick')->exists());
        $this->assertFalse(TeacherAttendanceRecord::query()->where('teacher_id', $this->teacher->id)->whereDate('date', '2026-11-01')->exists());
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->school->id,
            'actor_id' => $this->admin->id,
            'event_type' => 'academic.teacher_leave.reviewed',
            'target_id' => (string) $leave->id,
        ]);

        $otherTeacher = $this->makeTeacher(null, 'Guru Tiga');
        $response = $this->actingAs($this->teacherUser)->from('/akademik/izin-guru')->post('/akademik/izin-guru', [
            'type' => 'leave',
            'start_date' => '2026-10-27',
            'end_date' => '2026-12-26',
            'reason' => 'Permintaan terlalu panjang',
        ]);
        $response->assertRedirect();
        $this->assertSame(1, TeacherLeaveRequest::count(), 'An invalid 61-calendar-day request must not be stored.');
        $this->assertDatabaseMissing('teacher_leave_requests', ['teacher_id' => $otherTeacher->id]);
    }

    public function test_substitute_candidates_exclude_teachers_with_overlapping_published_schedule(): void
    {
        $substituteUser = $this->makeUser(Role::where('name', 'teacher')->firstOrFail(), 'substitute');
        $substitute = $this->makeTeacher($substituteUser, 'Guru Pengganti');
        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->term->academic_year_id,
            'grade_level' => 7,
            'name' => 'VII-A',
            'capacity' => 32,
        ]);
        $subject = Subject::create(['school_id' => $this->school->id, 'code' => 'MTK', 'name' => 'Matematika']);
        $absentSlot = $this->makeSchedule($this->teacher, $class, $subject, '08:00:00', '09:00:00');
        $this->makeSchedule($substitute, $class, $subject, '08:30:00', '09:30:00');
        $attendance = TeacherAttendanceRecord::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-10-26',
            'status' => 'absent',
        ]);

        $this->actingAs($this->admin)->get('/akademik/absensi-guru/'.$attendance->id.'/pengganti')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Academic/TeacherOperations/Substitutes')
                ->has('slots', 1)
                ->where('slots.0.id', $absentSlot->id)
                ->has('slots.0.candidates', 1)
                ->where('slots.0.candidates.0.id', $this->homeroomTeacher->id)
            );

        $response = $this->actingAs($this->admin)->from('/akademik/absensi-guru/'.$attendance->id.'/pengganti')->post('/akademik/absensi-guru/'.$attendance->id.'/pengganti', [
            'schedule_id' => $absentSlot->id,
            'substitute_teacher_id' => $substitute->id,
        ]);
        $response->assertRedirect();
        $this->assertSame(0, TeacherSubstituteAssignment::count(), 'A teacher with an overlapping schedule must not be assigned.');
    }

    public function test_admin_cannot_review_a_leave_request_from_another_school(): void
    {
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'SMP Lain', 'status' => 'active']);
        $foreignTeacher = Teacher::create([
            'school_id' => $otherSchool->id,
            'name' => 'Guru Luar',
            'gender' => 'L',
            'is_active' => true,
        ]);
        $foreignLeave = TeacherLeaveRequest::create([
            'school_id' => $otherSchool->id,
            'teacher_id' => $foreignTeacher->id,
            'type' => 'permission',
            'start_date' => '2026-10-27',
            'end_date' => '2026-10-27',
            'reason' => 'Di luar sekolah admin',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->patch('/akademik/izin-guru/'.$foreignLeave->id.'/keputusan', [
            'decision' => 'approve',
        ])->assertNotFound();
        $this->assertDatabaseHas('teacher_leave_requests', ['id' => $foreignLeave->id, 'status' => 'pending']);
    }

    private function makeUser(Role $role, string $username): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => ucfirst($username),
            'username' => $username,
            'email' => $username.'@school.test',
            'password' => bcrypt('TestPass123!'),
            'is_active' => true,
        ]);
    }

    private function makeTeacher(?User $user, string $name): Teacher
    {
        return Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $user?->id,
            'nip' => 'NIP-'.$name,
            'name' => $name,
            'gender' => 'L',
            'is_active' => true,
        ]);
    }

    private function makeSchedule(Teacher $teacher, ClassGroup $class, Subject $subject, string $start, string $end): Schedule
    {
        return Schedule::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'class_group_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'published',
        ]);
    }
}
