<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicImportService;
use App\Modules\Academic\Application\Services\EnrollmentService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

class StudentController extends Controller
{
    public function __construct(
        protected AcademicImportService $importService,
        protected EnrollmentService $enrollmentService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', Student::class);

        $schoolId = (int) $request->user()->school_id;
        $search = $request->query('search');
        $status = $request->query('status');
        $classGroupId = $request->query('class_group_id');

        // Cari active term
        $activeTerm = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $classes = $activeTerm
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();

        // Default ke kelas pertama agar daftar siswa tidak pernah memuat seluruh sekolah sekaligus.
        $classGroupId = $classGroupId ?: $classes->first()?->id;

        $query = Student::where('school_id', $schoolId)
            ->with(['enrollments' => function ($q) use ($activeTerm) {
                if ($activeTerm) {
                    $q->where('term_id', $activeTerm->id)->with('classGroup');
                }
            }])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->when($classGroupId && $activeTerm, function ($q) use ($classGroupId, $activeTerm) {
                $q->whereHas('enrollments', function ($sub) use ($classGroupId, $activeTerm) {
                    $sub->where('term_id', $activeTerm->id)->where('class_group_id', $classGroupId);
                });
            })
            ->latest('id');

        $students = $query->paginate(15)->withQueryString();

        return Inertia::render('Academic/Students/Index', [
            'students' => $students,
            'can_manage' => Gate::allows('create', Student::class),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'class_group_id' => $classGroupId,
            ],
            'classes' => $classes,
            'active_term' => $activeTerm,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('students')->where('school_id', $schoolId)],
            'nisn' => ['nullable', 'string', 'max:30', Rule::unique('students')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'class_group_id' => [
                'nullable',
                'required_with:term_id',
                Rule::exists('class_groups', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'term_id' => [
                'nullable',
                'required_with:class_group_id',
                Rule::exists('terms', 'id')->where(fn ($query) => $query->whereIn(
                    'academic_year_id',
                    AcademicYear::where('school_id', $schoolId)->select('id')
                )),
            ],
        ]);

        $validated['school_id'] = $schoolId;
        $classGroupId = $validated['class_group_id'] ?? null;
        $termId = $validated['term_id'] ?? null;
        unset($validated['class_group_id'], $validated['term_id']);

        try {
            $student = DB::transaction(function () use ($validated, $classGroupId, $termId, $request, $schoolId): Student {
                $student = Student::create($validated);

                if ($classGroupId && $termId) {
                    $this->enrollmentService->enrollStudent(
                        termId: (int) $termId,
                        classGroupId: (int) $classGroupId,
                        studentId: $student->id,
                        actorId: $request->user()->id
                    );
                }

                $this->auditLogger->log(
                    eventType: 'academic.student.created',
                    targetType: 'student',
                    targetId: (string) $student->id,
                    newValues: $student->toArray(),
                    oldValues: null,
                    actor: $request->user(),
                    schoolId: $schoolId
                );

                return $student;
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['class_group_id' => $e->getMessage()]);
        }

        return back()->with('message', "Siswa {$student->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('students')->where('school_id', $schoolId)->ignore($student->id)],
            'nisn' => ['nullable', 'string', 'max:30', Rule::unique('students')->where('school_id', $schoolId)->ignore($student->id)],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:active,graduated,transferred,dropped_out'],
        ]);

        $oldValues = $student->toArray();
        $student->update($validated);

        $this->auditLogger->log(
            eventType: 'academic.student.updated',
            targetType: 'student',
            targetId: (string) $student->id,
            newValues: $student->toArray(),
            oldValues: $oldValues,
            actor: $request->user(),
            schoolId: $schoolId
        );

        return back()->with('message', "Data siswa {$student->name} berhasil diperbarui.");
    }

    public function import(Request $request): InertiaResponse|RedirectResponse
    {
        return $this->previewImport($request);
    }

    public function previewImport(Request $request): InertiaResponse|RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $request->validate([
            'csv_file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'csv_content' => ['required_without:csv_file', 'nullable', 'string', 'max:2097152'],
            'enroll_term_id' => ['nullable', 'exists:terms,id'],
            'enroll_class_group_id' => ['nullable', 'exists:class_groups,id'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $content = $request->hasFile('csv_file')
            ? file_get_contents($request->file('csv_file')->getRealPath())
            : (string) $request->input('csv_content');

        try {
            $rows = $this->importService->parseCsvString($content, ['nis', 'name', 'gender']);

            if (empty($rows)) {
                return back()->withErrors(['csv_file' => 'Berkas CSV kosong atau format header tidak valid.']);
            }

            $validation = $this->importService->validateStudentRows($schoolId, $rows);
            $token = (string) Str::uuid();
            Cache::put($this->importCacheKey($token), [
                'user_id' => (int) $request->user()->id,
                'school_id' => $schoolId,
                'valid_rows' => $validation['valid_rows'],
                'invalid_rows' => $validation['invalid_rows'],
                'enroll_term_id' => $request->filled('enroll_term_id') ? (int) $request->input('enroll_term_id') : null,
                'enroll_class_group_id' => $request->filled('enroll_class_group_id') ? (int) $request->input('enroll_class_group_id') : null,
            ], now()->addMinutes(15));

            $previewRows = collect($validation['valid_rows'])->map(fn (array $row) => [
                'row' => $row['row_index'], 'identifier' => $row['nis'], 'name' => $row['name'],
                'gender' => $row['gender'], 'valid' => true, 'errors' => [],
            ])->concat(collect($validation['invalid_rows'])->map(fn (array $row) => [
                'row' => $row['row_index'], 'identifier' => $row['data']['nis'] ?? '',
                'name' => $row['data']['name'] ?? '', 'gender' => $row['data']['gender'] ?? '',
                'valid' => false, 'errors' => $row['errors'],
            ]))->sortBy('row')->values();

            return Inertia::render('Academic/Imports/Preview', [
                'token' => $token,
                'entity' => 'siswa',
                'index_url' => '/akademik/siswa',
                'confirm_url' => "/akademik/siswa/import/{$token}/confirm",
                'rows' => $previewRows,
                'valid_count' => count($validation['valid_rows']),
                'invalid_count' => count($validation['invalid_rows']),
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['csv_file' => $e->getMessage()]);
        }
    }

    public function confirmImport(Request $request, string $token): RedirectResponse
    {
        Gate::authorize('create', Student::class);
        abort_unless(Str::isUuid($token), 404);

        $cacheKey = $this->importCacheKey($token);
        $lock = Cache::lock($cacheKey.':confirm', 900);
        abort_unless($lock->get(), 409, 'Impor ini sedang diproses. Coba lagi beberapa saat.');

        try {
            $stage = Cache::get($cacheKey);
            abort_unless(is_array($stage)
                && $stage['user_id'] === (int) $request->user()->id
                && $stage['school_id'] === (int) $request->user()->school_id, 404, 'Preview impor sudah kedaluwarsa atau tidak tersedia.');
            abort_if($stage['invalid_rows'] !== [] || $stage['valid_rows'] === [], 422, 'Perbaiki semua baris yang bermasalah lalu unggah ulang sebelum impor.');

            $result = $this->importService->importStudentsBatch(
                schoolId: (int) $request->user()->school_id,
                rows: $stage['valid_rows'],
                enrollTermId: $stage['enroll_term_id'],
                enrollClassGroupId: $stage['enroll_class_group_id'],
                actor: $request->user()
            );
            Cache::forget($cacheKey);

            return redirect()->route('academic.students.index')->with('message', "Berhasil mengimpor {$result['imported_count']} siswa.");
        } finally {
            $lock->release();
        }
    }

    private function importCacheKey(string $token): string
    {
        return 'academic.student-import.'.hash('sha256', $token);
    }

    public function template(): Response
    {
        $csvHeader = "nis,nisn,name,gender,birth_place,birth_date,religion,address,parent_phone,parent_name\n";
        $csvExample = "1001,0012345678,Budi Santoso,L,Jakarta,2012-05-10,Islam,Jl. Melati No. 12,081234567890,Bambang Santoso\n";

        return response($csvHeader.$csvExample, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_impor_siswa.csv"',
        ]);
    }
}
