<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLedgerDetail;
use App\Modules\Academic\Infrastructure\Models\StudentLedgerDocument;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StudentLedgerController extends Controller
{
    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorizeManagement($request);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = Term::query()
            ->with('academicYear:id,name,school_id')
            ->where('is_active', true)
            ->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', $schoolId))
            ->latest('start_date')
            ->first();

        $classes = $activeTerm
            ? ClassGroup::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,graduated,transferred,dropped_out'],
            'class_group_id' => [
                'nullable',
                'integer',
                'exists:class_groups,id',
            ],
        ]);

        $classGroupId = isset($filters['class_group_id']) ? (int) $filters['class_group_id'] : null;
        abort_if($classGroupId && ! $classes->contains('id', $classGroupId), 404);

        $students = Student::query()
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
                ->where('status', 'active')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'gender' => $student->gender,
                'status' => $student->status,
                'parent_name' => $student->parent_name,
                'class_name' => $student->enrollments->first()?->classGroup?->name,
            ]);

        return Inertia::render('Academic/Students/Ledger/Index', [
            'students' => $students,
            'classes' => $classes,
            'active_term' => $activeTerm ? [
                'name' => $activeTerm->name,
                'academic_year' => $activeTerm->academicYear?->name,
            ] : null,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'class_group_id' => $classGroupId ? (string) $classGroupId : '',
            ],
        ]);
    }

    public function show(Request $request, int $studentId): InertiaResponse
    {
        $this->authorizeManagement($request);

        $schoolId = (int) $request->user()->school_id;
        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $history = $this->classHistory($student->id, $schoolId);
        $detail = StudentLedgerDetail::query()->where('school_id', $schoolId)->where('student_id', $student->id)->first();
        $documents = StudentLedgerDocument::query()->where('school_id', $schoolId)->where('student_id', $student->id)
            ->latest('created_at')->get(['id', 'document_type', 'original_name', 'size_bytes', 'created_at']);

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
            'class_history' => $history->map(fn (Enrollment $enrollment): array => [
                'id' => (int) $enrollment->id,
                'academic_year' => $enrollment->term?->academicYear?->name,
                'term' => $enrollment->term?->name,
                'class_name' => $enrollment->classGroup?->name,
                'grade_level' => $enrollment->classGroup?->grade_level,
                'roll_number' => $enrollment->roll_number,
                'status' => $enrollment->status,
            ])->values(),
            'detail' => $detail ? [
                'photo_url' => $detail->photo_path ? "/akademik/buku-induk/{$student->id}/foto" : null,
                'nickname' => $detail->nickname,
                'nik' => $detail->nik,
                'blood_type' => $detail->blood_type,
                'address_rt' => $detail->address_rt,
                'address_rw' => $detail->address_rw,
                'village' => $detail->village,
                'district' => $detail->district,
                'sports_hobby' => $detail->sports_hobby,
                'father_name' => $detail->father_name,
                'father_occupation' => $detail->father_occupation,
                'mother_name' => $detail->mother_name,
                'mother_occupation' => $detail->mother_occupation,
                'previous_school' => $detail->previous_school,
                'diploma_number' => $detail->diploma_number,
                'diploma_date' => $detail->diploma_date?->format('Y-m-d'),
            ] : null,
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
        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:100'],
            'nik' => ['nullable', 'digits:16'],
            'blood_type' => ['nullable', 'in:A,B,AB,O,Tidak Tahu'],
            'address_rt' => ['nullable', 'string', 'max:5'],
            'address_rw' => ['nullable', 'string', 'max:5'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'sports_hobby' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'father_occupation' => ['nullable', 'string', 'max:60'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'mother_occupation' => ['nullable', 'string', 'max:60'],
            'previous_school' => ['nullable', 'string', 'max:100'],
            'diploma_number' => ['nullable', 'string', 'max:50'],
            'diploma_date' => ['nullable', 'date'],
        ]);

        $detail = StudentLedgerDetail::query()->firstOrNew(['school_id' => $schoolId, 'student_id' => $student->id]);
        $detail->fill($validated);
        $changedFields = array_keys($detail->getDirty());
        DB::transaction(fn () => $detail->save());

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.details_updated',
            targetType: 'student',
            targetId: (string) $student->id,
            newValues: ['changed_fields' => $changedFields],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        return back()->with('message', 'Data buku induk berhasil disimpan.');
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
        $history = $this->classHistory($student->id, $schoolId);
        $detail = StudentLedgerDetail::query()->where('school_id', $schoolId)->where('student_id', $student->id)->first();
        $documents = StudentLedgerDocument::query()->where('school_id', $schoolId)->where('student_id', $student->id)
            ->orderBy('created_at')->get(['document_type', 'original_name', 'size_bytes', 'created_at']);
        $school = $this->schoolScope->getSchoolDetails($schoolId);

        $this->auditLogger->log(
            eventType: 'academic.student_ledger.exported',
            targetType: 'student',
            targetId: (string) $student->id,
            newValues: ['format' => 'pdf'],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        return Pdf::loadView('academic.student-ledger', [
            'student' => $student,
            'detail' => $detail,
            'documents' => $documents,
            'classHistory' => $history,
            'school' => $school,
        ])->setPaper('a4', 'portrait')->download('Buku-Induk-'.$student->nis.'.pdf');
    }

    private function classHistory(int $studentId, int $schoolId)
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

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator']), 403);
    }
}
