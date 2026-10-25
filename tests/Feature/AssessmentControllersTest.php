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
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
}
