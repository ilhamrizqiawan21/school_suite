<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Application\Services\EnrollmentService;
use App\Modules\Academic\Domain\Enums\EnrollmentStatusEnum;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private EnrollmentService $enrollmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $this->enrollmentService = app(EnrollmentService::class);
    }

    public function test_can_enroll_single_student_and_logs_audit(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'nis' => 'NIS-001',
            'name' => 'Ahmad Fikri (Sintetis)',
            'gender' => 'L',
        ]);

        $enrollment = $this->enrollmentService->enrollStudent(
            termId: $term->id,
            classGroupId: $class7A->id,
            studentId: $student->id,
            rollNumber: 1
        );

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'term_id' => $term->id,
            'class_group_id' => $class7A->id,
            'student_id' => $student->id,
            'roll_number' => 1,
            'status' => EnrollmentStatusEnum::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.enrollment.created',
            'target_type' => 'enrollment',
            'target_id' => (string) $enrollment->id,
        ]);
    }

    public function test_cannot_enroll_student_in_two_classes_in_same_term(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $class7B = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7B',
            'capacity' => 32,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'nis' => 'NIS-002',
            'name' => 'Citra Lestari (Sintetis)',
            'gender' => 'P',
        ]);

        $this->enrollmentService->enrollStudent(
            termId: $term->id,
            classGroupId: $class7A->id,
            studentId: $student->id
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah terdaftar di kelas lain pada semester ini');

        $this->enrollmentService->enrollStudent(
            termId: $term->id,
            classGroupId: $class7B->id,
            studentId: $student->id
        );
    }

    public function test_ss_d_004_historical_enrollment_preserved_across_grade_promotion(): void
    {
        // Tahun 1: 2025/2026
        $year1 = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => false,
        ]);

        $term1Year1 = Term::create([
            'academic_year_id' => $year1->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => false,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year1->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        // Tahun 2: 2026/2027
        $year2 = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-20',
            'is_active' => true,
        ]);

        $term1Year2 = Term::create([
            'academic_year_id' => $year2->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => true,
        ]);

        $class8A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year2->id,
            'grade_level' => 8,
            'name' => '8A',
            'capacity' => 32,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'nis' => 'NIS-003',
            'name' => 'Dewi Rahmawati (Sintetis)',
            'gender' => 'P',
        ]);

        // 1. Enrollment di kelas 7 pada tahun 2025/2026
        $enrollmentYear1 = $this->enrollmentService->enrollStudent(
            termId: $term1Year1->id,
            classGroupId: $class7A->id,
            studentId: $student->id,
            rollNumber: 5
        );

        // 2. Kenaikan kelas ke 8A pada tahun 2026/2027
        $promotions = [
            [
                'student_id' => $student->id,
                'target_class_group_id' => $class8A->id,
                'roll_number' => 4,
            ],
        ];

        $newEnrollments = $this->enrollmentService->promoteOrAdvanceStudents(
            sourceTermId: $term1Year1->id,
            targetTermId: $term1Year2->id,
            promotions: $promotions
        );

        $this->assertCount(1, $newEnrollments);

        // BUKTI SS-D-004:
        // Siswa memiliki 2 record enrollment terpisah
        $allEnrollments = $student->enrollments()->with(['term.academicYear', 'classGroup'])->get();
        $this->assertCount(2, $allEnrollments);

        // Histori lama tetap 7A pada semester 2025/2026
        $histYear1 = $allEnrollments->firstWhere('term_id', $term1Year1->id);
        $this->assertNotNull($histYear1);
        $this->assertEquals('7A', $histYear1->classGroup->name);
        $this->assertEquals(7, $histYear1->classGroup->grade_level);
        $this->assertEquals('2025/2026', $histYear1->term->academicYear->name);

        // Rekor baru adalah 8A pada semester 2026/2027
        $histYear2 = $allEnrollments->firstWhere('term_id', $term1Year2->id);
        $this->assertNotNull($histYear2);
        $this->assertEquals('8A', $histYear2->classGroup->name);
        $this->assertEquals(8, $histYear2->classGroup->grade_level);
        $this->assertEquals('2026/2027', $histYear2->term->academicYear->name);
    }

    public function test_can_transfer_student_to_another_class_in_same_term(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $class7B = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7B',
            'capacity' => 32,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'nis' => 'NIS-004',
            'name' => 'Eko Prasetyo (Sintetis)',
            'gender' => 'L',
        ]);

        $enrollment = $this->enrollmentService->enrollStudent(
            termId: $term->id,
            classGroupId: $class7A->id,
            studentId: $student->id
        );

        $transferred = $this->enrollmentService->transferClass(
            termId: $term->id,
            studentId: $student->id,
            newClassGroupId: $class7B->id
        );

        $this->assertEquals($class7B->id, $transferred->class_group_id);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.enrollment.transferred',
            'target_type' => 'enrollment',
            'target_id' => (string) $enrollment->id,
        ]);
    }
}
