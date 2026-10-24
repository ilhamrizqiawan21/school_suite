<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicMigrationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);
    }

    public function test_all_academic_tables_are_created(): void
    {
        $expectedTables = [
            'academic_years',
            'terms',
            'subjects',
            'teachers',
            'class_groups',
            'students',
            'enrollments',
            'teaching_assignments',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table [{$table}] should exist in database schema."
            );
        }
    }

    public function test_students_table_does_not_contain_class_id_column(): void
    {
        // SS-D-004 invariant: students is identity; class membership is historical via enrollments
        $this->assertFalse(
            Schema::hasColumn('students', 'class_id'),
            'students table must not have direct class_id column'
        );

        $this->assertFalse(
            Schema::hasColumn('students', 'class_group_id'),
            'students table must not have direct class_group_id column'
        );
    }

    public function test_academic_year_and_terms_relationship_and_uniqueness(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term1 = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $term2 = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Genap',
            'semester_type' => 'even',
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-20',
            'is_active' => false,
        ]);

        $this->assertCount(2, $year->terms);
        $this->assertEquals($year->id, $term1->academicYear->id);

        // Uji keunikan nama semester dalam 1 tahun ajaran
        $this->expectException(QueryException::class);
        Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
        ]);
    }

    public function test_students_and_historical_enrollment(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term1 = Term::create([
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
            'nis' => 'S-001',
            'nisn' => '0012345678',
            'name' => 'Budi Santoso (Sintetis)',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'term_id' => $term1->id,
            'class_group_id' => $class7A->id,
            'student_id' => $student->id,
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $this->assertEquals('7A', $student->enrollments()->first()->classGroup->name);
        $this->assertCount(1, $class7A->enrollments);

        // Siswa tidak dapat didaftarkan ganda di semester yang sama
        $this->expectException(QueryException::class);
        Enrollment::create([
            'term_id' => $term1->id,
            'class_group_id' => $class7A->id,
            'student_id' => $student->id,
            'roll_number' => 2,
        ]);
    }

    public function test_teaching_assignment_and_teachers(): void
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

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198501012010011001',
            'name' => 'Dr. Suryanto, M.Pd (Sintetis)',
            'gender' => 'L',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'homeroom_teacher_id' => $teacher->id,
            'capacity' => 32,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'Wajib',
        ]);

        $assignment = TeachingAssignment::create([
            'term_id' => $term->id,
            'class_group_id' => $class7A->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 4,
        ]);

        $this->assertEquals($teacher->id, $class7A->homeroomTeacher->id);
        $this->assertEquals('MTK', $assignment->subject->code);
        $this->assertEquals($teacher->id, $assignment->teacher->id);
    }

    public function test_migrations_can_be_rolled_back_cleanly(): void
    {
        // Jalankan rollback
        Artisan::call('migrate:rollback');

        $academicTables = [
            'teaching_assignments',
            'enrollments',
            'students',
            'class_groups',
            'teachers',
            'subjects',
            'terms',
            'academic_years',
        ];

        foreach ($academicTables as $table) {
            $this->assertFalse(
                Schema::hasTable($table),
                "Table [{$table}] should be dropped after rollback."
            );
        }
    }
}
