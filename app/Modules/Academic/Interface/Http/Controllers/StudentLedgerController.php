<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLedgerDetail;
use App\Modules\Academic\Infrastructure\Models\StudentLedgerDocument;
use App\Modules\Academic\Infrastructure\Models\StudentLifecycleEvent;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Assessment\Application\Services\ReportPublishingService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exports\SpreadsheetStyler;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentLedgerController extends Controller
{
    private const MAX_BATCH_PDF_STUDENTS = 60;

    private const MAX_IMPORT_ROWS = 1000;

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AuditLogger $auditLogger,
        private readonly ReportPublishingService $reportPublishingService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorizeManagement($request);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = $this->resolveActiveTerm($schoolId);
        $classes = $this->resolveActiveClasses($schoolId, $activeTerm);
        $filters = $this->validateIndexFilters($request, $classes);

        $baseSchoolQuery = Student::query()->where('school_id', $schoolId);
        $totalStudents = (clone $baseSchoolQuery)->count();
        $completeStudents = $this->applyCompletenessFilter(clone $baseSchoolQuery, 'complete')->count();

        $students = $this->filteredStudentsQuery($schoolId, $activeTerm, $filters)
            ->with(['ledgerDetail'])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Student $student): array {
                $detail = $student->ledgerDetail;
                $completeness = $this->calculateCompleteness($student, $detail);

                return [
                    'id' => (int) $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'gender' => $student->gender,
                    'status' => $student->status,
                    'parent_name' => $student->parent_name,
                    'class_name' => $student->enrollments->first()?->classGroup?->name,
                    'completeness_status' => $completeness['status'],
                    'completeness_percent' => $completeness['percent'],
                    'filled_checkpoints' => $completeness['filled'],
                    'total_checkpoints' => $completeness['total'],
                    'quick_detail' => [
                        'nik' => $detail?->nik,
                        'kk_number' => $detail?->kk_number,
                        'father_name' => $detail?->father_name,
                        'mother_name' => $detail?->mother_name,
                        'previous_school' => $detail?->previous_school,
                        'diploma_number' => $detail?->diploma_number,
                        'accepted_grade' => $detail?->accepted_grade,
                        'accepted_date' => $detail?->accepted_date?->format('Y-m-d'),
                    ],
                ];
            });

        return Inertia::render('Academic/Students/Ledger/Index', [
            'students' => $students,
            'classes' => $classes,
            'active_term' => $activeTerm ? [
                'name' => $activeTerm->name,
                'academic_year' => $activeTerm->academicYear?->name,
            ] : null,
            'summary' => [
                'total' => $totalStudents,
                'complete' => $completeStudents,
                'incomplete' => max(0, $totalStudents - $completeStudents),
            ],
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'class_group_id' => isset($filters['class_group_id']) ? (string) $filters['class_group_id'] : '',
                'completeness' => $filters['completeness'] ?? '',
            ],
        ]);
    }

    public function show(Request $request, int $studentId): InertiaResponse
    {
        $this->authorizeManagement($request);

        $schoolId = (int) $request->user()->school_id;
        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $history = $this->classHistory($student->id, $schoolId);
        $lifecycle = $this->lifecycleHistory($student->id, $schoolId);
        $detail = StudentLedgerDetail::query()->where('school_id', $schoolId)->where('student_id', $student->id)->first();
        $documents = StudentLedgerDocument::query()->where('school_id', $schoolId)->where('student_id', $student->id)
            ->latest('created_at')->get(['id', 'document_type', 'original_name', 'size_bytes', 'created_at']);
        $reports = $this->formatReportSnapshotsForStudent($schoolId, $student->id, $history);
        $completeness = $this->calculateCompleteness($student, $detail);

        return Inertia::render('Academic/Students/Ledger/Show', [
            'student' => [
                'id' => (int) $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'birth_place' => $student->birth_place,
                'birth_date' => $student->birth_date?->format('Y-m-d'),
                'religion' => $student->religion,
                'address' => $student->address,
                'parent_name' => $student->parent_name,
                'parent_phone' => $student->parent_phone,
                'status' => $student->status,
            ],
            'completeness' => $completeness,
            'adjacent_students' => $this->resolveAdjacentStudents($schoolId, $student),
            'class_history' => $history->map(fn (Enrollment $enrollment): array => [
                'id' => (int) $enrollment->id,
                'academic_year' => $enrollment->term?->academicYear?->name,
                'term' => $enrollment->term?->name,
                'class_name' => $enrollment->classGroup?->name,
                'grade_level' => $enrollment->classGroup?->grade_level,
                'roll_number' => $enrollment->roll_number,
                'status' => $enrollment->status,
            ])->values(),
            'lifecycle_history' => $lifecycle->map(fn (StudentLifecycleEvent $event): array => [
                'id' => (int) $event->id,
                'action' => $event->action,
                'effective_date' => $event->effective_date?->format('Y-m-d'),
                'source_class' => $event->sourceEnrollment?->classGroup?->name,
                'target_class' => $event->targetEnrollment?->classGroup?->name,
                'notes' => $event->notes,
            ])->values(),
            'report_history' => $reports,
            'detail' => $detail ? $this->serializeDetail($student->id, $detail) : null,
            'documents' => $documents->map(fn (StudentLedgerDocument $document): array => [
                'id' => (int) $document->id,
                'document_type' => $document->document_type,
                'original_name' => $document->original_name,
                'size_bytes' => $document->size_bytes,
                'created_at' => $document->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function updateDetails(Request $request, int $studentId): RedirectResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);

        $detailRules = $this->detailValidationRules();
        $coreRules = [];
        if ($request->boolean('update_core_identity')) {
            $coreRules = [
                'name' => ['required', 'string', 'max:150'],
                'nisn' => ['nullable', 'string', 'max:30', Rule::unique('students', 'nisn')->where('school_id', $schoolId)->ignore($student->id)],
                'gender' => ['required', 'in:L,P'],
                'birth_place' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date'],
                'religion' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:1000'],
                'parent_name' => ['nullable', 'string', 'max:150'],
                'parent_phone' => ['nullable', 'string', 'max:30'],
            ];
        }

        $validated = $request->validate(array_merge($detailRules, $coreRules, [
            'update_core_identity' => ['nullable', 'boolean'],
            'redirect_student_id' => ['nullable', 'integer'],
        ]));

        $detailPayload = array_intersect_key($validated, $detailRules);
        $corePayload = $request->boolean('update_core_identity')
            ? array_intersect_key($validated, $coreRules)
            : [];

        $detail = StudentLedgerDetail::query()->firstOrNew(['school_id' => $schoolId, 'student_id' => $student->id]);
        $detail->fill($detailPayload);
        $changedFields = array_keys($detail->getDirty());

        DB::transaction(function () use ($student, $detail, $corePayload, &$changedFields): void {
            if ($corePayload !== []) {
                $student->fill($corePayload);
                $changedFields = array_values(array_unique(array_merge($changedFields, array_keys($student->getDirty()))));
                $student->save();
            }
            $detail->save();
        });

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.details_updated',
            targetType: 'student',
            targetId: (string) $student->id,
            newValues: ['changed_fields' => $changedFields],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        $redirectStudentId = isset($validated['redirect_student_id']) ? (int) $validated['redirect_student_id'] : null;
        if ($redirectStudentId && Student::query()->where('school_id', $schoolId)->whereKey($redirectStudentId)->exists()) {
            return redirect()->to("/akademik/buku-induk/{$redirectStudentId}")
                ->with('message', "Data buku induk {$student->name} disimpan. Menampilkan siswa berikutnya.");
        }

        return back()->with('message', 'Data buku induk berhasil disimpan.');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        $activeTerm = $this->resolveActiveTerm($schoolId);
        $classes = $this->resolveActiveClasses($schoolId, $activeTerm);
        $filters = $this->validateIndexFilters($request, $classes);

        $columns = $this->ledgerSpreadsheetColumns();
        $query = $this->filteredStudentsQuery($schoolId, $activeTerm, $filters)
            ->with(['ledgerDetail'])
            ->orderBy('name')
            ->orderBy('id');

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.exported_xlsx',
            targetType: 'student_ledger_batch',
            targetId: (string) $schoolId,
            newValues: ['filters' => array_filter($filters)],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        $rows = (function () use ($query, $columns) {
            yield array_keys($columns);
            foreach ($query->lazy(300) as $student) {
                $detail = $student->ledgerDetail;
                $activeClass = $student->enrollments->first()?->classGroup?->name ?? '';
                yield [
                    $student->nis,
                    $student->nisn ?? '',
                    $student->name,
                    $detail?->nickname ?? '',
                    $student->gender,
                    $activeClass,
                    $detail?->nik ?? '',
                    $detail?->kk_number ?? '',
                    $student->birth_place ?? '',
                    $student->birth_date?->format('Y-m-d') ?? '',
                    $student->religion ?? '',
                    $detail?->citizenship ?? 'WNI',
                    $detail?->child_order !== null ? (string) $detail->child_order : '',
                    $detail?->siblings_count !== null ? (string) $detail->siblings_count : '',
                    $detail?->family_status ?? '',
                    $detail?->daily_language ?? '',
                    $detail?->blood_type ?? '',
                    $detail?->height_cm !== null ? (string) $detail->height_cm : '',
                    $detail?->weight_kg !== null ? (string) $detail->weight_kg : '',
                    $detail?->health_history ?? '',
                    $detail?->sports_hobby ?? '',
                    $detail?->arts_hobby ?? '',
                    $student->address ?? '',
                    $detail?->address_rt ?? '',
                    $detail?->address_rw ?? '',
                    $detail?->village ?? '',
                    $detail?->district ?? '',
                    $detail?->city ?? '',
                    $detail?->postal_code ?? '',
                    $detail?->living_with ?? '',
                    $detail?->distance_km !== null ? (string) $detail->distance_km : '',
                    $detail?->father_name ?? '',
                    $detail?->father_nik ?? '',
                    $detail?->father_education ?? '',
                    $detail?->father_occupation ?? '',
                    $detail?->father_income ?? '',
                    $detail?->father_phone ?? '',
                    $detail?->mother_name ?? '',
                    $detail?->mother_nik ?? '',
                    $detail?->mother_education ?? '',
                    $detail?->mother_occupation ?? '',
                    $detail?->mother_income ?? '',
                    $detail?->mother_phone ?? '',
                    $student->parent_name ?? '',
                    $student->parent_phone ?? '',
                    $detail?->guardian_relation ?? '',
                    $detail?->guardian_occupation ?? '',
                    $detail?->guardian_address ?? '',
                    $detail?->previous_school ?? '',
                    $detail?->diploma_number ?? '',
                    $detail?->diploma_date?->format('Y-m-d') ?? '',
                    $detail?->study_duration_years ?? '',
                    $detail?->accepted_date?->format('Y-m-d') ?? '',
                    $detail?->accepted_grade ?? '',
                    $detail?->admission_type ?? '',
                    $detail?->graduation_date?->format('Y-m-d') ?? '',
                    $detail?->graduation_diploma_number ?? '',
                    $detail?->continuing_to ?? '',
                    $detail?->leaving_reason ?? '',
                ];
            }
        })();

        return response()->streamDownload(function () use ($rows): void {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('Buku Induk Siswa');
                foreach ($rows as $rowIndex => $row) {
                    foreach (array_values($row) as $colIndex => $value) {
                        $sheet->setCellValueExplicit([$colIndex + 1, $rowIndex + 1], (string) $value, DataType::TYPE_STRING);
                    }
                }
                SpreadsheetStyler::styleWorksheet($sheet, 'D2');
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'buku_induk_siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importBulk(Request $request): RedirectResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
        ]);

        $uploaded = $request->file('file');
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $table = $extension === 'xlsx'
            ? $this->readSpreadsheetTable($uploaded->getRealPath())
            : $this->readCsvTable($uploaded->getRealPath());

        if (count($table) < 2) {
            throw ValidationException::withMessages(['file' => 'Berkas kosong atau tidak memiliki baris data.']);
        }

        $headerRow = array_shift($table);
        $columnMap = $this->resolveImportHeaderMap($headerRow);
        if (! isset($columnMap['nis'])) {
            throw ValidationException::withMessages(['file' => 'Kolom wajib "nis" tidak ditemukan pada baris header berkas.']);
        }

        if (count($table) > self::MAX_IMPORT_ROWS) {
            throw ValidationException::withMessages(['file' => 'Maksimal 1.000 baris siswa per unggahan buku induk.']);
        }

        $studentsByNis = Student::query()
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy(fn (Student $s): string => trim($s->nis));

        $detailRules = $this->detailValidationRules();
        $dateFields = ['birth_date', 'diploma_date', 'accepted_date', 'graduation_date'];
        $digit16Fields = ['nik', 'kk_number', 'father_nik', 'mother_nik'];
        $intFields = ['child_order', 'siblings_count', 'height_cm', 'weight_kg'];

        $preparedUpdates = [];
        $errors = [];

        foreach ($table as $index => $cells) {
            $rowNumber = $index + 2;
            if (! collect($cells)->contains(fn ($v): bool => trim((string) $v) !== '')) {
                continue;
            }

            $rawRow = [];
            foreach ($columnMap as $field => $colIdx) {
                $val = $cells[$colIdx] ?? null;
                if (in_array($field, $dateFields, true)) {
                    $val = $this->normalizeImportDate($val);
                } else {
                    $val = trim((string) ($val ?? ''));
                }

                if (in_array($field, $digit16Fields, true) && $val !== null && $val !== '' && ctype_digit($val) && strlen($val) < 16) {
                    $val = str_pad($val, 16, '0', STR_PAD_LEFT);
                }

                if ($field === 'nisn' && $val !== null && $val !== '' && ctype_digit($val) && strlen($val) < 10) {
                    $val = str_pad($val, 10, '0', STR_PAD_LEFT);
                }

                if (in_array($field, $intFields, true) && $val !== null && $val !== '' && is_numeric($val)) {
                    $val = (int) $val;
                }

                $rawRow[$field] = ($val === '') ? null : $val;
            }

            $nis = (string) ($rawRow['nis'] ?? '');
            if ($nis === '' || ! $studentsByNis->has($nis)) {
                $errors[] = "Baris {$rowNumber}: NIS [{$nis}] tidak ditemukan pada data siswa sekolah ini.";

                continue;
            }

            $student = $studentsByNis->get($nis);
            $detailInput = array_intersect_key($rawRow, $detailRules);
            $validator = Validator::make($detailInput, $detailRules);
            if ($validator->fails()) {
                $errors[] = "Baris {$rowNumber} (NIS {$nis}): ".$validator->errors()->first();

                continue;
            }

            $coreInput = [];
            foreach (['birth_place', 'birth_date', 'religion', 'address', 'parent_name', 'parent_phone'] as $coreField) {
                if (array_key_exists($coreField, $rawRow) && $rawRow[$coreField] !== null) {
                    $coreInput[$coreField] = $rawRow[$coreField];
                }
            }

            $preparedUpdates[] = [
                'student' => $student,
                'detail' => $validator->validated(),
                'core' => $coreInput,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => implode(' ', array_slice($errors, 0, 5))]);
        }

        if ($preparedUpdates === []) {
            throw ValidationException::withMessages(['file' => 'Tidak ada baris data siswa yang valid untuk diperbarui.']);
        }

        DB::transaction(function () use ($schoolId, $preparedUpdates, $request): void {
            foreach ($preparedUpdates as $item) {
                /** @var Student $student */
                $student = $item['student'];
                if ($item['core'] !== []) {
                    $student->fill($item['core']);
                    $student->save();
                }

                $detail = StudentLedgerDetail::query()->firstOrNew([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                ]);
                foreach ($item['detail'] as $k => $v) {
                    if ($v !== null) {
                        $detail->setAttribute($k, $v);
                    }
                }
                $detail->save();
            }

            $this->auditLogger->log(
                eventType: 'academic.student_ledger.imported_batch',
                targetType: 'student_ledger_batch',
                targetId: (string) count($preparedUpdates),
                newValues: ['updated_count' => count($preparedUpdates)],
                actor: $request->user(),
                schoolId: $schoolId,
            );
        });

        return back()->with('message', 'Berhasil memperbarui data buku induk untuk '.count($preparedUpdates).' siswa.');
    }

    public function uploadPhoto(Request $request, int $studentId): RedirectResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=3000,max_height=3000'],
        ]);

        $photo = $validated['photo'];
        $path = $photo->store("student-ledger/{$schoolId}/{$student->id}/profile", 'local');
        $oldPath = null;

        try {
            DB::transaction(function () use ($request, $student, $schoolId, $path, &$oldPath): void {
                Student::query()->where('school_id', $schoolId)->whereKey($student->id)->lockForUpdate()->firstOrFail();
                $detail = StudentLedgerDetail::query()->firstOrNew(['school_id' => $schoolId, 'student_id' => $student->id]);
                $oldPath = $detail->photo_path;
                $detail->photo_path = $path;
                $detail->save();
                $this->auditLogger->log(
                    eventType: 'academic.student_ledger.photo_uploaded',
                    targetType: 'student',
                    targetId: (string) $student->id,
                    newValues: ['mime_type' => $request->file('photo')?->getMimeType()],
                    actor: $request->user(),
                    schoolId: $schoolId,
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('message', 'Foto siswa berhasil disimpan.');
    }

    public function showPhoto(Request $request, int $studentId): Response
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $detail = StudentLedgerDetail::query()->where('school_id', $schoolId)->where('student_id', $studentId)->firstOrFail();
        abort_unless($detail->photo_path && Storage::disk('local')->exists($detail->photo_path), 404);

        $response = response()->file(Storage::disk('local')->path($detail->photo_path), [
            'Content-Type' => Storage::disk('local')->mimeType($detail->photo_path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function deletePhoto(Request $request, int $studentId): RedirectResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $detail = StudentLedgerDetail::query()->where('school_id', $schoolId)->where('student_id', $studentId)->firstOrFail();
        $path = $detail->photo_path;
        abort_unless($path, 404);

        DB::transaction(function () use ($request, $detail, $studentId, $schoolId): void {
            $detail->photo_path = null;
            $detail->save();
            $this->auditLogger->log(
                eventType: 'academic.student_ledger.photo_deleted',
                targetType: 'student',
                targetId: (string) $studentId,
                actor: $request->user(),
                schoolId: $schoolId,
            );
        });
        Storage::disk('local')->delete($path);

        return back()->with('message', 'Foto siswa berhasil dihapus.');
    }

    public function uploadDocument(Request $request, int $studentId): RedirectResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png'],
        ]);

        $file = $validated['file'];
        $path = $file->store("student-ledger/{$schoolId}/{$student->id}", 'local');
        $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename($file->getClientOriginalName())) ?: 'dokumen';
        $originalName = mb_substr($originalName, 0, 255);

        try {
            DB::transaction(function () use ($request, $student, $schoolId, $validated, $file, $path, $originalName): void {
                Student::query()->where('school_id', $schoolId)->whereKey($student->id)->lockForUpdate()->firstOrFail();
                $count = StudentLedgerDocument::query()->where('school_id', $schoolId)->where('student_id', $student->id)->count();
                if ($count >= 10) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 10 dokumen dapat disimpan untuk satu siswa.']);
                }
                $document = StudentLedgerDocument::query()->create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'uploaded_by' => $request->user()->id,
                    'document_type' => $validated['document_type'],
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $file->getSize(),
                ]);
                $this->auditLogger->log(
                    eventType: 'academic.student_ledger.document_uploaded',
                    targetType: 'student_ledger_document',
                    targetId: (string) $document->id,
                    newValues: ['student_id' => (int) $student->id, 'document_type' => $document->document_type, 'size_bytes' => $document->size_bytes],
                    actor: $request->user(),
                    schoolId: $schoolId,
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('message', 'Dokumen berhasil diunggah.');
    }

    public function downloadDocument(Request $request, int $studentId, int $documentId): Response
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $document = StudentLedgerDocument::query()->where('school_id', $schoolId)
            ->where('student_id', $studentId)->findOrFail($documentId);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.document_downloaded',
            targetType: 'student_ledger_document',
            targetId: (string) $document->id,
            newValues: ['student_id' => $studentId],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function deleteDocument(Request $request, int $studentId, int $documentId): RedirectResponse
    {
        $this->authorizeManagement($request);
        $schoolId = (int) $request->user()->school_id;
        Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $document = StudentLedgerDocument::query()->where('school_id', $schoolId)
            ->where('student_id', $studentId)->findOrFail($documentId);
        $path = $document->file_path;

        DB::transaction(function () use ($request, $document, $studentId, $schoolId): void {
            $document->delete();
            $this->auditLogger->log(
                eventType: 'academic.student_ledger.document_deleted',
                targetType: 'student_ledger_document',
                targetId: (string) $document->id,
                newValues: ['student_id' => $studentId, 'document_type' => $document->document_type],
                actor: $request->user(),
                schoolId: $schoolId,
            );
        });
        Storage::disk('local')->delete($path);

        return back()->with('message', 'Dokumen berhasil dihapus.');
    }

    public function exportPdf(Request $request, int $studentId): Response
    {
        $this->authorizeManagement($request);

        $schoolId = (int) $request->user()->school_id;
        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $termId = $request->filled('term_id') ? (int) $request->query('term_id') : null;
        $record = $this->buildPdfStudentRecord($schoolId, $student, $termId);
        $school = $this->schoolScope->getSchoolLegalSnapshot($schoolId);
        $schoolLogo = $this->schoolScope->getSchoolLogoDataUri($schoolId);

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.exported',
            targetType: 'student',
            targetId: (string) $student->id,
            newValues: ['format' => 'pdf', 'term_id' => $termId],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        $pdf = Pdf::loadView('academic.student-ledger', [
            'records' => [$record],
            'student' => $record['student'],
            'detail' => $record['detail'],
            'documents' => $record['documents'],
            'classHistory' => $record['classHistory'],
            'lifecycleHistory' => $record['lifecycleHistory'],
            'reportHistory' => $record['reportHistory'],
            'photoDataUri' => $record['photoDataUri'],
            'school' => $school,
            'schoolLogo' => $schoolLogo,
        ])->setPaper('a4', 'portrait');

        $filename = 'Buku-Induk-'.$student->nis.'.pdf';

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function exportBatchPdf(Request $request): Response
    {
        $this->authorizeManagement($request);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = $this->resolveActiveTerm($schoolId);
        $classes = $this->resolveActiveClasses($schoolId, $activeTerm);
        $filters = $this->validateIndexFilters($request, $classes);

        $students = $this->filteredStudentsQuery($schoolId, $activeTerm, $filters)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::MAX_BATCH_PDF_STUDENTS)
            ->get();

        abort_if($students->isEmpty(), 404, 'Tidak ada data siswa yang cocok untuk dicetak.');

        $records = $students->map(fn (Student $student): array => $this->buildPdfStudentRecord($schoolId, $student))->all();
        $school = $this->schoolScope->getSchoolLegalSnapshot($schoolId);
        $schoolLogo = $this->schoolScope->getSchoolLogoDataUri($schoolId);

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.exported_batch_pdf',
            targetType: 'student_ledger_batch',
            targetId: (string) count($records),
            newValues: ['count' => count($records), 'filters' => array_filter($filters)],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        $first = $records[0];

        $pdf = Pdf::loadView('academic.student-ledger', [
            'records' => $records,
            'student' => $first['student'],
            'detail' => $first['detail'],
            'documents' => $first['documents'],
            'classHistory' => $first['classHistory'],
            'lifecycleHistory' => $first['lifecycleHistory'],
            'reportHistory' => $first['reportHistory'],
            'photoDataUri' => $first['photoDataUri'],
            'school' => $school,
            'schoolLogo' => $schoolLogo,
        ])->setPaper('a4', 'portrait');

        $filename = 'Buku-Induk-Kumpulan-'.now()->format('Ymd').'.pdf';

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    private function buildPdfStudentRecord(int $schoolId, Student $student, ?int $termId = null): array
    {
        $history = $this->classHistory($student->id, $schoolId);
        $lifecycle = $this->lifecycleHistory($student->id, $schoolId);
        $detail = StudentLedgerDetail::query()->where('school_id', $schoolId)->where('student_id', $student->id)->first();
        $documents = StudentLedgerDocument::query()->where('school_id', $schoolId)->where('student_id', $student->id)
            ->orderBy('created_at')->get(['document_type', 'original_name', 'size_bytes', 'created_at']);
        $reports = $this->formatReportSnapshotsForStudent($schoolId, $student->id, $history, $termId);

        return [
            'student' => $student,
            'detail' => $detail,
            'documents' => $documents,
            'classHistory' => $history,
            'lifecycleHistory' => $lifecycle,
            'reportHistory' => $reports,
            'photoDataUri' => $this->resolveStudentPhotoDataUri($detail),
        ];
    }

    private function resolveStudentPhotoDataUri(?StudentLedgerDetail $detail): ?string
    {
        if (! $detail?->photo_path || ! Storage::disk('local')->exists($detail->photo_path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($detail->photo_path) ?: 'image/jpeg';
        if ($mime === 'image/png' && ! extension_loaded('gd')) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($detail->photo_path));
    }

    /**
     * @param  Collection<int, Enrollment>  $history
     * @return list<array<string, mixed>>
     */
    private function formatReportSnapshotsForStudent(int $schoolId, int $studentId, Collection $history, ?int $termId = null): array
    {
        $grouped = $this->reportPublishingService->getPublishedSnapshotsForStudents($schoolId, [$studentId], $termId);
        $snapshots = $grouped[$studentId] ?? [];
        if ($snapshots === []) {
            return [];
        }

        $enrollmentsById = $history->keyBy('id');

        return array_map(function (array $snapshot) use ($enrollmentsById): array {
            /** @var ?Enrollment $enrollment */
            $enrollment = $enrollmentsById->get($snapshot['enrollment_id']);

            return [
                'id' => $snapshot['id'],
                'term_id' => $snapshot['term_id'] ?? $enrollment?->term_id,
                'academic_year' => $enrollment?->term?->academicYear?->name ?? '—',
                'term_name' => $enrollment?->term?->name ?? '—',
                'class_name' => $enrollment?->classGroup?->name ?? '—',
                'published_at' => $snapshot['published_at'],
                'checksum' => $snapshot['checksum'],
                'grades' => $snapshot['grades'],
                'attendance' => $snapshot['attendance'],
                'notes' => $snapshot['notes'],
            ];
        }, $snapshots);
    }

    private function resolveActiveTerm(int $schoolId): ?Term
    {
        return Term::query()
            ->with('academicYear:id,name,school_id')
            ->where('is_active', true)
            ->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', $schoolId))
            ->latest('start_date')
            ->first();
    }

    private function resolveActiveClasses(int $schoolId, ?Term $activeTerm): Collection
    {
        if (! $activeTerm) {
            return collect();
        }

        return ClassGroup::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $activeTerm->academic_year_id)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);
    }

    private function validateIndexFilters(Request $request, Collection $classes): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,graduated,transferred,dropped_out'],
            'class_group_id' => ['nullable', 'integer', 'exists:class_groups,id'],
            'completeness' => ['nullable', 'in:complete,incomplete'],
        ]);

        $classGroupId = isset($filters['class_group_id']) ? (int) $filters['class_group_id'] : null;
        abort_if($classGroupId && ! $classes->contains('id', $classGroupId), 404);
        $filters['class_group_id'] = $classGroupId;

        return $filters;
    }

    private function filteredStudentsQuery(int $schoolId, ?Term $activeTerm, array $filters): Builder
    {
        $classGroupId = $filters['class_group_id'] ?? null;

        $query = Student::query()
            ->where('school_id', $schoolId)
            ->with(['enrollments' => function ($query) use ($activeTerm): void {
                if ($activeTerm) {
                    $query->where('term_id', $activeTerm->id)
                        ->where('status', 'active')
                        ->with('classGroup:id,name,grade_level');
                } else {
                    $query->whereRaw('1 = 0');
                }
            }])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($classGroupId && $activeTerm, fn (Builder $query) => $query->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                ->where('term_id', $activeTerm->id)
                ->where('class_group_id', $classGroupId)
                ->where('status', 'active')));

        if (! empty($filters['completeness'])) {
            $this->applyCompletenessFilter($query, (string) $filters['completeness']);
        }

        return $query;
    }

    private function applyCompletenessFilter(Builder $query, string $mode): Builder
    {
        if ($mode === 'complete') {
            return $query->whereHas('ledgerDetail', function (Builder $detailQuery): void {
                $detailQuery->whereNotNull('nik')->where('nik', '!=', '')
                    ->whereNotNull('father_name')->where('father_name', '!=', '')
                    ->whereNotNull('mother_name')->where('mother_name', '!=', '')
                    ->whereNotNull('previous_school')->where('previous_school', '!=', '');
            });
        }

        if ($mode === 'incomplete') {
            return $query->where(function (Builder $sub): void {
                $sub->whereDoesntHave('ledgerDetail')
                    ->orWhereHas('ledgerDetail', function (Builder $detailQuery): void {
                        $detailQuery->whereNull('nik')->orWhere('nik', '')
                            ->orWhereNull('father_name')->orWhere('father_name', '')
                            ->orWhereNull('mother_name')->orWhere('mother_name', '')
                            ->orWhereNull('previous_school')->orWhere('previous_school', '');
                    });
            });
        }

        return $query;
    }

    /**
     * @return array{status: string, percent: int, filled: int, total: int}
     */
    private function calculateCompleteness(Student $student, ?StudentLedgerDetail $detail): array
    {
        $checks = [
            filled($student->nisn),
            filled($student->birth_place) && $student->birth_date !== null,
            filled($student->address),
            filled($detail?->nik),
            filled($detail?->village) && filled($detail?->district),
            filled($detail?->father_name) && filled($detail?->mother_name),
            filled($detail?->previous_school),
            filled($detail?->photo_path),
        ];

        $filled = count(array_filter($checks));
        $total = count($checks);
        $percent = (int) round(($filled / $total) * 100);

        $isCoreLedgerComplete = filled($detail?->nik)
            && filled($detail?->father_name)
            && filled($detail?->mother_name)
            && filled($detail?->previous_school);

        $status = $isCoreLedgerComplete ? 'complete' : ($detail !== null ? 'partial' : 'empty');

        return [
            'status' => $status,
            'percent' => $percent,
            'filled' => $filled,
            'total' => $total,
        ];
    }

    private function resolveAdjacentStudents(int $schoolId, Student $current): array
    {
        $orderedIds = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'nis']);

        $currentIndex = $orderedIds->search(fn (Student $s): bool => (int) $s->id === (int) $current->id);
        $prev = ($currentIndex !== false && $currentIndex > 0) ? $orderedIds->get($currentIndex - 1) : null;
        $next = ($currentIndex !== false && $currentIndex < $orderedIds->count() - 1) ? $orderedIds->get($currentIndex + 1) : null;

        return [
            'previous' => $prev ? ['id' => (int) $prev->id, 'name' => $prev->name, 'nis' => $prev->nis] : null,
            'next' => $next ? ['id' => (int) $next->id, 'name' => $next->name, 'nis' => $next->nis] : null,
        ];
    }

    private function serializeDetail(int $studentId, StudentLedgerDetail $detail): array
    {
        return [
            'photo_url' => $detail->photo_path ? "/akademik/buku-induk/{$studentId}/foto" : null,
            'nickname' => $detail->nickname,
            'nik' => $detail->nik,
            'kk_number' => $detail->kk_number,
            'citizenship' => $detail->citizenship,
            'child_order' => $detail->child_order,
            'siblings_count' => $detail->siblings_count,
            'family_status' => $detail->family_status,
            'daily_language' => $detail->daily_language,
            'blood_type' => $detail->blood_type,
            'height_cm' => $detail->height_cm,
            'weight_kg' => $detail->weight_kg,
            'health_history' => $detail->health_history,
            'address_rt' => $detail->address_rt,
            'address_rw' => $detail->address_rw,
            'village' => $detail->village,
            'district' => $detail->district,
            'city' => $detail->city,
            'postal_code' => $detail->postal_code,
            'living_with' => $detail->living_with,
            'distance_km' => $detail->distance_km,
            'sports_hobby' => $detail->sports_hobby,
            'arts_hobby' => $detail->arts_hobby,
            'father_name' => $detail->father_name,
            'father_nik' => $detail->father_nik,
            'father_education' => $detail->father_education,
            'father_occupation' => $detail->father_occupation,
            'father_income' => $detail->father_income,
            'father_phone' => $detail->father_phone,
            'mother_name' => $detail->mother_name,
            'mother_nik' => $detail->mother_nik,
            'mother_education' => $detail->mother_education,
            'mother_occupation' => $detail->mother_occupation,
            'mother_income' => $detail->mother_income,
            'mother_phone' => $detail->mother_phone,
            'guardian_relation' => $detail->guardian_relation,
            'guardian_occupation' => $detail->guardian_occupation,
            'guardian_address' => $detail->guardian_address,
            'previous_school' => $detail->previous_school,
            'diploma_number' => $detail->diploma_number,
            'diploma_date' => $detail->diploma_date?->format('Y-m-d'),
            'study_duration_years' => $detail->study_duration_years,
            'accepted_date' => $detail->accepted_date?->format('Y-m-d'),
            'accepted_grade' => $detail->accepted_grade,
            'admission_type' => $detail->admission_type,
            'graduation_date' => $detail->graduation_date?->format('Y-m-d'),
            'graduation_diploma_number' => $detail->graduation_diploma_number,
            'continuing_to' => $detail->continuing_to,
            'leaving_reason' => $detail->leaving_reason,
        ];
    }

    private function detailValidationRules(): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:100'],
            'nik' => ['nullable', 'digits:16'],
            'kk_number' => ['nullable', 'digits:16'],
            'citizenship' => ['nullable', 'string', 'max:30'],
            'child_order' => ['nullable', 'integer', 'min:1', 'max:30'],
            'siblings_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'family_status' => ['nullable', 'string', 'max:40'],
            'daily_language' => ['nullable', 'string', 'max:60'],
            'blood_type' => ['nullable', 'in:A,B,AB,O,Tidak Tahu'],
            'height_cm' => ['nullable', 'integer', 'min:30', 'max:250'],
            'weight_kg' => ['nullable', 'integer', 'min:5', 'max:250'],
            'health_history' => ['nullable', 'string', 'max:255'],
            'address_rt' => ['nullable', 'string', 'max:5'],
            'address_rw' => ['nullable', 'string', 'max:5'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'living_with' => ['nullable', 'string', 'max:60'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'sports_hobby' => ['nullable', 'string', 'max:100'],
            'arts_hobby' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'father_nik' => ['nullable', 'digits:16'],
            'father_education' => ['nullable', 'string', 'max:40'],
            'father_occupation' => ['nullable', 'string', 'max:60'],
            'father_income' => ['nullable', 'string', 'max:60'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'mother_nik' => ['nullable', 'digits:16'],
            'mother_education' => ['nullable', 'string', 'max:40'],
            'mother_occupation' => ['nullable', 'string', 'max:60'],
            'mother_income' => ['nullable', 'string', 'max:60'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relation' => ['nullable', 'string', 'max:60'],
            'guardian_occupation' => ['nullable', 'string', 'max:60'],
            'guardian_address' => ['nullable', 'string', 'max:255'],
            'previous_school' => ['nullable', 'string', 'max:100'],
            'diploma_number' => ['nullable', 'string', 'max:50'],
            'diploma_date' => ['nullable', 'date'],
            'study_duration_years' => ['nullable', 'string', 'max:30'],
            'accepted_date' => ['nullable', 'date'],
            'accepted_grade' => ['nullable', 'string', 'max:30'],
            'admission_type' => ['nullable', 'string', 'max:40'],
            'graduation_date' => ['nullable', 'date'],
            'graduation_diploma_number' => ['nullable', 'string', 'max:60'],
            'continuing_to' => ['nullable', 'string', 'max:120'],
            'leaving_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function ledgerSpreadsheetColumns(): array
    {
        return [
            'nis' => ['nis', 'nomor induk'],
            'nisn' => ['nisn'],
            'name' => ['name', 'nama', 'nama lengkap'],
            'nickname' => ['nickname', 'nama panggilan'],
            'gender' => ['gender', 'l/p', 'jk'],
            'active_class' => ['active_class', 'kelas', 'rombel'],
            'nik' => ['nik', 'nik siswa'],
            'kk_number' => ['kk_number', 'no kk', 'nomor kk'],
            'birth_place' => ['birth_place', 'tempat lahir'],
            'birth_date' => ['birth_date', 'tanggal lahir'],
            'religion' => ['religion', 'agama'],
            'citizenship' => ['citizenship', 'kewarganegaraan'],
            'child_order' => ['child_order', 'anak ke'],
            'siblings_count' => ['siblings_count', 'jumlah saudara', 'saudara kandung'],
            'family_status' => ['family_status', 'status keluarga'],
            'daily_language' => ['daily_language', 'bahasa sehari-hari', 'bahasa'],
            'blood_type' => ['blood_type', 'golongan darah', 'gol darah'],
            'height_cm' => ['height_cm', 'tinggi badan', 'tb'],
            'weight_kg' => ['weight_kg', 'berat badan', 'bb'],
            'health_history' => ['health_history', 'riwayat kesehatan', 'riwayat penyakit'],
            'sports_hobby' => ['sports_hobby', 'hobi olahraga', 'olahraga'],
            'arts_hobby' => ['arts_hobby', 'hobi kesenian', 'kesenian'],
            'address' => ['address', 'alamat'],
            'address_rt' => ['address_rt', 'rt'],
            'address_rw' => ['address_rw', 'rw'],
            'village' => ['village', 'desa', 'kelurahan', 'desa/kelurahan'],
            'district' => ['district', 'kecamatan'],
            'city' => ['city', 'kabupaten', 'kota', 'kabupaten/kota'],
            'postal_code' => ['postal_code', 'kode pos'],
            'living_with' => ['living_with', 'tinggal bersama'],
            'distance_km' => ['distance_km', 'jarak km', 'jarak ke sekolah'],
            'father_name' => ['father_name', 'nama ayah'],
            'father_nik' => ['father_nik', 'nik ayah'],
            'father_education' => ['father_education', 'pendidikan ayah'],
            'father_occupation' => ['father_occupation', 'pekerjaan ayah'],
            'father_income' => ['father_income', 'penghasilan ayah'],
            'father_phone' => ['father_phone', 'telepon ayah', 'hp ayah'],
            'mother_name' => ['mother_name', 'nama ibu'],
            'mother_nik' => ['mother_nik', 'nik ibu'],
            'mother_education' => ['mother_education', 'pendidikan ibu'],
            'mother_occupation' => ['mother_occupation', 'pekerjaan ibu'],
            'mother_income' => ['mother_income', 'penghasilan ibu'],
            'mother_phone' => ['mother_phone', 'telepon ibu', 'hp ibu'],
            'parent_name' => ['parent_name', 'nama wali'],
            'parent_phone' => ['parent_phone', 'telepon wali', 'hp wali'],
            'guardian_relation' => ['guardian_relation', 'hubungan wali'],
            'guardian_occupation' => ['guardian_occupation', 'pekerjaan wali'],
            'guardian_address' => ['guardian_address', 'alamat wali'],
            'previous_school' => ['previous_school', 'sekolah asal', 'asal sekolah'],
            'diploma_number' => ['diploma_number', 'nomor ijazah asal', 'no ijazah asal'],
            'diploma_date' => ['diploma_date', 'tanggal ijazah asal', 'tgl ijazah asal'],
            'study_duration_years' => ['study_duration_years', 'lama belajar asal', 'lama belajar'],
            'accepted_date' => ['accepted_date', 'tanggal diterima', 'tgl diterima'],
            'accepted_grade' => ['accepted_grade', 'diterima di kelas'],
            'admission_type' => ['admission_type', 'jenis pendaftaran'],
            'graduation_date' => ['graduation_date', 'tanggal lulus', 'tanggal keluar'],
            'graduation_diploma_number' => ['graduation_diploma_number', 'nomor ijazah lulus'],
            'continuing_to' => ['continuing_to', 'melanjutkan ke'],
            'leaving_reason' => ['leaving_reason', 'alasan keluar'],
        ];
    }

    private function resolveImportHeaderMap(array $headerRow): array
    {
        $aliases = [];
        foreach ($this->ledgerSpreadsheetColumns() as $field => $labels) {
            foreach ($labels as $label) {
                $aliases[$this->normalizeHeaderKey($label)] = $field;
            }
        }

        $map = [];
        foreach ($headerRow as $colIdx => $rawLabel) {
            $clean = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $rawLabel) ?? '');
            $key = $this->normalizeHeaderKey($clean);
            if ($key !== '' && isset($aliases[$key])) {
                $map[$aliases[$key]] = $colIdx;
            }
        }

        return $map;
    }

    private function normalizeHeaderKey(string $header): string
    {
        return preg_replace('/[^a-z0-9]+/u', '', mb_strtolower(trim($header))) ?? '';
    }

    private function readSpreadsheetTable(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $lastRow = min($sheet->getHighestDataRow(), self::MAX_IMPORT_ROWS + 5);
            $lastColIndex = min(Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), 80);

            $table = [];
            for ($r = 1; $r <= $lastRow; $r++) {
                $row = [];
                for ($c = 1; $c <= $lastColIndex; $c++) {
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($c).$r);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $spreadsheet->disconnectWorksheets();
                        throw ValidationException::withMessages(['file' => "Rumus Excel tidak diizinkan (baris {$r})."]);
                    }
                    $row[] = $cell->getFormattedValue();
                }
                $table[] = $row;
            }
            $spreadsheet->disconnectWorksheets();

            return $table;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Berkas XLSX tidak dapat dibaca.']);
        }
    }

    private function readCsvTable(string $path): array
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw ValidationException::withMessages(['file' => 'Berkas CSV tidak dapat dibaca.']);
        }

        try {
            $firstLine = fgets($stream);
            if ($firstLine === false) {
                return [];
            }
            rewind($stream);
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            $rows = [];
            while (($row = fgetcsv($stream, separator: $delimiter)) !== false) {
                $rows[] = array_map(static fn ($val): string => (string) ($val ?? ''), $row);
                if (count($rows) > self::MAX_IMPORT_ROWS + 5) {
                    break;
                }
            }

            return $rows;
        } finally {
            fclose($stream);
        }
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

    private function classHistory(int $studentId, int $schoolId): Collection
    {
        return Enrollment::query()
            ->where('student_id', $studentId)
            ->whereHas('classGroup', fn (Builder $query) => $query->where('school_id', $schoolId))
            ->whereHas('term.academicYear', fn (Builder $query) => $query->where('school_id', $schoolId))
            ->with([
                'classGroup:id,school_id,name,grade_level',
                'term:id,academic_year_id,name,semester_type,is_active',
                'term.academicYear:id,school_id,name',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    private function lifecycleHistory(int $studentId, int $schoolId): Collection
    {
        return StudentLifecycleEvent::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->with(['sourceEnrollment.classGroup:id,name', 'targetEnrollment.classGroup:id,name'])
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator']), 403);
    }
}
