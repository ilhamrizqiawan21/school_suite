<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyOperationsControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacherUser;

    private User $studentUser;

    private Teacher $teacher;

    private Term $term;

    private ClassGroup $class7A;

    private Subject $subject;

    private Enrollment $enrollment;

    private LessonSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $adminRole = Role::create([
            'name' => 'admin',
            'label' => 'Administrator',
            'is_system' => true,
        ]);

        $teacherRole = Role::create([
            'name' => 'teacher',
            'label' => 'Guru',
            'is_system' => true,
        ]);

        $studentRole = Role::create([
            'name' => 'student',
            'label' => 'Siswa',
            'is_system' => true,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Admin Sekolah',
            'username' => 'admin',
            'email' => 'admin@school.test',
            'password' => bcrypt('AdminPass123!'),
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Guru Matematika',
            'username' => 'gurumtk',
            'email' => 'gurumtk@school.test',
            'password' => bcrypt('GuruPass123!'),
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Siswa Belajar',
            'username' => 'siswa',
            'email' => 'siswa@school.test',
            'password' => bcrypt('SiswaPass123!'),
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'nip' => '198501012010011001',
            'name' => 'Guru Matematika, M.Pd',
            'gender' => 'L',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $this->class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MTK',
            'name' => 'Matematika',
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->studentUser->id,
            'nis' => '1001',
            'name' => 'Siswa Belajar',
            'gender' => 'L',
        ]);

        $this->enrollment = Enrollment::create([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'student_id' => $student->id,
            'roll_number' => 1,
        ]);

        $this->session = LessonSession::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2025-08-10',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'meeting_number' => 1,
            'status' => 'scheduled',
        ]);
    }

    public function test_can_view_schedules_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/akademik/jadwal');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Academic/Schedules/Index'));
    }

    public function test_admin_can_store_and_publish_schedule(): void
    {
        $response = $this->actingAs($this->admin)->post('/akademik/jadwal', [
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 7A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'status' => 'draft',
        ]);

        // Publish
        $publishResponse = $this->actingAs($this->admin)->post('/akademik/jadwal/terbitkan', [
            'term_id' => $this->term->id,
        ]);

        $publishResponse->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'term_id' => $this->term->id,
            'status' => 'published',
        ]);
    }

    public function test_student_cannot_create_schedule(): void
    {
        $response = $this->actingAs($this->studentUser)->post('/akademik/jadwal', [
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(403);
    }

    public function test_teacher_can_record_attendance_and_correct(): void
    {
        // 1. Catat presensi sesi
        $response = $this->actingAs($this->teacherUser)->post('/akademik/presensi', [
            'session_id' => $this->session->id,
            'date' => '2025-08-10',
            'records' => [
                [
                    'enrollment_id' => $this->enrollment->id,
                    'status' => 'present',
                ],
            ],
        ]);

        $response->assertRedirect();
        $record = AttendanceRecord::where('lesson_session_id', $this->session->id)->firstOrFail();
        $this->assertEquals('present', $record->status);

        // 2. Koreksi presensi
        $correctResponse = $this->actingAs($this->teacherUser)->post("/akademik/presensi/{$record->id}/koreksi", [
            'status' => 'sick',
            'reason' => 'Siswa pulang lebih awal sakit demam',
        ]);

        $correctResponse->assertRedirect();
        $this->assertEquals('sick', $record->fresh()->status);
    }

    public function test_teacher_can_save_journal(): void
    {
        $response = $this->actingAs($this->teacherUser)->post('/akademik/jurnal', [
            'lesson_session_id' => $this->session->id,
            'topic' => 'Aljabar dan Persamaan Linier',
            'learning_objectives' => 'Menyelesaikan persamaan satu variabel',
            'notes' => 'Siswa antusias mengerjakan soal di papan tulis',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teaching_journals', [
            'lesson_session_id' => $this->session->id,
            'topic' => 'Aljabar dan Persamaan Linier',
        ]);
        $this->assertEquals('completed', $this->session->fresh()->status);
    }
}
