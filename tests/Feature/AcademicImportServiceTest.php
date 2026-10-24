<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Application\Services\AcademicImportService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AcademicImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $this->importService = app(AcademicImportService::class);
    }

    public function test_can_parse_csv_string_properly(): void
    {
        $csv = "nis,name,gender,nisn\n1001,Andi Saputra,L,00112233\n1002,Bunga Mawar,P,00112234\n";
        $parsed = $this->importService->parseCsvString($csv);

        $this->assertCount(2, $parsed);
        $this->assertEquals('1001', $parsed[0]['nis']);
        $this->assertEquals('Andi Saputra', $parsed[0]['name']);
        $this->assertEquals('L', $parsed[0]['gender']);
        $this->assertEquals('Bunga Mawar', $parsed[1]['name']);
    }

    public function test_rejects_csv_rows_with_mismatched_column_counts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('baris data 2');

        $this->importService->parseCsvString(
            "nis,name,gender\n1001,Andi Saputra,L\n1002,Bunga Mawar\n",
            ['nis', 'name', 'gender']
        );
    }

    public function test_rejects_csv_with_missing_required_headers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kolom wajib: gender');

        $this->importService->parseCsvString(
            "nis,name\n1001,Andi Saputra\n",
            ['nis', 'name', 'gender']
        );
    }

    public function test_validate_student_rows_detects_duplicate_nis(): void
    {
        // Masukkan satu siswa yang sudah ada di database
        Student::create([
            'school_id' => $this->school->id,
            'nis' => '1001',
            'name' => 'Siswa Lama',
            'gender' => 'L',
        ]);

        $batch = [
            ['nis' => '1001', 'name' => 'Siswa Bentrok Database', 'gender' => 'L'],
            ['nis' => '1002', 'name' => 'Siswa Unik', 'gender' => 'P'],
            ['nis' => '1002', 'name' => 'Siswa Bentrok Batch', 'gender' => 'L'],
            ['nis' => '', 'name' => 'Siswa Tanpa NIS', 'gender' => 'P'],
        ];

        $result = $this->importService->validateStudentRows($this->school->id, $batch);

        $this->assertFalse($result['is_valid']);
        $this->assertCount(1, $result['valid_rows']); // hanya baris ke-2 (1002 yang pertama) yang valid sebelum duplikatnya datang
        $this->assertCount(3, $result['invalid_rows']);

        // Baris 1: duplikat database
        $this->assertStringContainsString('sudah terdaftar di sekolah', $result['invalid_rows'][0]['errors'][0]);
        // Baris 3: duplikat dalam batch
        $this->assertStringContainsString('terduplikasi di dalam berkas impor', $result['invalid_rows'][1]['errors'][0]);
        // Baris 4: NIS wajib diisi
        $this->assertStringContainsString('NIS wajib diisi', $result['invalid_rows'][2]['errors'][0]);
    }

    public function test_import_students_batch_atomic_rollback_on_failure(): void
    {
        $batch = [
            ['nis' => '2001', 'name' => 'Siswa A', 'gender' => 'L'],
            ['nis' => '', 'name' => 'Siswa Gagal', 'gender' => 'L'], // baris cacat
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validasi impor siswa gagal pada baris 2');

        try {
            $this->importService->importStudentsBatch($this->school->id, $batch);
        } finally {
            // Bukti atomik: Siswa A sama sekali tidak tersimpan di database
            $this->assertDatabaseMissing('students', ['nis' => '2001']);
        }
    }

    public function test_import_students_batch_success_with_auto_enrollment(): void
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

        $batch = [
            ['nis' => '3001', 'name' => 'Cantika Putri (Sintetis)', 'gender' => 'P'],
            ['nis' => '3002', 'name' => 'Danang Wijaya (Sintetis)', 'gender' => 'L'],
        ];

        $result = $this->importService->importStudentsBatch(
            schoolId: $this->school->id,
            rows: $batch,
            enrollTermId: $term->id,
            enrollClassGroupId: $class7A->id
        );

        $this->assertEquals(2, $result['imported_count']);
        $this->assertDatabaseHas('students', ['nis' => '3001']);
        $this->assertDatabaseHas('students', ['nis' => '3002']);

        // Terbukti auto-enrolled di kelas 7A
        $this->assertDatabaseHas('enrollments', [
            'term_id' => $term->id,
            'class_group_id' => $class7A->id,
            'student_id' => $result['students'][0]->id,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.students.imported_batch',
            'target_type' => 'student_batch',
            'target_id' => '2',
        ]);
    }

    public function test_import_teachers_batch_success_and_validates(): void
    {
        $batch = [
            ['nip' => '199001012015011001', 'name' => 'Hendra Setiawan, M.Pd (Sintetis)', 'gender' => 'L', 'email' => 'hendra@example.test'],
            ['nip' => '199202022016022002', 'name' => 'Indah Permata, S.Pd (Sintetis)', 'gender' => 'P', 'email' => 'indah@example.test'],
        ];

        $result = $this->importService->importTeachersBatch($this->school->id, $batch);

        $this->assertEquals(2, $result['imported_count']);
        $this->assertDatabaseHas('teachers', ['nip' => '199001012015011001']);
        $this->assertDatabaseHas('teachers', ['nip' => '199202022016022002']);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.teachers.imported_batch',
            'target_type' => 'teacher_batch',
            'target_id' => '2',
        ]);
    }
}
