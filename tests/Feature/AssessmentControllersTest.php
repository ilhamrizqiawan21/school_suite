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
use App\Modules\Assessment\Application\Services\QuestionSpreadsheetService;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\Exam;
use App\Modules\Assessment\Infrastructure\Models\ExamAnswer;
use App\Modules\Assessment\Infrastructure\Models\ExamAttempt;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionVersion;
use App\Modules\Assessment\Infrastructure\Models\QuestionPack;
use App\Modules\Assessment\Infrastructure\Models\QuestionPackItem;
use App\Modules\Assessment\Infrastructure\Models\QuestionShare;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Assessment\Infrastructure\Models\StudentAttitude;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssessmentControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $adminUser;

    private User $teacherUser;

    private User $homeroomUser;

    private User $studentUser;

    private User $otherStudentUser;

    private AcademicYear $academicYear;

    private Term $term;

    private ClassGroup $classGroup;

    private Student $student;

    private Student $otherStudent;

    private Enrollment $enrollment;

    private Enrollment $otherEnrollment;

    private AssessmentScheme $scheme;

    private AssessmentComponent $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Bintang Juara',
            'status' => 'active',
        ]);

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $teacherRole = Role::create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);
        $homeroomRole = Role::create(['name' => 'homeroom', 'label' => 'Wali Kelas', 'is_system' => true]);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Siswa', 'is_system' => true]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-20',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ganjil 2026/2027',
            'semester' => 1,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $this->classGroup = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-A',
            'grade_level' => 7,
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198501012010011001',
            'name' => 'Budi Santoso, S.Pd.',
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'nis' => '1001',
            'nisn' => '0011223344',
            'name' => 'Ahmad Dahlan',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->otherStudent = Student::create([
            'school_id' => $this->school->id,
            'nis' => '1002',
            'nisn' => '0011223355',
            'name' => 'Siti Fatimah',
            'gender' => 'P',
            'status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'term_id' => $this->term->id,
            'student_id' => $this->student->id,
            'class_group_id' => $this->classGroup->id,
            'status' => 'active',
        ]);

        $this->otherEnrollment = Enrollment::create([
            'term_id' => $this->term->id,
            'student_id' => $this->otherStudent->id,
            'class_group_id' => $this->classGroup->id,
            'status' => 'active',
        ]);

        // Pengguna
        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Super Admin',
            'username' => 'admin_test',
            'email' => 'admin@sekolah.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Guru Matematika',
            'username' => 'teacher_test',
            'email' => 'teacher@sekolah.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->homeroomUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $homeroomRole->id,
            'name' => 'Wali Kelas VII-A',
            'username' => 'homeroom_test',
            'email' => 'homeroom@sekolah.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Ahmad Dahlan',
            'username' => 'ahmad_test',
            'email' => 'ahmad@sekolah.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->otherStudentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Siti Fatimah',
            'username' => 'siti_test',
            'email' => 'siti@sekolah.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $teacher->update(['user_id' => $this->teacherUser->id]);
        $homeroomTeacher = Teacher::create(['school_id' => $this->school->id, 'user_id' => $this->homeroomUser->id, 'name' => 'Wali Kelas', 'gender' => 'L', 'is_active' => true]);
        $this->classGroup->update(['homeroom_teacher_id' => $homeroomTeacher->id]);
        $this->student->update(['user_id' => $this->studentUser->id]);
        $this->otherStudent->update(['user_id' => $this->otherStudentUser->id]);

        // Skema dan Komponen
        $this->scheme = AssessmentScheme::create([
            'school_id' => $this->school->id,
            'name' => 'Kurikulum Merdeka 2026',
            'code' => 'KM-2026',
            'version' => 1,
            'is_active' => true,
            'rounding_rule' => RoundingRuleEnum::HALF_UP,
            'decimal_places' => 2,
            'passing_grade_default' => 75.0,
        ]);

        $this->component = AssessmentComponent::create([
            'scheme_id' => $this->scheme->id,
            'name' => 'Formatif 1',
            'code' => 'F1',
            'weight_percentage' => 40.0,
            'passing_grade' => 75.0,
            'sequence' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT-01',
            'name' => 'Matematika',
            'is_active' => true,
        ]);

        TeachingAssignment::create([
            'term_id' => $this->term->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_group_id' => $this->classGroup->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('assessment.scores.index'));
        $response->assertRedirect('/login');

        $responseScheme = $this->get(route('assessment.schemes.index'));
        $responseScheme->assertRedirect('/login');

        $responseReport = $this->get(route('assessment.reports.index'));
        $responseReport->assertRedirect('/login');
    }

    public function test_active_question_pack_creates_exam_with_immutable_version_and_pack_points(): void
    {
        $assignmentId = TeachingAssignment::query()->value('id');
        $question = ExamQuestion::create([
            'school_id' => $this->school->id,
            'owner_id' => $this->adminUser->id,
            'type' => 'multiple_choice',
            'prompt' => 'Prompt saat ini',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'points' => 1,
            'is_active' => true,
            'current_version' => 2,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id,
            'version_number' => 1,
            'type' => 'multiple_choice',
            'prompt' => 'Prompt snapshot paket',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'points' => 4,
            'created_by' => $this->adminUser->id,
        ]);
        $currentVersion = ExamQuestionVersion::create([
            'exam_question_id' => $question->id,
            'version_number' => 2,
            'type' => 'multiple_choice',
            'prompt' => 'Prompt saat ini',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'points' => 1,
            'created_by' => $this->adminUser->id,
        ]);
        $pack = QuestionPack::create([
            'school_id' => $this->school->id,
            'created_by' => $this->adminUser->id,
            'name' => 'Paket Uji',
            'jenjang' => 'SMP',
            'curriculum' => 'Merdeka',
            'duration_minutes' => 45,
            'randomize_questions' => false,
            'randomize_options' => true,
            'status' => 'active',
        ]);
        QuestionPackItem::create([
            'question_pack_id' => $pack->id,
            'exam_question_id' => $question->id,
            'question_version_id' => $version->id,
            'sequence' => 1,
            'points' => 7,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('exams.store'), [
            'teaching_assignment_id' => $assignmentId,
            'title' => 'Ujian dari paket',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(2)->toDateTimeString(),
            'duration_minutes' => 45,
            'randomize_questions' => false,
            'randomize_options' => true,
            'max_violations' => 3,
            'question_pack_id' => $pack->id,
        ]);

        $response->assertRedirect('/ujian');
        $exam = Exam::query()->where('title', 'Ujian dari paket')->firstOrFail();
        $this->assertSame($pack->id, $exam->question_pack_id);
        $this->assertDatabaseHas('exam_question_items', [
            'exam_id' => $exam->id,
            'exam_question_id' => $question->id,
            'question_version_id' => $version->id,
            'sequence' => 1,
            'points' => 7,
        ]);

        $manualResponse = $this->actingAs($this->adminUser)->post(route('exams.store'), [
            'teaching_assignment_id' => $assignmentId,
            'title' => 'Ujian soal manual',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(2)->toDateTimeString(),
            'duration_minutes' => 60,
            'randomize_questions' => false,
            'randomize_options' => false,
            'max_violations' => 3,
            'question_ids' => [$question->id],
        ]);
        $manualResponse->assertRedirect('/ujian');
        $manualExam = Exam::query()->where('title', 'Ujian soal manual')->firstOrFail();
        $this->assertNull($manualExam->question_pack_id);
        $this->assertDatabaseHas('exam_question_items', [
            'exam_id' => $manualExam->id,
            'exam_question_id' => $question->id,
            'question_version_id' => $currentVersion->id,
            'points' => 1,
        ]);
    }

    public function test_exam_monitor_exposes_roster_and_supports_supervisor_actions(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Soal monitor', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 2, 'is_active' => true, 'current_version' => 1,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'Monitor CBT', 'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2), 'duration_minutes' => 60, 'max_violations' => 3, 'status' => 'published',
        ]);
        $exam->items()->create(['exam_question_id' => $question->id, 'sequence' => 1, 'points' => 2]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'status' => 'locked',
            'started_at' => now()->subMinutes(20), 'expires_at' => now()->addMinutes(40),
            'last_heartbeat_at' => now()->subMinute(), 'violation_count' => 3,
            'question_order' => [$question->id], 'random_seed' => 42,
        ]);

        $this->actingAs($this->adminUser)->get(route('exams.monitor', $exam))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assessment/Exams/Monitor')
                ->where('snapshot.summary.total', 2)
                ->where('snapshot.summary.locked', 1)
                ->where('snapshot.summary.not_started', 1)
                ->has('snapshot.students', 2));

        $this->actingAs($this->adminUser)->post(route('exams.unlock', $attempt))->assertStatus(422);
        $this->assertSame('locked', $attempt->fresh()->status);

        $oldExpiry = $attempt->fresh()->expires_at;
        $this->actingAs($this->adminUser)->post(route('exams.extend', $attempt), ['minutes' => 10])->assertStatus(422);
        $this->assertTrue($attempt->fresh()->expires_at->equalTo($oldExpiry));

        $this->actingAs($this->adminUser)->post(route('exams.force-finish', $attempt))->assertRedirect();
        $this->assertSame('cheated', $attempt->fresh()->status);
        $this->assertSame(0.0, (float) $attempt->fresh()->score);
    }

    public function test_three_violations_terminate_attempt_zero_score_and_notify_staff(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Soal anti curang', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 10, 'is_active' => true, 'current_version' => 1,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'CBT anti curang', 'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(), 'duration_minutes' => 30, 'max_violations' => 3, 'status' => 'published',
        ]);
        $exam->items()->create(['exam_question_id' => $question->id, 'sequence' => 1, 'points' => 10]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'status' => 'in_progress',
            'started_at' => now(), 'expires_at' => now()->addMinutes(30), 'last_heartbeat_at' => now(),
            'question_order' => [$question->id], 'random_seed' => 7,
        ]);

        foreach (['tab_hidden', 'copy_attempt', 'paste_attempt'] as $type) {
            $this->actingAs($this->studentUser)->post(route('exams.violation', $attempt), ['type' => $type])->assertRedirect();
        }

        $attempt->refresh();
        $this->assertSame('cheated', $attempt->status);
        $this->assertSame(3, $attempt->violation_count);
        $this->assertSame(0.0, (float) $attempt->score);
        $this->assertSame(1, $attempt->events()->where('type', 'cheating_terminated')->count());
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->adminUser->id, 'title' => 'Ujian dihentikan karena pelanggaran']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->teacherUser->id, 'title' => 'Ujian dihentikan karena pelanggaran']);
        $this->actingAs($this->adminUser)->post(route('exams.force-finish', $attempt))->assertStatus(422);
    }

    public function test_randomized_exam_options_are_stable_for_the_same_attempt(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Pilihan stabil', 'options' => ['A', 'B', 'C'],
            'correct_answer' => 'A', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'Urutan stabil', 'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(), 'duration_minutes' => 30, 'randomize_options' => true, 'status' => 'published',
        ]);
        $exam->items()->create(['exam_question_id' => $question->id, 'sequence' => 1, 'points' => 1]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'status' => 'in_progress',
            'started_at' => now(), 'expires_at' => now()->addMinutes(30), 'question_order' => [$question->id], 'random_seed' => 4242,
        ]);

        $first = null;
        $this->actingAs($this->studentUser)->get(route('exams.attempt', $attempt))->assertInertia(fn (Assert $page) => $page
            ->where('questions.0.options', function ($options) use (&$first): bool {
                $first = $options;

                return true;
            }));
        $second = null;
        $this->actingAs($this->studentUser)->get(route('exams.attempt', $attempt))->assertInertia(fn (Assert $page) => $page
            ->where('questions.0.options', function ($options) use (&$second): bool {
                $second = $options;

                return true;
            }));

        $this->assertSame($first, $second);
    }

    public function test_manager_can_create_question_pack_with_snapshot_items(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Jelaskan pecahan', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'is_active' => true, 'current_version' => 1,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1,
            'type' => 'essay', 'prompt' => 'Jelaskan pecahan', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'created_by' => $this->teacherUser->id,
        ]);

        $this->actingAs($this->teacherUser)->post(route('assessment.question-packs.store'), [
            'name' => 'Paket pecahan', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
            'randomize_questions' => false, 'randomize_options' => false, 'status' => 'active',
            'items' => [['question_id' => $question->id, 'points' => 5]],
        ])->assertRedirect();

        $pack = QuestionPack::query()->where('name', 'Paket pecahan')->firstOrFail();
        $this->assertDatabaseHas('question_pack_items', [
            'question_pack_id' => $pack->id, 'exam_question_id' => $question->id,
            'question_version_id' => $version->id, 'sequence' => 1, 'points' => 5,
        ]);
    }

    public function test_question_pack_reports_incompatible_questions_as_form_errors(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Soal tingkat SMA', 'points' => 2,
            'jenjang' => 'SMA', 'curriculum' => 'merdeka', 'is_active' => true, 'current_version' => 1,
        ]);

        $this->actingAs($this->teacherUser)->from(route('assessment.question-packs.index'))
            ->post(route('assessment.question-packs.store'), [
                'name' => 'Paket salah jenjang', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
                'randomize_questions' => false, 'randomize_options' => false, 'status' => 'draft',
                'items' => [['question_id' => $question->id, 'points' => 2]],
            ])->assertRedirect(route('assessment.question-packs.index'))->assertSessionHasErrors(['items']);

        $this->assertDatabaseMissing('question_packs', ['name' => 'Paket salah jenjang']);
    }

    public function test_question_pack_reports_missing_question_version_as_form_error(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Soal tanpa versi', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'is_active' => true, 'current_version' => 1,
        ]);

        $this->actingAs($this->teacherUser)->from(route('assessment.question-packs.index'))
            ->post(route('assessment.question-packs.store'), [
                'name' => 'Paket tanpa versi', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
                'randomize_questions' => false, 'randomize_options' => false, 'status' => 'draft',
                'items' => [['question_id' => $question->id, 'points' => 2]],
            ])->assertRedirect(route('assessment.question-packs.index'))->assertSessionHasErrors(['items']);

        $this->assertDatabaseMissing('question_packs', ['name' => 'Paket tanpa versi']);
    }

    public function test_teacher_can_create_exam_from_pack_containing_copy_shared_question(): void
    {
        Teacher::query()->firstOrFail()->update(['user_id' => $this->teacherUser->id]);
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Soal berbagi', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 1, 'jenjang' => 'SMP',
            'curriculum' => 'merdeka', 'is_active' => true, 'current_version' => 1,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1,
            'type' => 'multiple_choice', 'prompt' => 'Soal berbagi', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 1, 'jenjang' => 'SMP',
            'curriculum' => 'merdeka', 'created_by' => $this->adminUser->id,
        ]);
        QuestionShare::create([
            'school_id' => $this->school->id, 'sender_id' => $this->adminUser->id,
            'recipient_id' => $this->teacherUser->id, 'resource_type' => 'question',
            'resource_id' => $question->id, 'permission' => 'copy',
            'status' => 'accepted', 'active_key' => 'active',
        ]);

        $this->actingAs($this->teacherUser)->post(route('assessment.question-packs.store'), [
            'name' => 'Paket soal berbagi', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
            'randomize_questions' => false, 'randomize_options' => false, 'status' => 'active',
            'items' => [['question_id' => $question->id, 'points' => 4]],
        ])->assertRedirect();
        $pack = QuestionPack::query()->where('name', 'Paket soal berbagi')->firstOrFail();

        $this->actingAs($this->teacherUser)->post(route('exams.store'), [
            'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'title' => 'Ujian soal berbagi', 'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(2)->toDateTimeString(), 'duration_minutes' => 45,
            'randomize_questions' => false, 'randomize_options' => false,
            'max_violations' => 3, 'question_pack_id' => $pack->id,
        ])->assertRedirect('/ujian');

        $exam = Exam::query()->where('title', 'Ujian soal berbagi')->firstOrFail();
        $this->assertDatabaseHas('exam_question_items', [
            'exam_id' => $exam->id, 'exam_question_id' => $question->id,
            'question_version_id' => $version->id, 'points' => 4,
        ]);
    }

    public function test_edit_shared_question_is_available_and_usable_in_question_pack(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Soal kolaborasi untuk paket', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'is_active' => true, 'current_version' => 1,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1,
            'type' => 'essay', 'prompt' => 'Soal kolaborasi untuk paket', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'created_by' => $this->teacherUser->id,
        ]);
        QuestionShare::create([
            'school_id' => $this->school->id, 'sender_id' => $this->teacherUser->id,
            'recipient_id' => $this->homeroomUser->id, 'resource_type' => 'question',
            'resource_id' => $question->id, 'permission' => 'edit',
            'status' => 'accepted', 'active_key' => 'active',
        ]);

        $this->actingAs($this->homeroomUser)->get(route('assessment.question-packs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('question_options', fn ($options) => collect($options)->contains(fn ($option) => (int) $option['id'] === $question->id)));

        $this->actingAs($this->homeroomUser)->post(route('assessment.question-packs.store'), [
            'name' => 'Paket kolaborasi edit', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
            'randomize_questions' => false, 'randomize_options' => false, 'status' => 'draft',
            'items' => [['question_id' => $question->id, 'points' => 3]],
        ])->assertRedirect();

        $pack = QuestionPack::query()->where('name', 'Paket kolaborasi edit')->firstOrFail();
        $this->assertDatabaseHas('question_pack_items', [
            'question_pack_id' => $pack->id, 'exam_question_id' => $question->id,
            'question_version_id' => $version->id, 'points' => 3,
        ]);
    }

    public function test_student_exam_page_does_not_receive_bank_questions_or_packs(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('exams.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Exams/Index')
            ->has('questions', 0)
            ->has('packs', 0)
        );
    }

    public function test_question_spreadsheet_preview_confirm_imports_all_four_types_and_export_keeps_formula_text_inert(): void
    {
        $service = new QuestionSpreadsheetService;
        $book = $service->template();
        $data = $book->getSheetByName('Data');
        $examples = $book->getSheetByName('Contoh')->rangeToArray('A2:P5', null, false, false);
        foreach ($examples as $index => $row) {
            $row[3] = '';
            $data->fromArray($row, null, 'A'.($index + 2));
        }
        $data->setCellValueExplicit('E2', '=HYPERLINK("https://invalid.example","klik")', DataType::TYPE_STRING);
        $path = tempnam(sys_get_temp_dir(), 'question-import-').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        $upload = new UploadedFile($path, 'questions.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);

        $preview = $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.preview'), ['file' => $upload]);
        $token = '';
        $preview->assertOk()->assertInertia(function (Assert $page) use (&$token) {
            $page->component('Assessment/QuestionBank/ImportPreview')->where('valid_count', 4)->where('invalid_count', 0);
            $token = $page->toArray()['props']['token'];
        });

        $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.confirm', $token))->assertRedirect(route('assessment.questions.index'));
        $this->assertSame(4, ExamQuestion::query()->where('school_id', $this->school->id)->count());
        $this->assertSame(['essay', 'matching', 'multiple_choice', 'true_false'], ExamQuestion::query()->where('school_id', $this->school->id)->orderBy('type')->pluck('type')->all());

        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'role_id' => $this->adminUser->role_id,
            'name' => 'Admin Sekolah Lain',
            'username' => 'other_admin',
            'email' => 'other-admin@sekolah.id',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        ExamQuestion::create([
            'school_id' => $otherSchool->id,
            'owner_id' => $otherAdmin->id,
            'type' => 'essay',
            'prompt' => 'SOAL RAHASIA SEKOLAH LAIN',
            'points' => 1,
            'is_active' => true,
            'current_version' => 1,
        ]);

        $export = $this->actingAs($this->teacherUser)->get(route('assessment.questions.export'));
        $export->assertOk()->assertDownload('bank-soal.xlsx');
        $exportPath = tempnam(sys_get_temp_dir(), 'question-export-').'.xlsx';
        file_put_contents($exportPath, $export->streamedContent());
        $exportBook = (new Xlsx)->load($exportPath);
        $exportSheet = $exportBook->getSheetByName('Data');
        $this->assertSame(5, $exportSheet->getHighestRow());
        $this->assertNotContains('SOAL RAHASIA SEKOLAH LAIN', array_column($exportSheet->rangeToArray('E2:E5', null, false, false), 0));
        $this->assertSame(DataType::TYPE_STRING, $exportSheet->getCell('E2')->getDataType());
        $this->assertSame('=HYPERLINK("https://invalid.example","klik")', $exportSheet->getCell('E2')->getValue());
        $exportBook->disconnectWorksheets();
        @unlink($path);
        @unlink($exportPath);
    }

    public function test_import_preview_reports_invalid_rows_and_refuses_partial_commit(): void
    {
        $service = new QuestionSpreadsheetService;
        $book = $service->template();
        $data = $book->getSheetByName('Data');
        $row = $book->getSheetByName('Contoh')->rangeToArray('A2:P2', null, false, false)[0];
        $row[3] = '';
        $row[4] = '';
        $data->fromArray($row, null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'question-import-invalid-').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        $upload = new UploadedFile($path, 'invalid.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UPLOAD_ERR_OK, true);

        $preview = $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.preview'), ['file' => $upload]);
        $token = '';
        $preview->assertOk()->assertInertia(function (Assert $page) use (&$token) {
            $page->component('Assessment/QuestionBank/ImportPreview')->where('valid_count', 0)->where('invalid_count', 1)->has('rows.0.errors');
            $token = $page->toArray()['props']['token'];
        });
        $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.confirm', $token))->assertUnprocessable();
        $this->assertSame(0, ExamQuestion::query()->where('school_id', $this->school->id)->count());
        @unlink($path);
    }

    public function test_question_import_confirmation_is_exclusive_per_preview_token(): void
    {
        $token = (string) Str::uuid();
        $cacheKey = 'assessment:question-import:'.$token;
        Cache::put($cacheKey, [
            'user_id' => $this->teacherUser->id,
            'school_id' => $this->school->id,
            'errors' => false,
            'rows' => [[
                'type' => 'essay', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
                'prompt' => 'Soal sekali impor', 'points' => 2,
            ]],
        ], now()->addMinutes(15));

        $lock = Cache::lock($cacheKey.':confirm', 60);
        $this->assertTrue($lock->get());
        try {
            $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.confirm', $token))
                ->assertSessionHasErrors('file');
            $this->assertDatabaseCount('exam_questions', 0);
        } finally {
            $lock->release();
        }

        $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.confirm', $token))
            ->assertRedirect(route('assessment.questions.index'));
        $this->assertDatabaseCount('exam_questions', 1);
        $this->actingAs($this->teacherUser)->post(route('assessment.questions.import.confirm', $token))
            ->assertNotFound();
    }

    public function test_exam_result_csv_keeps_formula_like_student_names_as_text(): void
    {
        $unsafeName = '=HYPERLINK("https://invalid.example","klik")';
        $this->student->update(['name' => $unsafeName]);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'Ekspor aman',
            'starts_at' => now(), 'ends_at' => now()->addDay(), 'duration_minutes' => 60,
            'status' => 'results_published',
        ]);
        ExamAttempt::create([
            'exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id,
            'status' => 'graded', 'started_at' => now()->subHour(),
            'expires_at' => now(), 'score' => 80, 'question_order' => [],
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('exams.results.export', $exam));
        $response->assertOk();
        $row = str_getcsv(explode("\n", trim($response->streamedContent()))[1]);
        $this->assertSame("'".$unsafeName, $row[1]);
    }

    public function test_students_cannot_download_or_export_question_spreadsheets(): void
    {
        $this->actingAs($this->studentUser)->get(route('assessment.questions.template'))->assertForbidden();
        $this->actingAs($this->studentUser)->get(route('assessment.questions.export'))->assertForbidden();
    }

    public function test_student_is_forbidden_from_inputting_scores(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('assessment.scores.index'));

        $response->assertForbidden();

        $postResponse = $this->actingAs($this->studentUser)
            ->post(route('assessment.scores.store'), [
                'component_id' => $this->component->id,
                'scores' => [
                    ['enrollment_id' => $this->enrollment->id, 'raw_score' => 90],
                ],
            ]);

        $postResponse->assertForbidden();
    }

    public function test_teacher_can_view_input_grid_and_store_batch_scores(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->get(route('assessment.scores.index', [
                'class_group_id' => $this->classGroup->id,
                'component_id' => $this->component->id,
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Scores/InputGrid')
            ->has('students', 2)
            ->where('students.0.name', 'Ahmad Dahlan')
        );

        // Simpan nilai batch dengan remedial
        $storeResponse = $this->actingAs($this->teacherUser)
            ->post(route('assessment.scores.store'), [
                'component_id' => $this->component->id,
                'scores' => [
                    [
                        'enrollment_id' => $this->enrollment->id,
                        'raw_score' => 60,
                        'remedial_score' => 80, // KKTP 75 -> nilai efektif max 75
                        'notes' => 'Remedial berhasil tuntas',
                    ],
                    [
                        'enrollment_id' => $this->otherEnrollment->id,
                        'raw_score' => 90,
                        'remedial_score' => null,
                        'notes' => 'Sangat memuaskan',
                    ],
                ],
            ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('message');

        // Pastikan tersimpan di DB
        $score = Score::where('enrollment_id', $this->enrollment->id)
            ->where('assessment_component_id', $this->component->id)
            ->firstOrFail();

        $this->assertEquals(60.0, $score->raw_score);
        $this->assertEquals(80.0, $score->remedial_score);
        $this->assertEquals(75.0, $score->final_score); // min(80, 75)

        $this->assertDatabaseHas('remedial_records', [
            'score_id' => $score->id,
            'initial_score' => 60,
            'remedial_score' => 80,
            'passing_grade' => 75.0,
            'adjusted_score' => 75.0,
        ]);
    }

    public function test_teacher_can_save_indicator_based_attitudes_while_preserving_legacy_report_fields(): void
    {
        StudentAttitude::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'enrollment_id' => $this->enrollment->id,
            'type' => 'spiritual',
            'predicate' => 'good',
            'description' => 'Catatan lama spiritual',
            'recorded_by' => $this->teacherUser->id,
        ]);
        StudentAttitude::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'enrollment_id' => $this->enrollment->id,
            'type' => 'social',
            'predicate' => 'fair',
            'description' => 'Catatan lama sosial',
            'recorded_by' => $this->teacherUser->id,
        ]);

        $this->actingAs($this->teacherUser)
            ->get(route('assessment.attitudes.index', ['class_group_id' => $this->classGroup->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assessment/Attitudes/Index')
                ->where('students.0.spiritual_scores.taqwa', null)
                ->where('students.0.spiritual_description', 'Catatan lama spiritual')
            );

        $response = $this->actingAs($this->teacherUser)->post(route('assessment.attitudes.store'), [
            'records' => [[
                'enrollment_id' => $this->enrollment->id,
                'spiritual_scores' => ['taqwa' => 5, 'kejujuran' => 5, 'disiplin' => 4, 'sabar' => 4, 'syukur' => 4, 'tawadhu' => 5],
                'social_scores' => ['empati' => 3, 'kerjasama' => 4, 'toleransi' => 3, 'percaya_diri' => 4, 'komunikasi' => 3],
            ]],
        ]);

        $response->assertRedirect();
        $spiritual = StudentAttitude::query()->where('enrollment_id', $this->enrollment->id)->where('type', 'spiritual')->firstOrFail();
        $social = StudentAttitude::query()->where('enrollment_id', $this->enrollment->id)->where('type', 'social')->firstOrFail();

        $this->assertSame([5, 5, 4, 4, 4, 5], array_values($spiritual->spiritual_scores));
        $this->assertSame('excellent', $spiritual->predicate);
        $this->assertSame('Catatan lama spiritual', $spiritual->description);
        $this->assertSame([3, 4, 3, 4, 3], array_values($social->social_scores));
        $this->assertSame('fair', $social->predicate);
        $this->assertSame('Catatan lama sosial', $social->description);
    }

    public function test_attitude_indicator_scores_are_limited_to_one_through_five(): void
    {
        $response = $this->actingAs($this->teacherUser)->from(route('assessment.attitudes.index'))->post(route('assessment.attitudes.store'), [
            'records' => [[
                'enrollment_id' => $this->enrollment->id,
                'spiritual_scores' => ['taqwa' => 6, 'kejujuran' => null, 'disiplin' => null, 'sabar' => null, 'syukur' => null, 'tawadhu' => null],
                'social_scores' => ['empati' => 1, 'kerjasama' => null, 'toleransi' => null, 'percaya_diri' => null, 'komunikasi' => null],
            ]],
        ]);

        $response->assertSessionHasErrors('records.0.spiritual_scores.taqwa');
        $this->assertDatabaseCount('student_attitudes', 0);
    }

    public function test_score_validation_rejects_out_of_bounds_scores(): void
    {
        $response = $this->actingAs($this->teacherUser)
            ->post(route('assessment.scores.store'), [
                'component_id' => $this->component->id,
                'scores' => [
                    [
                        'enrollment_id' => $this->enrollment->id,
                        'raw_score' => 150, // Invalid > 100
                    ],
                ],
            ]);

        $response->assertSessionHasErrors(['scores.0.raw_score']);
    }

    public function test_admin_can_manage_assessment_schemes_and_components(): void
    {
        // 1. View skema list
        $viewResponse = $this->actingAs($this->adminUser)
            ->get(route('assessment.schemes.index'));
        $viewResponse->assertOk();
        $viewResponse->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Schemes/Index')
            ->has('schemes', 1)
        );

        // 2. Tambah skema baru (akan auto-increment version bila kode sama)
        $storeResponse = $this->actingAs($this->adminUser)
            ->post(route('assessment.schemes.store'), [
                'name' => 'Kurikulum Merdeka 2026 Revisi',
                'code' => 'KM-2026',
                'rounding_rule' => 'ceil',
                'decimal_places' => 1,
                'passing_grade_default' => 70,
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('assessment_schemes', [
            'school_id' => $this->school->id,
            'code' => 'KM-2026',
            'version' => 2,
            'decimal_places' => 1,
        ]);

        $scheme2 = AssessmentScheme::where('school_id', $this->school->id)->where('version', 2)->firstOrFail();

        // 3. Tambah komponen ke skema baru
        $compResponse = $this->actingAs($this->adminUser)
            ->post(route('assessment.schemes.component', $scheme2), [
                'name' => 'Sumatif Akhir Semester',
                'code' => 'SAS',
                'weight_percentage' => 50,
                'passing_grade' => 70,
                'sequence' => 1,
            ]);

        $compResponse->assertRedirect();
        $this->assertDatabaseHas('assessment_components', [
            'scheme_id' => $scheme2->id,
            'code' => 'SAS',
            'weight_percentage' => 50,
        ]);
    }

    public function test_homeroom_teacher_can_generate_batch_reports_and_publish(): void
    {
        // 1. Generate draf batch
        $genResponse = $this->actingAs($this->homeroomUser)
            ->post(route('assessment.reports.generate'), [
                'class_group_id' => $this->classGroup->id,
                'term_id' => $this->term->id,
            ]);

        $genResponse->assertRedirect();
        $this->assertDatabaseCount('report_snapshots', 2);

        $report = ReportSnapshot::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame(ReportStatusEnum::DRAFT, $report->status);

        // 2. Publish rapor
        $pubResponse = $this->actingAs($this->homeroomUser)
            ->post(route('assessment.reports.publish', $report));

        $pubResponse->assertRedirect();
        $report->refresh();

        $this->assertSame(ReportStatusEnum::PUBLISHED, $report->status);
        $this->assertNotNull($report->published_at);
        $this->assertNotEmpty($report->checksum);
    }

    public function test_batch_generation_skips_already_published_reports(): void
    {
        $report = ReportSnapshot::create([
            'school_id' => $this->school->id, 'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id, 'class_group_id' => $this->classGroup->id,
            'student_id' => $this->student->id, 'status' => ReportStatusEnum::PUBLISHED,
            'scheme_snapshot' => [], 'grades_snapshot' => [], 'attendance_snapshot' => [],
            'notes_snapshot' => [], 'checksum' => hash('sha256', 'published-original'), 'published_at' => now(),
        ]);

        $this->actingAs($this->homeroomUser)->post(route('assessment.reports.generate'), [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(ReportStatusEnum::PUBLISHED, $report->fresh()->status);
        $this->assertSame(hash('sha256', 'published-original'), $report->fresh()->checksum);
    }

    public function test_student_can_only_view_their_own_report_snapshot(): void
    {
        // Buat report untuk Ahmad
        $ahmadReport = ReportSnapshot::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->classGroup->id,
            'student_id' => $this->student->id,
            'scheme_snapshot' => ['code' => 'KM-2026'],
            'grades_snapshot' => [],
            'attendance_snapshot' => ['sick' => 0, 'permitted' => 0, 'absent' => 0],
            'notes_snapshot' => [],
            'scheme_version' => 1,
            'status' => ReportStatusEnum::PUBLISHED,
            'checksum' => hash('sha256', 'sample-ahmad'),
            'published_at' => now(),
        ]);

        // Ahmad dapat melihat laporannya sendiri
        $responseAhmad = $this->actingAs($this->studentUser)
            ->get(route('assessment.reports.show', $ahmadReport));
        $responseAhmad->assertOk();
        $responseAhmad->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Reports/Show')
            ->where('report.id', $ahmadReport->id)
            ->where('report.student.name', 'Ahmad Dahlan')
        );

        // Siti Fatimah TIDAK BOLEH melihat laporan milik Ahmad (403 Forbidden)
        $responseSiti = $this->actingAs($this->otherStudentUser)
            ->get(route('assessment.reports.show', $ahmadReport));
        $responseSiti->assertForbidden();
    }

    public function test_student_report_list_contains_only_own_published_reports(): void
    {
        $ownPublished = ReportSnapshot::create([
            'school_id' => $this->school->id, 'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id, 'class_group_id' => $this->classGroup->id,
            'student_id' => $this->student->id, 'status' => ReportStatusEnum::PUBLISHED,
            'scheme_snapshot' => [], 'grades_snapshot' => [], 'attendance_snapshot' => [],
            'notes_snapshot' => [], 'checksum' => hash('sha256', 'own-published'), 'published_at' => now(),
        ]);
        ReportSnapshot::create([
            'school_id' => $this->school->id, 'enrollment_id' => $this->otherEnrollment->id,
            'term_id' => $this->term->id, 'class_group_id' => $this->classGroup->id,
            'student_id' => $this->otherStudent->id, 'status' => ReportStatusEnum::PUBLISHED,
            'scheme_snapshot' => [], 'grades_snapshot' => [], 'attendance_snapshot' => [],
            'notes_snapshot' => [], 'checksum' => hash('sha256', 'other-published'), 'published_at' => now(),
        ]);

        $this->actingAs($this->studentUser)->get(route('assessment.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assessment/Reports/Index')
                ->has('reports', 1)
                ->where('reports.0.id', $ownPublished->id)
            );

    }

    public function test_student_cannot_view_their_own_draft_report_snapshot(): void
    {
        $ownDraft = ReportSnapshot::create([
            'school_id' => $this->school->id, 'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id, 'class_group_id' => $this->classGroup->id,
            'student_id' => $this->student->id, 'status' => ReportStatusEnum::DRAFT,
            'scheme_snapshot' => [], 'grades_snapshot' => [], 'attendance_snapshot' => [],
            'notes_snapshot' => [], 'checksum' => hash('sha256', 'own-draft'),
        ]);

        $this->actingAs($this->studentUser)->get(route('assessment.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('reports', 0));
        $this->get(route('assessment.reports.show', $ownDraft))->assertForbidden();
    }

    public function test_class_ledger_view_returns_matrix_data_and_json(): void
    {
        $response = $this->actingAs($this->homeroomUser)
            ->get(route('assessment.reports.ledger', [
                'class_group_id' => $this->classGroup->id,
                'term_id' => $this->term->id,
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Assessment/Reports/Ledger')
            ->has('ledger.students', 2)
            ->has('ledger.subjects', 1)
        );

        // Request format JSON
        $jsonResponse = $this->actingAs($this->homeroomUser)
            ->json('GET', route('assessment.reports.ledger', [
                'class_group_id' => $this->classGroup->id,
                'term_id' => $this->term->id,
            ]));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'class_group',
            'term',
            'subjects',
            'students' => [
                '*' => ['student_id', 'nis', 'name', 'grades', 'average', 'attendance'],
            ],
        ]);
    }

    public function test_question_share_requires_acceptance_and_revocation_immediately_ends_recipient_access(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Soal yang dibagikan', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);
        $sharePayload = [
            'resource_type' => 'question', 'resource_id' => $question->id,
            'recipient_id' => $this->homeroomUser->id, 'permission' => 'view', 'request_note' => 'Mohon ditinjau',
        ];

        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.store'), $sharePayload)
            ->assertRedirect(route('assessment.question-shares.index'));
        $share = QuestionShare::query()->firstOrFail();
        $this->assertSame('pending', $share->status);
        $this->assertSame('active', $share->active_key);

        $this->actingAs($this->homeroomUser)->get(route('assessment.question-shares.show', $share))
            ->assertForbidden();
        $this->actingAs($this->homeroomUser)->put(route('assessment.questions.update', $question), [
            'type' => 'multiple_choice', 'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'prompt' => 'Tidak boleh edit',
            'options' => ['A', 'B'], 'correct_answer' => 'A', 'points' => 1,
        ])->assertForbidden();
        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.store'), $sharePayload)
            ->assertSessionHasErrors('recipient_id');
        $this->assertDatabaseCount('question_shares', 1);

        $this->actingAs($this->homeroomUser)->post(route('assessment.question-shares.decide', [$share, 'accept']), ['decision_note' => 'Disetujui'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('question_shares', ['id' => $share->id, 'status' => 'accepted', 'decision_note' => 'Disetujui']);
        $this->actingAs($this->homeroomUser)->get(route('assessment.question-shares.show', $share))->assertOk();
        $this->assertDatabaseHas('question_share_events', ['question_share_id' => $share->id, 'event_type' => 'accepted', 'old_status' => 'pending', 'new_status' => 'accepted']);

        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.revoke', $share))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('question_shares', ['id' => $share->id, 'status' => 'revoked', 'active_key' => null]);
        $this->actingAs($this->homeroomUser)->get(route('assessment.question-shares.show', $share))->assertForbidden();
        $this->assertDatabaseHas('audit_events', ['target_type' => 'question_share', 'target_id' => (string) $share->id, 'event_type' => 'assessment.question_share.revoked']);
    }

    public function test_view_only_question_share_redacts_answer_key_from_recipient(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Soal tanpa kunci', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'explanation' => 'Penjelasan rahasia', 'points' => 1,
            'is_active' => true, 'current_version' => 1,
        ]);
        ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1,
            'type' => 'multiple_choice', 'prompt' => 'Soal tanpa kunci', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'explanation' => 'Penjelasan rahasia', 'points' => 1,
            'created_by' => $this->teacherUser->id,
        ]);
        $share = QuestionShare::create([
            'school_id' => $this->school->id, 'sender_id' => $this->teacherUser->id,
            'recipient_id' => $this->homeroomUser->id, 'resource_type' => 'question',
            'resource_id' => $question->id, 'permission' => 'view', 'status' => 'accepted',
            'active_key' => 'active',
        ]);

        $this->actingAs($this->homeroomUser)->get(route('assessment.question-shares.show', $share))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('resource.content.correct_answer')
                ->missing('resource.content.explanation'));
    }

    public function test_question_share_index_exposes_owned_resources_and_resolves_shared_names(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Jelaskan proses fotosintesis', 'points' => 2, 'is_active' => true, 'current_version' => 1,
        ]);
        $pack = QuestionPack::create([
            'school_id' => $this->school->id, 'created_by' => $this->teacherUser->id,
            'name' => 'Paket IPA VII', 'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'status' => 'draft',
        ]);
        $share = QuestionShare::create([
            'school_id' => $this->school->id, 'sender_id' => $this->teacherUser->id, 'recipient_id' => $this->homeroomUser->id,
            'resource_type' => 'question', 'resource_id' => $question->id, 'permission' => 'view', 'status' => 'pending', 'active_key' => 'active',
        ]);

        $this->actingAs($this->teacherUser)->get(route('assessment.question-shares.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assessment/QuestionShares/Index')
                ->where('sent.data.0.id', $share->id)
                ->where('sent.data.0.resource_name', 'Jelaskan proses fotosintesis')
                ->where('resource_options.questions.0.id', $question->id)
                ->where('resource_options.packs.0.id', $pack->id));
    }

    public function test_copy_share_creates_recipient_owned_question_without_changing_source_owner(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Konten salinan', 'options' => ['A', 'B'],
            'correct_answer' => 'B', 'points' => 2, 'is_active' => true, 'current_version' => 1,
        ]);
        $share = QuestionShare::create([
            'school_id' => $this->school->id, 'sender_id' => $this->teacherUser->id, 'recipient_id' => $this->homeroomUser->id,
            'resource_type' => 'question', 'resource_id' => $question->id, 'permission' => 'copy', 'status' => 'accepted', 'active_key' => 'active',
        ]);

        $this->actingAs($this->homeroomUser)->post(route('assessment.question-shares.copy', $share))
            ->assertRedirect(route('assessment.questions.index'));
        $copy = ExamQuestion::query()->where('owner_id', $this->homeroomUser->id)->where('prompt', 'Konten salinan')->firstOrFail();
        $this->assertNotSame($question->id, $copy->id);
        $this->assertSame($this->teacherUser->id, $question->fresh()->owner_id);
        $this->assertDatabaseHas('exam_question_versions', ['exam_question_id' => $copy->id, 'created_by' => $this->homeroomUser->id, 'prompt' => 'Konten salinan']);
    }

    public function test_question_share_rejects_cross_school_recipients(): void
    {
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $teacherRole = Role::query()->where('name', 'teacher')->firstOrFail();
        $otherTeacher = User::create([
            'school_id' => $otherSchool->id, 'role_id' => $teacherRole->id, 'name' => 'Guru Lain',
            'username' => 'teacher_other', 'email' => 'other@sekolah.id', 'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Soal rahasia', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);

        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.store'), [
            'resource_type' => 'question', 'resource_id' => $question->id, 'recipient_id' => $otherTeacher->id,
            'permission' => 'view', 'request_note' => 'Tidak boleh lintas sekolah',
        ])->assertSessionHasErrors('recipient_id');
        $this->assertDatabaseCount('question_shares', 0);
    }

    public function test_rejected_request_keeps_history_and_can_be_requested_again(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Soal riwayat', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);
        $payload = ['resource_type' => 'question', 'resource_id' => $question->id, 'recipient_id' => $this->homeroomUser->id, 'permission' => 'view'];
        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.store'), $payload)->assertRedirect();
        $share = QuestionShare::query()->firstOrFail();

        $this->actingAs($this->homeroomUser)->post(route('assessment.question-shares.decide', [$share, 'reject']), ['decision_note' => 'Belum sesuai'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('question_shares', ['id' => $share->id, 'status' => 'rejected', 'active_key' => null, 'decision_note' => 'Belum sesuai']);
        $this->assertDatabaseHas('question_share_events', ['question_share_id' => $share->id, 'event_type' => 'rejected', 'old_status' => 'pending', 'new_status' => 'rejected']);

        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('question_shares', 2);
    }

    public function test_edit_share_allows_versioned_source_update_without_transferring_ownership(): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'multiple_choice', 'prompt' => 'Isi lama', 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);
        $share = QuestionShare::create([
            'school_id' => $this->school->id, 'sender_id' => $this->teacherUser->id, 'recipient_id' => $this->homeroomUser->id,
            'resource_type' => 'question', 'resource_id' => $question->id, 'permission' => 'edit', 'status' => 'accepted', 'active_key' => 'active',
        ]);

        $this->actingAs($this->homeroomUser)->put(route('assessment.questions.update', $question), [
            'type' => 'multiple_choice', 'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'prompt' => 'Versi baru dari kolaborator',
            'options' => ['A', 'B'], 'correct_answer' => 'B', 'points' => 2,
        ])->assertRedirect();

        $this->assertSame($this->teacherUser->id, $question->fresh()->owner_id);
        $this->assertSame(2, $question->fresh()->current_version);
        $this->assertDatabaseHas('exam_question_versions', [
            'exam_question_id' => $question->id, 'version_number' => 2,
            'created_by' => $this->homeroomUser->id, 'prompt' => 'Versi baru dari kolaborator',
        ]);
        $this->assertDatabaseHas('question_shares', ['id' => $share->id, 'status' => 'accepted']);
    }

    public function test_pack_share_can_be_accepted_and_copied_as_recipient_owned_draft(): void
    {
        Teacher::query()->firstOrFail()->update(['user_id' => $this->homeroomUser->id]);
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->teacherUser->id,
            'type' => 'essay', 'prompt' => 'Soal dari paket berbagi', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'is_active' => true, 'current_version' => 1,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1,
            'type' => 'essay', 'prompt' => 'Soal dari paket berbagi', 'points' => 2,
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'created_by' => $this->teacherUser->id,
        ]);
        $pack = QuestionPack::create([
            'school_id' => $this->school->id, 'created_by' => $this->teacherUser->id,
            'name' => 'Paket kolaborasi', 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
            'randomize_questions' => false, 'randomize_options' => false, 'status' => 'active',
        ]);
        QuestionPackItem::create([
            'question_pack_id' => $pack->id, 'exam_question_id' => $question->id,
            'question_version_id' => $version->id, 'sequence' => 1, 'points' => 6,
        ]);
        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.store'), [
            'resource_type' => 'pack', 'resource_id' => $pack->id, 'recipient_id' => $this->homeroomUser->id, 'permission' => 'copy',
        ])->assertRedirect();
        $share = QuestionShare::query()->firstOrFail();
        $this->actingAs($this->homeroomUser)->post(route('assessment.question-shares.decide', [$share, 'accept']))->assertSessionHasNoErrors();
        $this->actingAs($this->homeroomUser)->post(route('assessment.question-shares.copy', $share))->assertRedirect(route('assessment.question-packs.index'));

        $copy = QuestionPack::query()->where('created_by', $this->homeroomUser->id)->where('name', 'Paket kolaborasi (salinan)')->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertSame($this->teacherUser->id, $pack->fresh()->created_by);

        $this->actingAs($this->teacherUser)->post(route('assessment.question-shares.revoke', $share))->assertRedirect();
        $this->actingAs($this->homeroomUser)->put(route('assessment.question-packs.update', $copy), [
            'name' => $copy->name, 'jenjang' => 'SMP', 'curriculum' => 'merdeka',
            'randomize_questions' => false, 'randomize_options' => false, 'status' => 'active',
            'items' => [['question_id' => $question->id, 'points' => 6]],
        ])->assertRedirect();

        $examData = [
            'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'title' => 'Ujian dari paket salinan', 'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(2)->toDateTimeString(), 'duration_minutes' => 45,
            'randomize_questions' => false, 'randomize_options' => false, 'max_violations' => 3,
        ];
        $this->actingAs($this->homeroomUser)->post(route('exams.store'), [
            ...$examData, 'question_ids' => [$question->id],
        ])->assertUnprocessable();
        $this->actingAs($this->homeroomUser)->post(route('exams.store'), [
            ...$examData, 'question_pack_id' => $copy->id,
        ])->assertRedirect('/ujian');
        $exam = Exam::query()->where('title', 'Ujian dari paket salinan')->firstOrFail();
        $this->assertDatabaseHas('exam_question_items', [
            'exam_id' => $exam->id, 'exam_question_id' => $question->id,
            'question_version_id' => $version->id, 'points' => 6,
        ]);
    }

    #[DataProvider('matchingAnswerPayloads')]
    public function test_matching_submission_handles_json_scalars_and_grades_valid_pairs(string $payload, bool $correct): void
    {
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id,
            'type' => 'matching', 'prompt' => 'Pasangkan ibu kota.',
            'correct_answer' => '{"0":"Jakarta","1":"Tokyo"}', 'points' => 5, 'is_active' => true,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'Ujian Menjodohkan', 'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(), 'duration_minutes' => 30, 'status' => 'published',
        ]);
        $exam->items()->create(['exam_question_id' => $question->id, 'sequence' => 1, 'points' => 5]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'status' => 'in_progress',
            'started_at' => now(), 'expires_at' => now()->addMinutes(30), 'question_order' => [$question->id],
        ]);
        $answer = ExamAnswer::create(['exam_attempt_id' => $attempt->id, 'exam_question_id' => $question->id, 'answer' => $payload]);

        $this->actingAs($this->studentUser)->post('/ujian/attempt/'.$attempt->id.'/kirim')->assertRedirect('/ujian');

        $this->assertSame('graded', $attempt->fresh()->status);
        $this->assertSame($correct, $answer->fresh()->is_correct);
        $this->assertSame($correct ? 5.0 : 0.0, (float) $answer->fresh()->score);
        $this->assertSame($correct ? 100.0 : 0.0, (float) $attempt->fresh()->score);
    }

    public static function matchingAnswerPayloads(): array
    {
        return [
            'number' => ['1', false],
            'string' => ['"Jakarta"', false],
            'boolean' => ['true', false],
            'null' => ['null', false],
            'invalid json' => ['invalid', false],
            'nested array' => ['[["Jakarta"],"Tokyo"]', false],
            'empty array' => ['[]', false],
            'correct reversed keys' => ['{"1":"Tokyo","0":"Jakarta"}', true],
            'incorrect pairs' => ['["Tokyo","Jakarta"]', false],
        ];
    }

    public function test_assessment_analytics_handles_empty_data_without_division_errors(): void
    {
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'Sekolah Terpisah', 'status' => 'active']);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id, 'role_id' => $this->adminUser->role_id, 'name' => 'Admin Lain',
            'username' => 'admin_lain_analytics', 'email' => 'admin-lain-analytics@sekolah.id',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        ExamQuestion::create([
            'school_id' => $otherSchool->id, 'owner_id' => $otherAdmin->id, 'type' => 'essay',
            'prompt' => 'Tidak boleh masuk statistik sekolah aktif', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);

        $this->actingAs($this->adminUser)->get('/penilaian/analisis')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assessment/Analytics/Index')
                ->where('bank.total', 0)
                ->has('bank.by_type', 0)
                ->where('exam_results.available', false)
                ->where('exam_results.average_score', null)
            );
    }

    public function test_assessment_analytics_aggregates_exam_items_only_after_results_are_published(): void
    {
        $subjectId = DB::table('subjects')->where('school_id', $this->school->id)->value('id');
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id, 'subject_id' => $subjectId,
            'type' => 'multiple_choice', 'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'cognitive_level' => 'L2',
            'prompt' => 'Berapa hasil 2 + 2?', 'options' => ['3', '4'], 'correct_answer' => '4',
            'points' => 5, 'is_active' => true, 'current_version' => 1,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1, 'type' => 'multiple_choice',
            'prompt' => $question->prompt, 'options' => $question->options, 'correct_answer' => '4',
            'points' => 5, 'subject_id' => $subjectId, 'subject_name_snapshot' => 'Matematika',
            'jenjang' => 'SMP', 'curriculum' => 'merdeka', 'cognitive_level' => 'L2', 'created_by' => $this->adminUser->id,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'Analisis Ujian', 'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(), 'duration_minutes' => 30, 'randomize_questions' => false,
            'randomize_options' => false, 'max_violations' => 3, 'status' => 'results_published',
        ]);
        $exam->items()->create(['exam_question_id' => $question->id, 'question_version_id' => $version->id, 'sequence' => 1, 'points' => 5]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id, 'enrollment_id' => $this->enrollment->id, 'status' => 'graded',
            'started_at' => now()->subHour(), 'expires_at' => now()->addMinutes(20), 'submitted_at' => now(),
            'score' => 80, 'question_order' => [$question->id],
        ]);
        ExamAnswer::create([
            'exam_attempt_id' => $attempt->id, 'exam_question_id' => $question->id, 'question_version_id' => $version->id,
            'answer' => '4', 'is_correct' => true, 'score' => 5,
        ]);

        $this->actingAs($this->adminUser)->get('/penilaian/analisis')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.exam_id', $exam->id)
                ->where('exam_results.exam.id', $exam->id)
                ->where('exam_results.available', true)
            );

        $this->actingAs($this->adminUser)->get('/penilaian/analisis?exam_id='.$exam->id.'&subject_id='.$subjectId)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assessment/Analytics/Index')
                ->where('bank.total', 1)
                ->where('exam_results.available', true)
                ->where('exam_results.average_score', 80)
                ->where('exam_results.score_distribution.3.count', 1)
                ->where('exam_results.items.0.answered', 1)
                ->where('exam_results.items.0.correct_rate', 100)
                ->missing('exam_results.students')
            );

        $exam->update(['status' => 'published']);
        $this->actingAs($this->adminUser)->get('/penilaian/analisis?exam_id='.$exam->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('exam_results.available', false)
                ->where('exam_results.items', null)
            );
    }

    public function test_students_cannot_open_or_export_assessment_analytics(): void
    {
        $this->actingAs($this->studentUser)->get('/penilaian/analisis')->assertForbidden();
        $this->actingAs($this->studentUser)->get('/penilaian/analisis/export')->assertForbidden();
    }

    public function test_analytics_xlsx_keeps_formula_like_item_text_inert_and_omits_student_details(): void
    {
        $prompt = '=HYPERLINK("https://invalid.example","klik")';
        $question = ExamQuestion::create([
            'school_id' => $this->school->id, 'owner_id' => $this->adminUser->id,
            'type' => 'multiple_choice', 'prompt' => $prompt, 'options' => ['A', 'B'],
            'correct_answer' => 'A', 'points' => 1, 'is_active' => true, 'current_version' => 1,
        ]);
        $version = ExamQuestionVersion::create([
            'exam_question_id' => $question->id, 'version_number' => 1, 'type' => 'multiple_choice',
            'prompt' => $prompt, 'options' => ['A', 'B'], 'correct_answer' => 'A', 'points' => 1,
            'created_by' => $this->adminUser->id,
        ]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => TeachingAssignment::query()->value('id'),
            'created_by' => $this->adminUser->id, 'title' => 'Export Analisis', 'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(), 'duration_minutes' => 30, 'randomize_questions' => false,
            'randomize_options' => false, 'max_violations' => 3, 'status' => 'results_published',
        ]);
        $exam->items()->create(['exam_question_id' => $question->id, 'question_version_id' => $version->id, 'sequence' => 1, 'points' => 1]);

        $response = $this->actingAs($this->adminUser)->get('/penilaian/analisis/export?exam_id='.$exam->id);
        $response->assertOk()->assertDownload('analisis-bank-soal-dan-ujian.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'assessment-analytics-').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $book = (new Xlsx)->load($path);
        $itemsSheet = $book->getSheetByName('Analisis Butir');
        $this->assertSame($prompt, $itemsSheet->getCell('C2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $itemsSheet->getCell('C2')->getDataType());
        $this->assertNull($book->getSheetByName('Siswa'));
        $book->disconnectWorksheets();
        @unlink($path);
    }

    public function test_teacher_analytics_lists_only_exams_in_their_teaching_scope(): void
    {
        Teacher::query()->where('nip', '198501012010011001')->update(['user_id' => $this->teacherUser->id]);
        $otherTeacher = Teacher::create([
            'school_id' => $this->school->id, 'nip' => '198501012010011002', 'name' => 'Guru tanpa penugasan saya', 'is_active' => true,
        ]);
        $subjectId = DB::table('subjects')->where('school_id', $this->school->id)->value('id');
        $otherAssignment = TeachingAssignment::create([
            'term_id' => $this->term->id, 'teacher_id' => $otherTeacher->id,
            'subject_id' => $subjectId, 'class_group_id' => $this->classGroup->id,
        ]);
        $makeExam = fn (int $assignmentId, string $title) => Exam::create([
            'school_id' => $this->school->id, 'teaching_assignment_id' => $assignmentId,
            'created_by' => $this->adminUser->id, 'title' => $title, 'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(), 'duration_minutes' => 30, 'randomize_questions' => false,
            'randomize_options' => false, 'max_violations' => 3, 'status' => 'results_published',
        ]);
        $ownExam = $makeExam((int) TeachingAssignment::query()->where('teacher_id', $this->teacherId())->value('id'), 'Ujian saya');
        $otherExam = $makeExam((int) $otherAssignment->id, 'Ujian guru lain');

        $this->actingAs($this->teacherUser)->get('/penilaian/analisis')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('exams', 1)
                ->where('exams.0.id', $ownExam->id)
            );
        $this->actingAs($this->teacherUser)->get('/penilaian/analisis?exam_id='.$otherExam->id)->assertNotFound();
    }

    private function teacherId(): int
    {
        return (int) Teacher::query()->where('nip', '198501012010011001')->value('id');
    }

    public function test_teacher_cannot_read_or_write_scores_for_another_teachers_component(): void
    {
        $other = $this->createUnassignedEnrollment();
        $assignment = TeachingAssignment::where('class_group_id', $other->class_group_id)->firstOrFail();
        $component = $this->component->replicate();
        $component->teaching_assignment_id = $assignment->id;
        $component->code = 'OTHER';
        $component->save();

        $this->actingAs($this->teacherUser)->get(route('assessment.scores.index', [
            'class_group_id' => $other->class_group_id, 'component_id' => $component->id,
        ]))->assertForbidden();
        $this->post(route('assessment.scores.store'), [
            'component_id' => $component->id,
            'scores' => [['enrollment_id' => $other->id, 'raw_score' => 99]],
        ])->assertForbidden();
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_score_batch_rejects_unassigned_enrollment_without_partial_writes(): void
    {
        $other = $this->createUnassignedEnrollment();
        $this->component->update(['teaching_assignment_id' => TeachingAssignment::where('class_group_id', $this->classGroup->id)->value('id')]);

        $this->actingAs($this->teacherUser)->post(route('assessment.scores.store'), [
            'component_id' => $this->component->id,
            'scores' => [
                ['enrollment_id' => $this->enrollment->id, 'raw_score' => 80],
                ['enrollment_id' => $other->id, 'raw_score' => 99],
            ],
        ])->assertForbidden();
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_teacher_cannot_write_a_component_from_another_school(): void
    {
        $school = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $scheme = $this->scheme->replicate();
        $scheme->school_id = $school->id;
        $scheme->save();
        $component = $this->component->replicate();
        $component->scheme_id = $scheme->id;
        $component->save();

        $this->actingAs($this->teacherUser)->post(route('assessment.scores.store'), [
            'component_id' => $component->id,
            'scores' => [['enrollment_id' => $this->enrollment->id, 'raw_score' => 99]],
        ])->assertForbidden();
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_homeroom_cannot_generate_or_publish_another_class_report(): void
    {
        $other = $this->createUnassignedEnrollment();
        $report = $this->draftReportFor($other);
        $this->actingAs($this->homeroomUser)->post(route('assessment.reports.generate'), [
            'class_group_id' => $other->class_group_id, 'term_id' => $this->term->id,
        ])->assertForbidden();
        $this->post(route('assessment.reports.publish', $report))->assertForbidden();
        $this->assertSame(ReportStatusEnum::DRAFT, $report->fresh()->status);
    }

    public function test_admin_cannot_publish_or_view_another_schools_report(): void
    {
        $report = $this->draftReportFor($this->enrollment);
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $otherAdmin = $this->adminUser->replicate();
        $otherAdmin->fill(['school_id' => $otherSchool->id, 'username' => 'other_admin', 'email' => 'other@example.test']);
        $otherAdmin->save();

        $this->actingAs($otherAdmin)->post(route('assessment.reports.publish', $report))->assertForbidden();
        $this->get(route('assessment.reports.show', $report))->assertForbidden();
        $this->assertSame(ReportStatusEnum::DRAFT, $report->fresh()->status);
    }

    public function test_guest_and_unassigned_teacher_cannot_read_class_ledger(): void
    {
        $other = $this->createUnassignedEnrollment();
        $this->actingAs($this->teacherUser)->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $other->class_group_id, 'term_id' => $this->term->id,
        ]))->assertForbidden();

        $parentRole = Role::create(['name' => 'parent', 'label' => 'Orang Tua', 'is_system' => true]);
        $parent = User::create([
            'school_id' => $this->school->id, 'role_id' => $parentRole->id,
            'name' => 'Orang Tua Uji', 'username' => 'parent_ledger_test',
            'email' => 'parent.ledger@test.example', 'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $this->actingAs($parent)->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ]))->assertForbidden();

        $this->actingAs($this->studentUser)->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ]))->assertForbidden();

        $role = Role::create(['name' => 'guest', 'label' => 'Tamu', 'is_system' => true]);
        $this->teacherUser->update(['role_id' => $role->id]);
        $this->teacherUser->unsetRelation('role');
        $this->actingAs($this->teacherUser)->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ]))->assertForbidden();
    }

    public function test_operator_cannot_read_another_schools_class_ledger(): void
    {
        $operatorRole = Role::create(['name' => 'operator', 'label' => 'Operator', 'is_system' => true]);
        $operator = User::create([
            'school_id' => $this->school->id, 'role_id' => $operatorRole->id,
            'name' => 'Operator Uji', 'username' => 'operator_ledger_test',
            'email' => 'operator.ledger@test.example', 'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $otherClass = ClassGroup::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-X',
            'grade_level' => 7,
        ]);

        $this->actingAs($operator)->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $otherClass->id, 'term_id' => $this->term->id,
        ]))->assertForbidden();
    }

    public function test_class_ledger_rejects_cross_school_and_mismatched_academic_year(): void
    {
        $year = $this->academicYear->replicate();
        $year->name = '2027/2028';
        $year->save();
        $term = $this->term->replicate();
        $term->academic_year_id = $year->id;
        $term->save();
        $this->actingAs($this->adminUser)->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $this->classGroup->id, 'term_id' => $term->id,
        ]))->assertForbidden();

        $school = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $this->adminUser->update(['school_id' => $school->id]);
        $this->getJson(route('assessment.reports.ledger', [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ]))->assertForbidden();
    }

    public function test_report_grades_keep_subject_specific_components_separate(): void
    {
        $mathAssignment = TeachingAssignment::where('class_group_id', $this->classGroup->id)->firstOrFail();
        $this->component->update(['teaching_assignment_id' => $mathAssignment->id, 'weight_percentage' => 100]);
        $subject = Subject::create(['school_id' => $this->school->id, 'code' => 'BHS', 'name' => 'Bahasa', 'is_active' => true]);
        $languageAssignment = $mathAssignment->replicate();
        $languageAssignment->subject_id = $subject->id;
        $languageAssignment->save();
        $languageComponent = $this->component->replicate();
        $languageComponent->fill(['teaching_assignment_id' => $languageAssignment->id, 'code' => 'BHS', 'name' => 'Nilai Bahasa']);
        $languageComponent->save();
        foreach ([[$this->component->id, 90], [$languageComponent->id, 50]] as [$componentId, $score]) {
            Score::create(['school_id' => $this->school->id, 'enrollment_id' => $this->enrollment->id, 'assessment_component_id' => $componentId, 'raw_score' => $score, 'final_score' => $score, 'recorded_by_user_id' => $this->teacherUser->id]);
        }

        $this->actingAs($this->homeroomUser)->post(route('assessment.reports.generate'), [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $report = ReportSnapshot::where('enrollment_id', $this->enrollment->id)->firstOrFail();
        $grades = collect($report->grades_snapshot)->keyBy('subject_id');
        $this->assertEquals(90, $grades[$mathAssignment->subject_id]['final_score']);
        $this->assertEquals(50, $grades[$subject->id]['final_score']);
    }

    public function test_multi_subject_report_rejects_ambiguous_generic_components(): void
    {
        $assignment = TeachingAssignment::where('class_group_id', $this->classGroup->id)->firstOrFail();
        $subject = Subject::create(['school_id' => $this->school->id, 'code' => 'BHS', 'name' => 'Bahasa', 'is_active' => true]);
        $otherAssignment = $assignment->replicate();
        $otherAssignment->subject_id = $subject->id;
        $otherAssignment->save();

        $this->actingAs($this->homeroomUser)->post(route('assessment.reports.generate'), [
            'class_group_id' => $this->classGroup->id, 'term_id' => $this->term->id,
        ])->assertSessionHasErrors('class_group_id');
        $this->assertDatabaseCount('report_snapshots', 0);
        $this->actingAs($this->teacherUser)->post(route('assessment.scores.store'), [
            'component_id' => $this->component->id,
            'scores' => [['enrollment_id' => $this->enrollment->id, 'raw_score' => 90]],
        ])->assertForbidden();
        $this->assertDatabaseCount('scores', 0);
    }

    private function createUnassignedEnrollment(): Enrollment
    {
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'VII-B', 'grade_level' => 7]);
        $student = $this->student->replicate();
        $student->fill(['nis' => 'OTHER', 'nisn' => '9999999999', 'user_id' => null]);
        $student->save();
        $teacher = Teacher::create(['school_id' => $this->school->id, 'name' => 'Guru Lain', 'gender' => 'L', 'is_active' => true]);
        $assignment = TeachingAssignment::firstOrFail()->replicate();
        $assignment->fill(['class_group_id' => $class->id, 'teacher_id' => $teacher->id]);
        $assignment->save();

        return Enrollment::create(['student_id' => $student->id, 'class_group_id' => $class->id, 'term_id' => $this->term->id, 'status' => 'active']);
    }

    private function draftReportFor(Enrollment $enrollment): ReportSnapshot
    {
        return ReportSnapshot::create([
            'school_id' => $this->school->id, 'enrollment_id' => $enrollment->id, 'term_id' => $enrollment->term_id,
            'class_group_id' => $enrollment->class_group_id, 'student_id' => $enrollment->student_id,
            'status' => ReportStatusEnum::DRAFT, 'scheme_snapshot' => [], 'grades_snapshot' => [],
            'attendance_snapshot' => [], 'notes_snapshot' => [], 'checksum' => hash('sha256', 'draft'),
        ]);
    }

    public function test_admin_can_bind_legacy_component_and_create_subject_specific_component(): void
    {
        $assignment = TeachingAssignment::where('class_group_id', $this->classGroup->id)->firstOrFail();
        $this->actingAs($this->adminUser)->get(route('assessment.schemes.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('teaching_assignments', 1));
        $this->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $assignment->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assessment_components', ['id' => $this->component->id, 'teaching_assignment_id' => $assignment->id]);
        $this->post(route('assessment.schemes.component', $this->scheme), [
            'name' => 'Sumatif Matematika', 'code' => 'SAS-MAT', 'weight_percentage' => 60, 'passing_grade' => 75,
            'teaching_assignment_id' => $assignment->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assessment_components', ['code' => 'SAS-MAT', 'scheme_id' => $this->scheme->id, 'teaching_assignment_id' => $assignment->id]);

        $this->actingAs($this->teacherUser)->post(route('assessment.scores.store'), [
            'component_id' => $this->component->id, 'scores' => [['enrollment_id' => $this->enrollment->id, 'raw_score' => 88]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('scores', ['assessment_component_id' => $this->component->id, 'enrollment_id' => $this->enrollment->id, 'raw_score' => 88]);
    }

    public function test_component_binding_rejects_foreign_school_teacher_and_rebinding_existing_scores(): void
    {
        $other = $this->createUnassignedEnrollment();
        $assignment = TeachingAssignment::where('class_group_id', $this->classGroup->id)->firstOrFail();
        $otherAssignment = TeachingAssignment::where('class_group_id', $other->class_group_id)->firstOrFail();
        $this->actingAs($this->teacherUser)->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $assignment->id])->assertForbidden();
        Score::create(['school_id' => $this->school->id, 'enrollment_id' => $this->enrollment->id, 'assessment_component_id' => $this->component->id, 'raw_score' => 80, 'final_score' => 80, 'recorded_by_user_id' => $this->teacherUser->id]);
        $this->actingAs($this->adminUser)->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $otherAssignment->id])
            ->assertSessionHasErrors('teaching_assignment_id');
        $this->assertNull($this->component->fresh()->teaching_assignment_id);
        $this->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $assignment->id])->assertSessionHasNoErrors();
        $this->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $otherAssignment->id])->assertSessionHasErrors('teaching_assignment_id');
        $this->assertSame($assignment->id, $this->component->fresh()->teaching_assignment_id);

        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $this->adminUser->update(['school_id' => $otherSchool->id]);
        $this->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $assignment->id])->assertNotFound();
        $this->post(route('assessment.schemes.component', $this->scheme), [
            'name' => 'Foreign', 'code' => 'FOREIGN', 'weight_percentage' => 100, 'passing_grade' => 75,
        ])->assertNotFound();
        $this->assertDatabaseMissing('assessment_components', ['code' => 'FOREIGN']);
    }

    public function test_component_binding_and_score_batch_reject_foreign_school_assignment_and_enrollment(): void
    {
        $school = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $year = $this->academicYear->replicate();
        $year->school_id = $school->id;
        $year->save();
        $term = $this->term->replicate();
        $term->academic_year_id = $year->id;
        $term->save();
        $class = $this->classGroup->replicate();
        $class->fill(['school_id' => $school->id, 'academic_year_id' => $year->id, 'homeroom_teacher_id' => null]);
        $class->save();
        $student = $this->student->replicate();
        $student->fill(['school_id' => $school->id, 'user_id' => null]);
        $student->save();
        $enrollment = Enrollment::create(['student_id' => $student->id, 'class_group_id' => $class->id, 'term_id' => $term->id, 'status' => 'active']);
        $assignment = TeachingAssignment::firstOrFail()->replicate();
        $assignment->fill(['term_id' => $term->id, 'class_group_id' => $class->id]);
        $assignment->save();

        $this->actingAs($this->adminUser)->put(route('assessment.schemes.component.bind', [$this->scheme, $this->component]), ['teaching_assignment_id' => $assignment->id])->assertNotFound();
        $this->post(route('assessment.schemes.component', $this->scheme), [
            'name' => 'Foreign', 'code' => 'FOREIGN', 'weight_percentage' => 100, 'passing_grade' => 75, 'teaching_assignment_id' => $assignment->id,
        ])->assertNotFound();
        $this->actingAs($this->teacherUser)->post(route('assessment.scores.store'), [
            'component_id' => $this->component->id,
            'scores' => [['enrollment_id' => $this->enrollment->id, 'raw_score' => 80], ['enrollment_id' => $enrollment->id, 'raw_score' => 90]],
        ])->assertForbidden();
        $this->assertDatabaseCount('scores', 0);
        $this->assertNull($this->component->fresh()->teaching_assignment_id);
    }
}
