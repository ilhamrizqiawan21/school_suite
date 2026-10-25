<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentMigrationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private Term $term;

    private ClassGroup $classGroup;

    private Student $student;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Penguji',
            'username' => 'guru_penguji',
            'email' => 'guru@school.test',
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

        $this->classGroup = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 7,
            'name' => 'VII-A',
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
    }

    public function test_all_assessment_tables_are_created(): void
    {
        $expectedTables = [
            'assessment_schemes',
            'assessment_components',
            'scores',
            'remedial_records',
            'report_snapshots',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table [{$table}] should exist in database schema."
            );
        }
    }

    public function test_scheme_supports_versioning_and_unique_constraints(): void
    {
        $schemeV1 = AssessmentScheme::create([
            'school_id' => $this->school->id,
            'name' => 'Kurikulum Merdeka 2026 V1',
            'code' => 'KM-SMP-7',
            'version' => 1,
            'is_active' => false,
            'rounding_rule' => 'half_up',
            'decimal_places' => 2,
            'passing_grade_default' => 75.00,
        ]);

        // Version 2 dengan code yang sama diperbolehkan
        $schemeV2 = AssessmentScheme::create([
            'school_id' => $this->school->id,
            'name' => 'Kurikulum Merdeka 2026 V2',
            'code' => 'KM-SMP-7',
            'version' => 2,
            'is_active' => true,
            'rounding_rule' => 'half_up',
            'decimal_places' => 2,
            'passing_grade_default' => 78.00,
        ]);

        $this->assertDatabaseCount('assessment_schemes', 2);

        // Duplikat school_id, code, dan version 1 harus ditolak
        $this->expectException(QueryException::class);
        AssessmentScheme::create([
            'school_id' => $this->school->id,
            'name' => 'Kurikulum Merdeka Duplicate',
            'code' => 'KM-SMP-7',
            'version' => 1,
            'is_active' => false,
        ]);
    }

    public function test_score_enforces_unique_enrollment_and_component(): void
    {
        $scheme = AssessmentScheme::create([
            'school_id' => $this->school->id,
            'name' => 'Skema Dasar',
            'code' => 'BASIC',
            'version' => 1,
        ]);

        $component = AssessmentComponent::create([
            'scheme_id' => $scheme->id,
            'name' => 'Sumatif Akhir Semester',
            'code' => 'SAS',
            'weight_percentage' => 40.00,
            'passing_grade' => 75.00,
        ]);

        Score::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'assessment_component_id' => $component->id,
            'raw_score' => 85.00,
            'final_score' => 85.00,
            'recorded_by_user_id' => $this->teacher->id,
        ]);

        // Duplikat input untuk enrollment dan komponen yang sama harus ditolak
        $this->expectException(QueryException::class);
        Score::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'assessment_component_id' => $component->id,
            'raw_score' => 90.00,
            'final_score' => 90.00,
            'recorded_by_user_id' => $this->teacher->id,
        ]);
    }

    public function test_report_snapshot_enforces_unique_enrollment_and_term(): void
    {
        ReportSnapshot::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->classGroup->id,
            'student_id' => $this->student->id,
            'status' => 'draft',
            'scheme_snapshot' => ['version' => 1],
            'grades_snapshot' => [],
            'attendance_snapshot' => ['present' => 100],
            'notes_snapshot' => ['general' => 'Baik'],
            'checksum' => hash('sha256', 'sample-snapshot-1'),
        ]);

        // Duplikat snapshot rapor untuk enrollment & term yang sama harus ditolak
        $this->expectException(QueryException::class);
        ReportSnapshot::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->classGroup->id,
            'student_id' => $this->student->id,
            'status' => 'draft',
            'scheme_snapshot' => ['version' => 1],
            'grades_snapshot' => [],
            'attendance_snapshot' => [],
            'notes_snapshot' => [],
            'checksum' => hash('sha256', 'sample-snapshot-2'),
        ]);
    }
}
