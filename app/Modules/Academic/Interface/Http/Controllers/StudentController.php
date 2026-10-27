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
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $students = $this->studentQuery($request, $activeTerm)->paginate(15)->withQueryString();
        $students->through(function (Student $student) use ($schoolId) {
            $account = $student->user;
            $student->setAttribute('account_status', $account === null ? 'no_account'
                : ((int) $account->school_id !== $schoolId || ! $account->hasRole('student') ? 'unavailable'
                    : ($account->must_change_password ? 'default' : 'changed')));
            $student->unsetRelation('user');

            return $student;
        });
        $counts = Student::where('school_id', $schoolId)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('Academic/Students/Index', [
            'students' => $students,
            'stats' => ['active' => (int) ($counts['active'] ?? 0), 'graduated' => (int) ($counts['graduated'] ?? 0), 'exited' => (int) ($counts['transferred'] ?? 0) + (int) ($counts['dropped_out'] ?? 0)],
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
        abort_unless((int) $student->school_id === (int) $request->user()->school_id, 403);
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

        $schoolId = (int) $request->user()->school_id;
        $request->validate([
            'csv_file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'csv_content' => ['required_without:csv_file', 'nullable', 'string', 'max:2097152'],
            'enroll_term_id' => ['nullable', Rule::exists('terms', 'id')->whereIn('academic_year_id', AcademicYear::where('school_id', $schoolId)->select('id'))],
            'enroll_class_group_id' => ['nullable', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)],
        ]);

        try {
            $parsed = $request->hasFile('csv_file')
                ? $this->importService->parseStudentImportFile(
                    $request->file('csv_file')->getRealPath(),
                    strtolower($request->file('csv_file')->getClientOriginalExtension())
                )
                : $this->importService->parseStudentImportCsvString((string) $request->input('csv_content'));
            $rows = $parsed['rows'];

            if (empty($rows)) {
                return back()->withErrors(['csv_file' => 'Berkas kosong atau format header tidak valid.']);
            }

            $termId = $request->filled('enroll_term_id') ? (int) $request->input('enroll_term_id') : null;
            $classGroupId = $request->filled('enroll_class_group_id') ? (int) $request->input('enroll_class_group_id') : null;
            $targetClass = null;
            if ($classGroupId !== null) {
                $targetClass = ClassGroup::query()->where('school_id', $schoolId)->whereKey($classGroupId)->firstOrFail();
                if ($termId === null) {
                    return back()->withErrors(['enroll_term_id' => 'Pilih periode akademik untuk penempatan kelas.']);
                }
                $term = Term::query()->whereKey($termId)->whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))->firstOrFail();
                if ((int) $targetClass->academic_year_id !== (int) $term->academic_year_id) {
                    return back()->withErrors(['enroll_class_group_id' => 'Kelas dan periode harus berasal dari tahun ajaran yang sama.']);
                }
            }

            $validation = $this->importService->validateStudentRows($schoolId, $rows, $targetClass?->name);
            $token = (string) Str::uuid();
            Cache::put($this->importCacheKey($token), [
                'user_id' => (int) $request->user()->id,
                'school_id' => $schoolId,
                'valid_rows' => $validation['valid_rows'],
                'invalid_rows' => $validation['invalid_rows'],
                'enroll_term_id' => $termId,
                'enroll_class_group_id' => $classGroupId,
            ], now()->addMinutes(15));

            $previewRows = collect($validation['valid_rows'])->map(fn (array $row) => [
                'row' => $row['row_index'], 'identifier' => $row['nis'], 'name' => $row['name'],
                'gender' => $row['gender'], 'valid' => true, 'errors' => [],
                'status' => $row['status'],
            ])->concat(collect($validation['invalid_rows'])->map(fn (array $row) => [
                'row' => $row['row_index'], 'identifier' => $row['data']['nis'] ?? '',
                'name' => $row['data']['name'] ?? '', 'gender' => $row['data']['gender'] ?? '',
                'status' => $row['data']['status'] ?? '',
                'valid' => false, 'errors' => $row['errors'],
            ]))->sortBy('row')->values();

            return Inertia::render('Academic/Imports/Preview', [
                'token' => $token,
                'entity' => 'siswa',
                'index_url' => '/akademik/siswa',
                'confirm_url' => "/akademik/siswa/import/{$token}/confirm",
                'rows' => $previewRows,
                'warnings' => $parsed['warnings'],
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

    private function studentQuery(Request $request, ?Term $activeTerm): Builder
    {
        return Student::where('school_id', (int) $request->user()->school_id)
            ->with(['user.role', 'enrollments' => function ($query) use ($activeTerm) {
                $query->where('term_id', $activeTerm?->id ?? 0)->with('classGroup');
            }])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->query('search');
                $query->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")->orWhere('nisn', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('class_group_id'), fn ($query) => $query->whereHas('enrollments', fn ($sub) => $sub
                ->where('term_id', $activeTerm?->id ?? 0)->where('class_group_id', $request->query('class_group_id'))))
            ->latest('id');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Student::class);
        $term = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', (int) $request->user()->school_id))->where('is_active', true)->first();
        $rows = (function () use ($request, $term) {
            yield ['NIS', 'NISN', 'Nama Lengkap', 'L/P', 'Kelas', 'Status'];
            foreach ($this->studentQuery($request, $term)->lazy(500) as $student) {
                yield [$student->nis, $student->nisn ?? '', $student->name, $student->gender,
                    $student->enrollments->first()?->classGroup?->name ?? '',
                    ['active' => 'Aktif', 'graduated' => 'Lulus', 'transferred' => 'Pindah', 'dropped_out' => 'Keluar'][$student->status] ?? $student->status];
            }
        })();

        return $this->spreadsheetDownload('data_siswa.xlsx', $rows);
    }

    public function exportPdf(Request $request, SchoolScopeService $schoolScope): Response
    {
        Gate::authorize('viewAny', Student::class);

        $schoolId = (int) $request->user()->school_id;
        $term = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();
        $students = $this->studentQuery($request, $term)->get();
        $school = (object) $schoolScope->getSchoolDetails($schoolId);

        return Pdf::loadView('academic.students-export', [
            'students' => $students,
            'school' => $school,
            'term' => $term,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'class_group_id' => $request->query('class_group_id'),
            ],
            'generatedAt' => now('Asia/Jakarta'),
        ])->setPaper('a4', 'landscape')->download('data-siswa.pdf');
    }

    private function spreadsheetDownload(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('Data Siswa');
                foreach ($rows as $index => $row) {
                    foreach (array_values($row) as $column => $value) {
                        $sheet->setCellValueExplicit([$column + 1, $index + 1], (string) $value, DataType::TYPE_STRING);
                    }
                }
                $sheet->freezePane('A2');
                $sheet->getStyle('1:1')->getFont()->setBold(true);
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function resetPassword(Request $request, Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('update', $student);
        $account = $student->user;
        if (! $account || (int) $account->school_id !== (int) $student->school_id || ! $account->hasRole('student')) {
            return back()->withErrors(['student' => 'Akun siswa tidak tersedia untuk reset sandi.']);
        }
        $password = Str::password(16, symbols: false);
        DB::transaction(function () use ($account, $password, $request, $student) {
            $account->forceFill(['password' => Hash::make($password), 'must_change_password' => true,
                'password_changed_at' => null, 'remember_token' => Str::random(60)])->save();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $account->id)->delete();
            }
            $this->auditLogger->log('academic.student.password_reset', 'student', (string) $student->id,
                ['user_id' => $account->id], actor: $request->user(), schoolId: (int) $student->school_id);
        });

        return back()->with('message', "Sandi sementara {$student->name}: {$password}. Siswa wajib menggantinya saat login.");
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        abort_unless((int) $student->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('delete', $student);

        return DB::transaction(function () use ($request, $student) {
            Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            foreach (['enrollments', 'student_lifecycle_events', 'report_snapshots', 'student_notes', 'student_incidents',
                'student_achievements', 'assistance_records', 'attendance_dispensations', 'administration_student_ledger_details', 'administration_student_ledger_documents'] as $table) {
                if (DB::table($table)->where('student_id', $student->id)->exists()) {
                    return back()->withErrors(['student' => 'Siswa memiliki riwayat akademik. Gunakan perubahan status atau menu Siklus Siswa.']);
                }
            }
            $snapshot = $student->toArray();
            $student->delete();
            $this->auditLogger->log('academic.student.deleted', 'student', (string) $student->id,
                oldValues: $snapshot, actor: $request->user(), schoolId: (int) $student->school_id);

            return back()->with('message', 'Data siswa berhasil dihapus. Akun pengguna tetap tersedia di manajemen pengguna.');
        });
    }

    public function template(Request $request): Response|StreamedResponse
    {
        Gate::authorize('create', Student::class);
        if ($request->query('format') === 'xlsx') {
            return $this->spreadsheetDownload('template_impor_siswa.xlsx', [
                ['nis', 'nisn', 'name', 'gender', 'birth_place', 'birth_date', 'religion', 'address', 'parent_phone', 'parent_name'],
                ['1001', '0012345678', 'Budi Santoso', 'L', 'Jakarta', '2012-05-10', 'Islam', 'Jl. Melati No. 12', '081234567890', 'Bambang Santoso'],
            ]);
        }

        $csvHeader = "nis,nisn,name,gender,birth_place,birth_date,religion,address,parent_phone,parent_name\n";
        $csvExample = "1001,0012345678,Budi Santoso,L,Jakarta,2012-05-10,Islam,Jl. Melati No. 12,081234567890,Bambang Santoso\n";

        return response($csvHeader.$csvExample, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_impor_siswa.csv"',
        ]);
    }
}
