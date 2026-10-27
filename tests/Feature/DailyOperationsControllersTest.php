<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Schedule;
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

    public function test_teacher_journal_index_is_scoped_to_the_logged_in_teacher(): void
    {
        $otherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->teacherUser->role_id,
            'name' => 'Guru Lain',
            'username' => 'guru-lain',
            'email' => 'guru-lain@school.test',
            'password' => bcrypt('GuruPass123!'),
            'is_active' => true,
        ]);
        $otherTeacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $otherUser->id,
            'nip' => '198501012010011002',
            'name' => 'Guru Lain, M.Pd',
            'gender' => 'P',
        ]);
        $otherSession = LessonSession::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $otherTeacher->id,
            'date' => '2025-08-10',
            'start_time' => '09:30',
            'end_time' => '11:00',
            'meeting_number' => 2,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/akademik/jurnal?date=2025-08-10');

        $response->assertInertia(fn ($page) => $page
            ->component('Academic/Journals/Index')
            ->where('agenda_sessions.0.id', $this->session->id)
            ->where('agenda_sessions', fn ($sessions) => collect($sessions)->pluck('id')->doesntContain($otherSession->id))
            ->where('journal_sessions.data', fn ($sessions) => collect($sessions)->pluck('id')->doesntContain($otherSession->id))
        );
    }

    public function test_teacher_cannot_save_journal_for_another_teachers_session(): void
    {
        $otherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $this->teacherUser->role_id,
            'name' => 'Guru Lain',
            'username' => 'guru-lain',
            'email' => 'guru-lain@school.test',
            'password' => bcrypt('GuruPass123!'),
            'is_active' => true,
        ]);
        $otherTeacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $otherUser->id,
            'nip' => '198501012010011002',
            'name' => 'Guru Lain, M.Pd',
            'gender' => 'P',
        ]);
        $otherSession = LessonSession::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $otherTeacher->id,
            'date' => '2025-08-10',
            'start_time' => '09:30',
            'end_time' => '11:00',
            'meeting_number' => 2,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->teacherUser)->post('/akademik/jurnal', [
            'lesson_session_id' => $otherSession->id,
            'topic' => 'Tidak boleh tersimpan',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('teaching_journals', ['lesson_session_id' => $otherSession->id]);
    }

    private function scheduleData(array $overrides = []): array
    {
        return array_replace(['term_id' => $this->term->id, 'class_group_id' => $this->class7A->id,
            'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id, 'day_of_week' => 7,
            'start_time' => '07:30', 'end_time' => '09:00', 'room' => ' Lab IPA '], $overrides);
    }

    public function test_schedule_update_excludes_itself_and_returns_published_slot_to_draft(): void
    {
        $this->actingAs($this->admin)->post('/akademik/jadwal', $this->scheduleData())->assertSessionHasNoErrors();
        $slot = Schedule::firstOrFail();
        $this->assertSame('Lab IPA', $slot->room);
        $this->post('/akademik/jadwal/terbitkan', ['term_id' => $this->term->id])->assertSessionHasNoErrors();
        $this->put('/akademik/jadwal/'.$slot->id, $this->scheduleData(['room' => 'Lab Baru']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', ['id' => $slot->id, 'status' => 'draft', 'room' => 'Lab Baru', 'day_of_week' => 7]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.schedule.updated']);
        $this->get('/akademik/jadwal')->assertInertia(fn ($page) => $page->has('schedules', 1)->where('draft_count', 1)->where('schedules.0.day_of_week', 7));
    }

    public function test_schedule_update_rejects_conflict_and_preserves_existing_slot(): void
    {
        $this->actingAs($this->admin)->post('/akademik/jadwal', $this->scheduleData())->assertSessionHasNoErrors();
        $first = Schedule::firstOrFail();
        $this->post('/akademik/jadwal', $this->scheduleData(['start_time' => '09:00', 'end_time' => '10:00']))->assertSessionHasNoErrors();
        $last = Schedule::latest('id')->firstOrFail();
        $this->put('/akademik/jadwal/'.$last->id, $this->scheduleData(['start_time' => '08:30', 'end_time' => '09:30']))->assertSessionHasErrors('start_time');
        $this->assertSame('09:00', substr($last->fresh()->start_time, 0, 5));
        $this->assertSame('07:30', substr($first->fresh()->start_time, 0, 5));
    }

    public function test_schedule_actions_reject_cross_school_and_read_only_users(): void
    {
        $other = School::create(['npsn' => '87654310', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $year = AcademicYear::create(['school_id' => $other->id, 'name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30']);
        $term = Term::create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'semester_type' => 'odd', 'start_date' => '2025-07-01', 'end_date' => '2025-12-31']);
        $foreign = Schedule::create($this->scheduleData(['school_id' => $other->id, 'term_id' => $term->id]));
        $this->actingAs($this->admin)->post('/akademik/jadwal/terbitkan', ['term_id' => $term->id])->assertSessionHasErrors('term_id');
        $this->post('/akademik/jadwal', $this->scheduleData(['term_id' => $term->id]))->assertSessionHasErrors('term_id');
        $this->put('/akademik/jadwal/'.$foreign->id, $this->scheduleData())->assertForbidden();
        $this->delete('/akademik/jadwal/'.$foreign->id)->assertForbidden();
        $this->get('/akademik/jadwal?term_id='.$term->id)->assertNotFound();
        $own = Schedule::create($this->scheduleData(['school_id' => $this->school->id]));
        $this->actingAs($this->teacherUser)->put('/akademik/jadwal/'.$own->id, $this->scheduleData())->assertForbidden();
        $this->delete('/akademik/jadwal/'.$own->id)->assertForbidden();
        $this->post('/akademik/jadwal/terbitkan', ['term_id' => $this->term->id])->assertForbidden();
        $this->assertSame('draft', $foreign->fresh()->status);
    }

    public function test_schedule_rejects_wrong_year_and_inactive_teacher_or_subject(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $year->id, 'grade_level' => 8, 'name' => '8A']);
        $this->actingAs($this->admin)->post('/akademik/jadwal', $this->scheduleData(['class_group_id' => $class->id]))->assertSessionHasErrors('start_time');
        $this->teacher->update(['is_active' => false]);
        $this->post('/akademik/jadwal', $this->scheduleData())->assertSessionHasErrors('teacher_id');
        $this->teacher->update(['is_active' => true]);
        $this->subject->update(['is_active' => false]);
        $this->post('/akademik/jadwal', $this->scheduleData())->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_schedule_history_blocks_update_and_delete(): void
    {
        $slot = Schedule::create($this->scheduleData(['school_id' => $this->school->id]));
        $this->session->update(['schedule_id' => $slot->id]);
        $this->actingAs($this->admin)->put('/akademik/jadwal/'.$slot->id, $this->scheduleData())->assertSessionHasErrors('start_time');
        $this->delete('/akademik/jadwal/'.$slot->id)->assertSessionHasErrors('schedule');
        $this->assertModelExists($slot);
        $this->assertSame($slot->id, $this->session->fresh()->schedule_id);
        $unused = Schedule::create($this->scheduleData(['school_id' => $this->school->id, 'day_of_week' => 6]));
        $this->delete('/akademik/jadwal/'.$unused->id)->assertSessionHasNoErrors();
        $this->assertModelMissing($unused);
    }

    public function test_schedule_student_sees_only_own_published_active_term_despite_filters(): void
    {
        $published = Schedule::create($this->scheduleData(['school_id' => $this->school->id, 'status' => 'published']));
        Schedule::create($this->scheduleData(['school_id' => $this->school->id, 'day_of_week' => 6]));
        $other = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->term->academic_year_id, 'grade_level' => 7, 'name' => '7B']);
        Schedule::create($this->scheduleData(['school_id' => $this->school->id, 'class_group_id' => $other->id, 'status' => 'published']));
        $this->actingAs($this->studentUser)->get('/akademik/jadwal?term_id=99999&class_group_id='.$other->id)->assertInertia(fn ($page) => $page
            ->has('schedules', 1)->where('schedules.0.id', $published->id)->where('draft_count', 0)->where('can_manage', false)->has('terms', 0));
    }

    public function test_schedule_period_filter_and_publish_revalidation(): void
    {
        $term = Term::create(['academic_year_id' => $this->term->academic_year_id, 'name' => 'Genap', 'semester_type' => 'even', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30']);
        $this->actingAs($this->admin)->post('/akademik/jadwal', $this->scheduleData())->assertSessionHasNoErrors();
        $this->get('/akademik/jadwal?term_id='.$term->id)->assertInertia(fn ($page) => $page->has('schedules', 0)->where('draft_count', 0)->where('active_term.id', $term->id));
        $this->teacher->update(['is_active' => false]);
        $this->post('/akademik/jadwal/terbitkan', ['term_id' => $this->term->id])->assertSessionHasErrors('schedule');
        $this->assertDatabaseHas('schedules', ['status' => 'draft']);
    }

    public function test_schedule_room_conflict_ignores_case_and_surrounding_spaces(): void
    {
        $this->actingAs($this->admin)->post('/akademik/jadwal', $this->scheduleData())->assertSessionHasNoErrors();
        $otherTeacher = Teacher::create(['school_id' => $this->school->id, 'name' => 'Guru Kedua', 'gender' => 'L']);
        $otherClass = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->term->academic_year_id, 'name' => '7B', 'grade_level' => 7]);
        $this->post('/akademik/jadwal', $this->scheduleData(['teacher_id' => $otherTeacher->id, 'class_group_id' => $otherClass->id, 'room' => ' lab ipa ']))->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_schedule_adjacent_slots_accept_stored_seconds(): void
    {
        Schedule::create($this->scheduleData(['school_id' => $this->school->id, 'start_time' => '07:30:00', 'end_time' => '09:00:00']));
        $this->actingAs($this->admin)->post('/akademik/jadwal', $this->scheduleData(['start_time' => '09:00', 'end_time' => '10:00']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_schedule_publish_rejects_conflicting_drafts_without_partial_publication(): void
    {
        Schedule::create($this->scheduleData(['school_id' => $this->school->id]));
        Schedule::create($this->scheduleData(['school_id' => $this->school->id]));
        $this->actingAs($this->admin)->post('/akademik/jadwal/terbitkan', ['term_id' => $this->term->id])->assertSessionHasErrors('schedule');
        $this->assertSame(2, Schedule::where('status', 'draft')->count());
        $this->assertSame(0, Schedule::where('status', 'published')->count());
    }
}
