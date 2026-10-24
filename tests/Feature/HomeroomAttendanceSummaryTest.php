<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Homeroom\Application\Services\HomeroomAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeroomAttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Term $term;

    private ClassGroup $class7A;

    private HomeroomAttendanceService $homeroomService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
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

        $this->homeroomService = app(HomeroomAttendanceService::class);
    }

    public function test_accurately_calculates_class_attendance_and_flags_early_warnings(): void
    {
        // Siswa 1: Hadir 10 hari sempurna (100%)
        $student1 = Student::create(['school_id' => $this->school->id, 'nis' => '1001', 'name' => 'Budi Santoso', 'gender' => 'L']);
        $enr1 = Enrollment::create(['term_id' => $this->term->id, 'class_group_id' => $this->class7A->id, 'student_id' => $student1->id, 'roll_number' => 1]);

        for ($i = 1; $i <= 10; $i++) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'enrollment_id' => $enr1->id,
                'date' => "2025-08-{$i}",
                'type' => 'daily',
                'status' => 'present',
            ]);
        }

        // Siswa 2: Alpa 3 hari berturut-turut (Harus kena Early Warning: Alpa >= 3)
        $student2 = Student::create(['school_id' => $this->school->id, 'nis' => '1002', 'name' => 'Cahyo Utomo', 'gender' => 'L']);
        $enr2 = Enrollment::create(['term_id' => $this->term->id, 'class_group_id' => $this->class7A->id, 'student_id' => $student2->id, 'roll_number' => 2]);

        for ($i = 1; $i <= 7; $i++) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'enrollment_id' => $enr2->id,
                'date' => "2025-08-{$i}",
                'type' => 'daily',
                'status' => 'present',
            ]);
        }
        for ($i = 8; $i <= 10; $i++) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'enrollment_id' => $enr2->id,
                'date' => "2025-08-{$i}",
                'type' => 'daily',
                'status' => 'absent',
            ]);
        }

        // Siswa 3: Sakit 3 hari, Izin 1 hari, Hadir 6 hari (Kehadiran 6/10 = 60%, < 85%) -> Kena Early Warning
        $student3 = Student::create(['school_id' => $this->school->id, 'nis' => '1003', 'name' => 'Diana Putri', 'gender' => 'P']);
        $enr3 = Enrollment::create(['term_id' => $this->term->id, 'class_group_id' => $this->class7A->id, 'student_id' => $student3->id, 'roll_number' => 3]);

        for ($i = 1; $i <= 6; $i++) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'enrollment_id' => $enr3->id,
                'date' => "2025-08-{$i}",
                'type' => 'daily',
                'status' => 'present',
            ]);
        }
        for ($i = 7; $i <= 9; $i++) {
            AttendanceRecord::create([
                'school_id' => $this->school->id,
                'enrollment_id' => $enr3->id,
                'date' => "2025-08-{$i}",
                'type' => 'daily',
                'status' => 'sick',
            ]);
        }
        AttendanceRecord::create([
            'school_id' => $this->school->id,
            'enrollment_id' => $enr3->id,
            'date' => '2025-08-10',
            'type' => 'daily',
            'status' => 'permitted',
        ]);

        $summary = $this->homeroomService->getClassAttendanceSummary($this->term->id, $this->class7A->id);

        $this->assertEquals(3, $summary['total_students']);
        $this->assertEquals(2, $summary['students_needing_attention_count']);

        // Siswa 1 normal
        $s1 = collect($summary['students'])->firstWhere('student_id', $student1->id);
        $this->assertEquals(100.0, $s1['attendance_percentage']);
        $this->assertFalse($s1['needs_attention']);

        // Siswa 2 kena Alpa >= 3
        $s2 = collect($summary['students'])->firstWhere('student_id', $student2->id);
        $this->assertEquals(70.0, $s2['attendance_percentage']);
        $this->assertTrue($s2['needs_attention']);
        $this->assertStringContainsString('Alpa telah mencapai 3 hari', $s2['attention_reason']);

        // Siswa 3 kena persentase rendah
        $s3 = collect($summary['students'])->firstWhere('student_id', $student3->id);
        $this->assertEquals(60.0, $s3['attendance_percentage']);
        $this->assertTrue($s3['needs_attention']);
        $this->assertStringContainsString('Kehadiran rendah (60%)', $s3['attention_reason']);
    }
}
