<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Shared\Audit\AuditLogger;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AcademicImportService
{
    public const MAX_STUDENT_IMPORT_ROWS = 1000;

    public const MAX_STUDENT_IMPORT_COLUMNS = 64;

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
    public function validateStudentRows(int $schoolId, array $rows, ?string $targetClassGroupName = null): array
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
            $gender = $this->normalizeGender($row['gender'] ?? null);
            if (! in_array($gender, ['L', 'P'], true)) {
                $errors[] = 'Jenis kelamin harus "L" atau "P".';
            }

            $status = $this->normalizeStudentStatus($row['status'] ?? null);
            if ($status === null) {
                $errors[] = 'Status siswa tidak dikenali. Gunakan Aktif, Lulus, Pindah, atau Keluar.';
            }

            $sourceClass = trim((string) ($row['source_class'] ?? ''));
            if ($sourceClass !== '') {
                if ($status !== 'active') {
                    $errors[] = 'Kelas sumber untuk siswa nonaktif tidak dapat dipetakan ke rombel aktif. Kosongkan kolom Kelas sebelum impor.';
                } elseif ($status === 'active' && $targetClassGroupName === null) {
                    $errors[] = 'Kolom Kelas berisi data. Pilih kelas dan periode tujuan sebelum melanjutkan.';
                } elseif ($status === 'active' && $this->normalizeClassName($sourceClass) !== $this->normalizeClassName($targetClassGroupName)) {
                    $errors[] = "Kelas sumber [{$sourceClass}] tidak cocok dengan kelas tujuan [{$targetClassGroupName}].";
                }
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
                    'status' => $status,
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
                if ($enrollTermId !== null && $enrollClassGroupId !== null && $rowData['status'] === 'active') {
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

    /** @return list<array<string, mixed>> */
    public function parseTeacherImportFile(string $path, string $extension): array
    {
        $table = $extension === 'xlsx' ? $this->readBoundedStudentSpreadsheet($path) : $this->readStudentCsvFile($path);
        $header = array_shift($table) ?? [];
        $header = array_map(fn ($value) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value) ?? ''), $header);
        if (count($header) !== count(array_unique($header)) || array_diff(['nip', 'name', 'gender'], $header) !== []) {
            throw new InvalidArgumentException('Header wajib nip, name, dan gender harus tersedia dan tidak boleh berulang.');
        }
        $rows = [];
        foreach ($table as $index => $values) {
            if (! collect($values)->contains(fn ($value) => trim((string) $value) !== '')) {
                continue;
            }
            if (count($values) !== count($header)) {
                throw new InvalidArgumentException('Jumlah kolom tidak cocok dengan header pada baris '.($index + 2).'.');
            }
            $row = array_combine($header, $values);
            if (array_key_exists('birth_date', $row)) {
                $row['birth_date'] = $this->normalizeImportDate($row['birth_date']);
            }
            $rows[] = $row;
        }
        if (count($rows) > 1000) {
            throw new InvalidArgumentException('Maksimal 1.000 guru per berkas.');
        }

        return $rows;
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
        if (count($rows) > 1000) {
            throw new InvalidArgumentException('Maksimal 1.000 guru per berkas.');
        }
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

            $row['birth_date'] = $this->normalizeImportDate($row['birth_date'] ?? null);

            // 4. Validasi Email
            $email = isset($row['email']) && trim((string) $row['email']) !== '' ? trim((string) $row['email']) : null;
            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format email [{$email}] tidak valid.";
            }

            $fieldValidation = Validator::make($row, [
                'name' => ['required', 'string', 'max:150'],
                'nip' => ['nullable', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
                'employment_status' => ['nullable', 'string', 'max:50'],
            ]);
            $errors = array_merge($errors, $fieldValidation->errors()->all());

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
                    'birth_date' => $row['birth_date'] ?? null,
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

    /**
     * Parse the School Suite student CSV or eMadrasah student XLSX/CSV export.
     * Unsupported data is reported as warnings instead of being silently mapped.
     *
     * @return array{rows: list<array<string, mixed>>, warnings: list<string>, has_source_class: bool}
     */
    public function parseStudentImportFile(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        if ($extension === 'xlsx') {
            $table = $this->readBoundedStudentSpreadsheet($path);
        } elseif (in_array($extension, ['csv', 'txt'], true)) {
            $table = $this->readStudentCsvFile($path);
        } else {
            throw new InvalidArgumentException('Berkas harus berupa CSV atau XLSX.');
        }

        return $this->normalizeStudentImportTable($table);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, warnings: list<string>, has_source_class: bool}
     */
    public function parseStudentImportCsvString(string $content): array
    {
        if (trim($content) === '') {
            throw new InvalidArgumentException('Berkas CSV kosong.');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        try {
            return $this->normalizeStudentImportTable($this->readDelimitedRows($stream));
        } finally {
            fclose($stream);
        }
    }

    /** @return list<list<mixed>> */
    private function readBoundedStudentSpreadsheet(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= AcademicImportService::MAX_STUDENT_IMPORT_ROWS + 2
                    && Coordinate::columnIndexFromString($columnAddress) <= AcademicImportService::MAX_STUDENT_IMPORT_COLUMNS;
            }
        });

        try {
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheet(0);
            $lastColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

            if ($lastColumnIndex > self::MAX_STUDENT_IMPORT_COLUMNS) {
                throw new InvalidArgumentException('Berkas XLSX memiliki lebih dari 64 kolom dan tidak dapat dipreview dengan aman.');
            }

            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow > self::MAX_STUDENT_IMPORT_ROWS + 2) {
                throw new InvalidArgumentException('Berkas berisi lebih dari 1.000 baris data. Pecah menjadi beberapa berkas.');
            }

            $table = [];
            for ($row = 1; $row <= $lastRow; $row++) {
                $values = [];
                for ($column = 1; $column <= $lastColumnIndex; $column++) {
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        throw new InvalidArgumentException("Rumus tidak diizinkan pada berkas impor (baris {$row}).");
                    }

                    $values[] = $cell->getFormattedValue();
                }
                $table[] = $values;
            }

            $spreadsheet->disconnectWorksheets();

            return $table;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable) {
            throw new InvalidArgumentException('Berkas XLSX tidak dapat dibaca. Pastikan berkas tidak rusak dan hanya berisi data siswa.');
        }
    }

    /** @return list<list<string>> */
    private function readStudentCsvFile(string $path): array
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new InvalidArgumentException('Berkas CSV tidak dapat dibaca.');
        }

        try {
            return $this->readDelimitedRows($stream);
        } finally {
            fclose($stream);
        }
    }

    /** @return list<list<string>> */
    private function readDelimitedRows($stream): array
    {
        $firstLine = fgets($stream);
        if ($firstLine === false) {
            return [];
        }
        rewind($stream);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $rows = [];

        while (($row = fgetcsv($stream, separator: $delimiter)) !== false) {
            $rows[] = array_map(static fn ($value): string => (string) ($value ?? ''), $row);
            if (count($rows) > self::MAX_STUDENT_IMPORT_ROWS + 2) {
                throw new InvalidArgumentException('Berkas berisi lebih dari 1.000 baris data. Pecah menjadi beberapa berkas.');
            }
        }

        return $rows;
    }

    /** @param list<list<mixed>> $table
     * @return array{rows: list<array<string, mixed>>, warnings: list<string>, has_source_class: bool}
     */
    private function normalizeStudentImportTable(array $table): array
    {
        $header = null;
        $headerRow = 0;
        foreach ($table as $index => $row) {
            if (collect($row)->contains(fn ($value): bool => trim((string) $value) !== '')) {
                $header = $row;
                $headerRow = $index + 1;
                break;
            }
        }

        if ($header === null) {
            throw new InvalidArgumentException('Berkas tidak memiliki baris header.');
        }

        $aliases = $this->studentImportHeaderAliases();
        $mappedHeaders = [];
        $ignoredHeaders = [];
        foreach ($header as $index => $label) {
            $label = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $label) ?? (string) $label);
            $normalized = $this->normalizeImportHeader($label);
            if ($normalized === '') {
                continue;
            }
            if (! isset($aliases[$normalized])) {
                $ignoredHeaders[$index] = $label;

                continue;
            }

            $field = $aliases[$normalized];
            if (isset($mappedHeaders[$field])) {
                throw new InvalidArgumentException("Header {$label} bertabrakan dengan kolom lain yang juga dipetakan ke {$field}.");
            }
            $mappedHeaders[$field] = $index;
        }

        foreach (['nis', 'name', 'gender'] as $required) {
            if (! isset($mappedHeaders[$required])) {
                throw new InvalidArgumentException('Header wajib tidak lengkap. Diperlukan NIS, Nama Lengkap, dan L/P.');
            }
        }

        $warnings = [];
        $ignored = [];
        if (isset($mappedHeaders['nik'])) {
            $ignored[] = 'NIK';
        }
        foreach ($ignoredHeaders as $columnIndex => $label) {
            if (collect(array_slice($table, $headerRow))->contains(fn ($row): bool => trim((string) ($row[$columnIndex] ?? '')) !== '')) {
                $ignored[] = $label;
            }
        }
        $ignored = array_values(array_unique($ignored));
        if ($ignored !== []) {
            $warnings[] = 'Kolom '.implode(', ', $ignored).' terdeteksi tetapi tidak disimpan oleh data siswa School Suite.';
        }

        $hasSourceClass = isset($mappedHeaders['source_class']);
        $rows = [];
        foreach (array_slice($table, $headerRow) as $cells) {
            if (! collect($cells)->contains(fn ($value): bool => trim((string) $value) !== '')) {
                continue;
            }

            $row = [];
            foreach ($mappedHeaders as $field => $columnIndex) {
                $value = $cells[$columnIndex] ?? null;
                if ($field === 'birth_date') {
                    $value = $this->normalizeImportDate($value);
                } else {
                    $value = trim((string) ($value ?? ''));
                }
                $row[$field] = $value === '' ? null : $value;
            }

            if (isset($row['nisn']) && is_string($row['nisn']) && ctype_digit($row['nisn']) && strlen($row['nisn']) < 10) {
                $row['nisn'] = str_pad($row['nisn'], 10, '0', STR_PAD_LEFT);
            }
            $rows[] = $row;
            if (count($rows) > self::MAX_STUDENT_IMPORT_ROWS) {
                throw new InvalidArgumentException('Berkas berisi lebih dari 1.000 baris data. Pecah menjadi beberapa berkas.');
            }
        }

        if ($rows === []) {
            throw new InvalidArgumentException('Berkas tidak berisi baris data.');
        }

        return ['rows' => $rows, 'warnings' => $warnings, 'has_source_class' => $hasSourceClass];
    }

    /** @return array<string, string> */
    private function studentImportHeaderAliases(): array
    {
        $aliases = [];
        foreach ([
            'nis' => ['nis'],
            'nisn' => ['nisn'],
            'nik' => ['nik'],
            'name' => ['name', 'nama', 'nama lengkap'],
            'source_class' => ['kelas'],
            'gender' => ['gender', 'l/p', 'jenis kelamin', 'jk'],
            'birth_place' => ['birth place', 'tempat lahir'],
            'birth_date' => ['birth date', 'tanggal lahir'],
            'religion' => ['religion', 'agama'],
            'address' => ['address', 'alamat'],
            'parent_name' => ['parent name', 'nama orang tua'],
            'parent_phone' => ['parent phone', 'no. hp', 'no hp', 'hp', 'telepon'],
            'status' => ['status'],
        ] as $field => $labels) {
            foreach ($labels as $label) {
                $aliases[$this->normalizeImportHeader($label)] = $field;
            }
        }

        return $aliases;
    }

    private function normalizeImportHeader(string $header): string
    {
        return preg_replace('/[^a-z0-9]+/u', '', mb_strtolower(trim($header))) ?? '';
    }

    private function normalizeGender(mixed $gender): string
    {
        return match (mb_strtolower(trim((string) $gender))) {
            'l', 'lk', 'laki-laki', 'laki laki', 'pria' => 'L',
            'p', 'pr', 'perempuan', 'wanita' => 'P',
            default => strtoupper(trim((string) $gender)),
        };
    }

    private function normalizeStudentStatus(mixed $status): ?string
    {
        return match (mb_strtolower(trim((string) ($status ?? '')))) {
            '', 'aktif', 'active' => 'active',
            'lulus', 'graduated' => 'graduated',
            'pindah', 'transferred' => 'transferred',
            'keluar', 'dropped_out', 'dropped out' => 'dropped_out',
            default => null,
        };
    }

    private function normalizeClassName(string $name): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name));
    }

    private function normalizeImportDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value) && (float) $value >= 10000 && (float) $value <= 80000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $date = trim((string) ($value ?? ''));
        if ($date === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!'.$format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d');
            }
        }

        return $date;
    }
}
