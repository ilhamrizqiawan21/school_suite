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
use App\Modules\Assessment\Application\Services\ReportPublishingService;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReportSnapshotImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacherUser;

    private Term $term;

    private ClassGroup $classGroup;

    private Student $student;

    private Enrollment $enrollment;

    private Subject $math;

    private AssessmentScheme $scheme;

    private AssessmentComponent $compFormatif;

    private AssessmentComponent $compSAS;

    private ReportPublishingService $publishingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $role = Role::create(['name' => 'homeroom', 'label' => 'Wali Kelas', 'is_system' => true]);
        $this->teacherUser = User::create([
            'role_id' => $role->id,
            'school_id' => $this->school->id,
            'name' => 'Guru Matematika',
            'username' => 'guru_math',
            'email' => 'math@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'nip' => '198001012005011002',
            'name' => 'Guru Matematika, M.Pd',
            'gender' => 'L',
            'is_active' => true,
        ]);

        $this->classGroup = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 7,
            'name' => 'VII-A',
            'homeroom_teacher_id' => $teacher->id,
            'capacity' => 32,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'nis' => '1001',
            'nisn' => '0012345678',
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'enrollment_status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_group_id' => $this->classGroup->id,
            'term_id' => $this->term->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT-7',
            'name' => 'Matematika',
            'order' => 1,
        ]);

        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->classGroup->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $this->math->id,
        ]);

        // Skema penilaian: Formatif 40%, SAS 60%
        $this->scheme = AssessmentScheme::create([
            'school_id' => $this->school->id,
            'name' => 'Kurikulum Merdeka 2026',
            'code' => 'KM-2026',
            'version' => 1,
            'is_active' => true,
            'rounding_rule' => RoundingRuleEnum::HALF_UP,
            'decimal_places' => 2,
            'passing_grade_default' => 75.00,
        ]);

        $this->compFormatif = AssessmentComponent::create([
            'scheme_id' => $this->scheme->id,
            'name' => 'Formatif',
            'code' => 'TP',
            'weight_percentage' => 40.00,
            'passing_grade' => 75.00,
            'sequence' => 1,
        ]);

        $this->compSAS = AssessmentComponent::create([
            'scheme_id' => $this->scheme->id,
            'name' => 'Sumatif Akhir Semester',
            'code' => 'SAS',
            'weight_percentage' => 60.00,
            'passing_grade' => 75.00,
            'sequence' => 2,
        ]);

        // Input nilai awal: Formatif 80, SAS 90 -> Nilai akhir = 32 + 54 = 86.0
        Score::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'assessment_component_id' => $this->compFormatif->id,
            'raw_score' => 80.00,
            'final_score' => 80.00,
            'recorded_by_user_id' => $this->teacherUser->id,
        ]);

        Score::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'assessment_component_id' => $this->compSAS->id,
            'raw_score' => 90.00,
            'final_score' => 90.00,
            'recorded_by_user_id' => $this->teacherUser->id,
        ]);

        $this->publishingService = app(ReportPublishingService::class);
    }

    public function test_report_snapshot_can_be_generated_and_published(): void
    {
        $report = $this->publishingService->generateReportSnapshot(
            schoolId: $this->school->id,
            enrollmentId: $this->enrollment->id,
            termId: $this->term->id,
            classGroupId: $this->classGroup->id,
            studentId: $this->student->id,
            actor: $this->teacherUser
        );

        $this->assertEquals(ReportStatusEnum::DRAFT, $report->status);
        $this->assertNotEmpty($report->checksum);
        $this->assertTrue($this->publishingService->verifyReportIntegrity($report));

        // Nilai matematika di snapshot harus 86.00
        $grades = $report->grades_snapshot;
        $this->assertCount(1, $grades);
        $this->assertEquals(86.00, $grades[0]['final_score']);
        $this->assertEquals('B', $grades[0]['predicate']);
        $this->assertTrue($grades[0]['is_passed']);

        // Terbitkan rapor
        $publishedReport = $this->publishingService->publishReport($report, $this->teacherUser);

        $this->assertEquals(ReportStatusEnum::PUBLISHED, $publishedReport->status);
        $this->assertNotNull($publishedReport->published_at);
        $this->assertEquals($this->teacherUser->id, $publishedReport->published_by_user_id);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'assessment.report.published',
            'target_id' => (string) $publishedReport->id,
        ]);
    }

    /**
     * SS-D-005: Rapor terbit bersifat Immutable.
     * Pengubahan nilai di tabel scores di kemudian hari tidak mempengaruhi rapor terbit.
     */
    public function test_published_report_snapshot_is_immutable_against_score_mutations(): void
    {
        // 1. Generate & Terbitkan Rapor
        $report = $this->publishingService->generateReportSnapshot(
            schoolId: $this->school->id,
            enrollmentId: $this->enrollment->id,
            termId: $this->term->id,
            classGroupId: $this->classGroup->id,
            studentId: $this->student->id,
            actor: $this->teacherUser
        );
        $this->publishingService->publishReport($report, $this->teacherUser);

        $originalChecksum = $report->checksum;
        $originalFinalScore = $report->grades_snapshot[0]['final_score'];
        $this->assertEquals(86.00, $originalFinalScore);

        // 2. Simulasi guru mengubah nilai mentah di database pada masa mendatang
        Score::where('enrollment_id', $this->enrollment->id)
            ->where('assessment_component_id', $this->compSAS->id)
            ->update(['raw_score' => 20.00, 'final_score' => 20.00]);

        // 3. Baca ulang rapor dari database
        $freshReport = ReportSnapshot::find($report->id);

        // Nilai pada snapshot rapor terbit terbukti TETAP 86.00 dan TIDAK terpengaruh oleh mutasi tabel scores
        $this->assertEquals(86.00, $freshReport->grades_snapshot[0]['final_score']);
        $this->assertEquals($originalChecksum, $freshReport->checksum);
        $this->assertTrue($this->publishingService->verifyReportIntegrity($freshReport));
    }

    /**
     * Rapor yang telah terbit terkunci dan dilarang di-regenerate sembarangan.
     */
    public function test_generating_already_published_report_throws_exception(): void
    {
        $report = $this->publishingService->generateReportSnapshot(
            schoolId: $this->school->id,
            enrollmentId: $this->enrollment->id,
            termId: $this->term->id,
            classGroupId: $this->classGroup->id,
            studentId: $this->student->id,
            actor: $this->teacherUser
        );
        $this->publishingService->publishReport($report, $this->teacherUser);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rapor untuk siswa pada semester ini telah diterbitkan dan berstatus terkunci permanen');

        // Percobaan regenerate harus ditolak
        $this->publishingService->generateReportSnapshot(
            schoolId: $this->school->id,
            enrollmentId: $this->enrollment->id,
            termId: $this->term->id,
            classGroupId: $this->classGroup->id,
            studentId: $this->student->id,
            actor: $this->teacherUser
        );
    }
}
