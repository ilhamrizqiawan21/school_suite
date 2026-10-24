<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcademicImportService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected EnrollmentService $enrollmentService
    ) {}

    /**
     * Memvalidasi data baris siswa sebelum diimpor (Dry-run / Preview).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     valid_rows: list<array<string, mixed>>,
     *     invalid_rows: list<array{row_index: int, errors: list<string>, data: array<string, mixed>}>,
     *     is_valid: bool
     * }
     */
    public function validateStudentRows(int $schoolId, array $rows): array
    {
        $validRows = [];
        $invalidRows = [];
        $seenNis = [];
        $seenNisn = [];

        // Ambil existing NIS & NISN di sekolah untuk pencocokan cepat
        $existingNis = Student::where('school_id', $schoolId)->pluck('nis')->flip()->toArray();
        $existingNisn = Student::where('school_id', $schoolId)->whereNotNull('nisn')->pluck('nisn')->flip()->toArray();

        foreach ($rows as $index => $row) {
            $rowIndex = $index + 1;
            $errors = [];

            // 1. Validasi NIS
            $nis = isset($row['nis']) ? trim((string) $row['nis']) : '';
            if ($nis === '') {
                $errors[] = 'NIS wajib diisi.';
            } elseif (isset($existingNis[$nis])) {
                $errors[] = "NIS [{$nis}] sudah terdaftar di sekolah.";
            } elseif (isset($seenNis[$nis])) {
                $errors[] = "NIS [{$nis}] terduplikasi di dalam berkas impor (baris {$seenNis[$nis]}).";
            } else {
                $seenNis[$nis] = $rowIndex;
            }

            // 2. Validasi Nama
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                $errors[] = 'Nama siswa wajib diisi.';
            }

            // 3. Validasi Jenis Kelamin
            $gender = isset($row['gender']) ? strtoupper(trim((string) $row['gender'])) : 'L';
            if (! in_array($gender, ['L', 'P'], true)) {
                $errors[] = 'Jenis kelamin harus "L" atau "P".';
            }

            // 4. Validasi NISN (opsional, namun jika diisi harus valid)
            $nisn = isset($row['nisn']) && trim((string) $row['nisn']) !== '' ? trim((string) $row['nisn']) : null;
            if ($nisn !== null) {
                if (isset($existingNisn[$nisn])) {
                    $errors[] = "NISN [{$nisn}] sudah terdaftar di sekolah.";
                } elseif (isset($seenNisn[$nisn])) {
                    $errors[] = "NISN [{$nisn}] terduplikasi di dalam berkas impor (baris {$seenNisn[$nisn]}).";
                } else {
                    $seenNisn[$nisn] = $rowIndex;
                }
            }

            if (! empty($errors)) {
                $invalidRows[] = [
                    'row_index' => $rowIndex,
                    'errors' => $errors,
                    'data' => $row,
                ];
            } else {
                $validRows[] = [
                    'row_index' => $rowIndex,
                    'school_id' => $schoolId,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'name' => $name,
                    'gender' => $gender,
                    'birth_place' => $row['birth_place'] ?? null,
                    'birth_date' => $row['birth_date'] ?? null,
                    'religion' => $row['religion'] ?? null,
                    'address' => $row['address'] ?? null,
                    'parent_phone' => $row['parent_phone'] ?? null,
                    'parent_name' => $row['parent_name'] ?? null,
                    'status' => 'active',
                ];
            }
        }

        return [
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'is_valid' => empty($invalidRows),
        ];
    }

    /**
     * Mengimpor sekumpulan siswa secara transaksional atomik.
     * Jika terjadi kesalahan atau validasi gagal, seluruh perubahan di-rollback.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     imported_count: int,
     *     students: list<Student>
     * }
     */
    public function importStudentsBatch(
        int $schoolId,
        array $rows,
        ?int $enrollTermId = null,
        ?int $enrollClassGroupId = null,
        ?User $actor = null
    ): array {
        $validation = $this->validateStudentRows($schoolId, $rows);

        if (! $validation['is_valid']) {
            $firstError = $validation['invalid_rows'][0]['errors'][0] ?? 'Data tidak valid.';
            $firstRow = $validation['invalid_rows'][0]['row_index'] ?? 1;
            throw new InvalidArgumentException("Validasi impor siswa gagal pada baris {$firstRow}: {$firstError}");
        }

        return DB::transaction(function () use ($schoolId, $validation, $enrollTermId, $enrollClassGroupId, $actor) {
            $importedStudents = [];

            foreach ($validation['valid_rows'] as $rowData) {
                unset($rowData['row_index']);

                $student = Student::create($rowData);
                $importedStudents[] = $student;

                // Jika ditentukan enrollment term dan kelas, daftarkan sekaligus
                if ($enrollTermId !== null && $enrollClassGroupId !== null) {
                    $this->enrollmentService->enrollStudent(
                        termId: $enrollTermId,
                        classGroupId: $enrollClassGroupId,
                        studentId: $student->id,
                        rollNumber: null,
                        actorId: $actor?->id
                    );
                }
            }

            $count = count($importedStudents);

            $this->auditLogger->log(
                eventType: 'academic.students.imported_batch',
                targetType: 'student_batch',
                targetId: (string) $count,
                newValues: [
                    'imported_count' => $count,
                    'enroll_term_id' => $enrollTermId,
                    'enroll_class_group_id' => $enrollClassGroupId,
                ],
                oldValues: null,
                actor: $actor,
                schoolId: $schoolId
            );

            return [
                'imported_count' => $count,
                'students' => $importedStudents,
            ];
        });
    }

    /**
     * Memvalidasi data baris guru sebelum diimpor.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     valid_rows: list<array<string, mixed>>,
     *     invalid_rows: list<array{row_index: int, errors: list<string>, data: array<string, mixed>}>,
     *     is_valid: bool
     * }
     */
    public function validateTeacherRows(int $schoolId, array $rows): array
    {
        $validRows = [];
        $invalidRows = [];
        $seenNip = [];

        $existingNip = Teacher::where('school_id', $schoolId)->whereNotNull('nip')->pluck('nip')->flip()->toArray();

        foreach ($rows as $index => $row) {
            $rowIndex = $index + 1;
            $errors = [];

            // 1. Validasi Nama
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                $errors[] = 'Nama guru wajib diisi.';
            }

            // 2. Validasi NIP (opsional)
            $nip = isset($row['nip']) && trim((string) $row['nip']) !== '' ? trim((string) $row['nip']) : null;
            if ($nip !== null) {
                if (isset($existingNip[$nip])) {
                    $errors[] = "NIP [{$nip}] sudah terdaftar di sekolah.";
                } elseif (isset($seenNip[$nip])) {
                    $errors[] = "NIP [{$nip}] terduplikasi di dalam berkas impor (baris {$seenNip[$nip]}).";
                } else {
                    $seenNip[$nip] = $rowIndex;
                }
            }

            // 3. Validasi Jenis Kelamin
            $gender = isset($row['gender']) ? strtoupper(trim((string) $row['gender'])) : 'L';
            if (! in_array($gender, ['L', 'P'], true)) {
                $errors[] = 'Jenis kelamin harus "L" atau "P".';
            }

            // 4. Validasi Email
            $email = isset($row['email']) && trim((string) $row['email']) !== '' ? trim((string) $row['email']) : null;
            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format email [{$email}] tidak valid.";
            }

            if (! empty($errors)) {
                $invalidRows[] = [
                    'row_index' => $rowIndex,
                    'errors' => $errors,
                    'data' => $row,
                ];
            } else {
                $validRows[] = [
                    'row_index' => $rowIndex,
                    'school_id' => $schoolId,
                    'nip' => $nip,
                    'name' => $name,
                    'gender' => $gender,
                    'phone' => $row['phone'] ?? null,
                    'email' => $email,
                    'employment_status' => $row['employment_status'] ?? 'GTT',
                    'is_active' => true,
                ];
            }
        }

        return [
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'is_valid' => empty($invalidRows),
        ];
    }

    /**
     * Mengimpor guru secara transaksional atomik.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     imported_count: int,
     *     teachers: list<Teacher>
     * }
     */
    public function importTeachersBatch(int $schoolId, array $rows, ?User $actor = null): array
    {
        $validation = $this->validateTeacherRows($schoolId, $rows);

        if (! $validation['is_valid']) {
            $firstError = $validation['invalid_rows'][0]['errors'][0] ?? 'Data tidak valid.';
            $firstRow = $validation['invalid_rows'][0]['row_index'] ?? 1;
            throw new InvalidArgumentException("Validasi impor guru gagal pada baris {$firstRow}: {$firstError}");
        }

        return DB::transaction(function () use ($schoolId, $validation, $actor) {
            $importedTeachers = [];

            foreach ($validation['valid_rows'] as $rowData) {
                unset($rowData['row_index']);
                $teacher = Teacher::create($rowData);
                $importedTeachers[] = $teacher;
            }

            $count = count($importedTeachers);

            $this->auditLogger->log(
                eventType: 'academic.teachers.imported_batch',
                targetType: 'teacher_batch',
                targetId: (string) $count,
                newValues: ['imported_count' => $count],
                oldValues: null,
                actor: $actor,
                schoolId: $schoolId
            );

            return [
                'imported_count' => $count,
                'teachers' => $importedTeachers,
            ];
        });
    }

    /**
     * Membaca string CSV menjadi array of associative rows.
     *
     * @return list<array<string, string>>
     */
    public function parseCsvString(string $csvContent, array $requiredHeaders = []): array
    {
        if (trim($csvContent) === '') {
            return [];
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", trim($csvContent)));

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($col) => strtolower(trim($col)), $header);

        if (in_array('', $header, true) || count($header) !== count(array_unique($header))) {
            throw new InvalidArgumentException('Header CSV kosong atau memiliki kolom duplikat.');
        }

        $missingHeaders = array_diff($requiredHeaders, $header);
        if (! empty($missingHeaders)) {
            throw new InvalidArgumentException(
                'Header CSV tidak lengkap. Kolom wajib: '.implode(', ', $missingHeaders).'.'
            );
        }

        $records = [];
        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $row = str_getcsv($trimmed);
            if (count($row) !== count($header)) {
                throw new InvalidArgumentException(
                    'Format CSV tidak valid pada baris data '.($index + 1).': jumlah kolom tidak sesuai header.'
                );
            }

            $records[] = array_combine($header, $row);
        }

        return $records;
    }
}
