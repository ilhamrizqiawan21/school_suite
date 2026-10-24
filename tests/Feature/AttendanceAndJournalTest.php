<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Application\Services\AttendanceService;
use App\Modules\Academic\Application\Services\JournalService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
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

class AttendanceAndJournalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacherUser;

    private Teacher $teacher;

    private Term $term;

    private ClassGroup $class7A;

    private Subject $subject;

    private Enrollment $enrollment1;

    private Enrollment $enrollment2;

    private LessonSession $session;

    private AttendanceService $attendanceService;

    private JournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $role = Role::create([
            'name' => 'teacher',
            'label' => 'Guru',
            'is_system' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => 'Pak Guru IPA',
            'username' => 'guruipa',
            'email' => 'guruipa@sekolah.test',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'nip' => '198505052010011003',
            'name' => 'Pak Guru IPA, S.Pd',
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
            'code' => 'IPA',
            'name' => 'Ilmu Pengetahuan Alam',
        ]);

        $student1 = Student::create([
            'school_id' => $this->school->id,
            'nis' => '1001',
            'name' => 'Aditya Pratama',
            'gender' => 'L',
        ]);

        $student2 = Student::create([
            'school_id' => $this->school->id,
            'nis' => '1002',
            'name' => 'Bella Safira',
            'gender' => 'P',
        ]);

        $this->enrollment1 = Enrollment::create([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'student_id' => $student1->id,
            'roll_number' => 1,
        ]);

        $this->enrollment2 = Enrollment::create([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'student_id' => $student2->id,
            'roll_number' => 2,
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

        $this->attendanceService = app(AttendanceService::class);
        $this->journalService = app(JournalService::class);
    }

    public function test_can_record_session_attendance_massively(): void
    {
        $records = [
            ['enrollment_id' => $this->enrollment1->id, 'status' => 'present'],
            ['enrollment_id' => $this->enrollment2->id, 'status' => 'sick', 'notes' => 'Demam flu'],
        ];

        $result = $this->attendanceService->recordSessionAttendance(
            lessonSessionId: $this->session->id,
            records: $records,
            actor: $this->teacherUser
        );

        $this->assertEquals(2, $result['recorded_count']);
        $this->assertEquals(1, $result['summary']['present']);
        $this->assertEquals(1, $result['summary']['sick']);

        $this->assertDatabaseHas('attendance_records', [
            'lesson_session_id' => $this->session->id,
            'enrollment_id' => $this->enrollment1->id,
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'lesson_session_id' => $this->session->id,
            'enrollment_id' => $this->enrollment2->id,
            'status' => 'sick',
            'notes' => 'Demam flu',
        ]);
    }

    public function test_ss_f_005_attendance_correction_records_audit_event(): void
    {
        // 1. Catat presensi awal: Siswa 2 tercatat Alpa (absent)
        $this->attendanceService->recordSessionAttendance(
            lessonSessionId: $this->session->id,
            records: [
                ['enrollment_id' => $this->enrollment2->id, 'status' => 'absent'],
            ],
            actor: $this->teacherUser
        );

        // 2. Koreksi presensi: Siswa 2 ternyata izin membawa surat dokter (sick)
        $updateResult = $this->attendanceService->recordSessionAttendance(
            lessonSessionId: $this->session->id,
            records: [
                ['enrollment_id' => $this->enrollment2->id, 'status' => 'sick', 'notes' => 'Surat dokter susulan'],
            ],
            actor: $this->teacherUser
        );

        $this->assertEquals(1, $updateResult['corrected_count']);

        // Verifikasi audit event koreksi tersimpan
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.attendance.corrected',
            'target_type' => 'attendance_record',
            'actor_id' => $this->teacherUser->id,
        ]);
    }

    public function test_can_save_teaching_journal_and_complete_session(): void
    {
        // Catat absensi dulu
        $this->attendanceService->recordSessionAttendance(
            lessonSessionId: $this->session->id,
            records: [
                ['enrollment_id' => $this->enrollment1->id, 'status' => 'present'],
                ['enrollment_id' => $this->enrollment2->id, 'status' => 'permitted'],
            ],
            actor: $this->teacherUser
        );

        // Simpan jurnal mengajar
        $journal = $this->journalService->saveJournal(
            lessonSessionId: $this->session->id,
            topic: 'Pengukuran Besaran Pokok dan Turunan',
            learningObjectives: 'Siswa mampu menggunakan jangka sorong dan mikrometer sekrup',
            notes: 'Praktikum berjalan tertib dan lancar',
            actor: $this->teacherUser
        );

        $this->assertDatabaseHas('teaching_journals', [
            'id' => $journal->id,
            'lesson_session_id' => $this->session->id,
            'topic' => 'Pengukuran Besaran Pokok dan Turunan',
        ]);

        // Sesi berubah menjadi completed
        $this->assertEquals('completed', $this->session->fresh()->status);

        // Snapshot presensi tersimpan di jurnal
        $this->assertEquals(1, $journal->attendance_summary['present']);
        $this->assertEquals(1, $journal->attendance_summary['permitted']);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.journal.saved',
            'target_type' => 'teaching_journal',
            'target_id' => (string) $journal->id,
        ]);
    }
}
