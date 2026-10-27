<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicImportService;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Shared\Audit\AuditLogger;
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

class TeacherController extends Controller
{
    public function __construct(
        protected AcademicImportService $importService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', Teacher::class);

        $schoolId = (int) $request->user()->school_id;
        $search = $request->query('search');
        $status = $request->query('employment_status');

        $teachers = $this->teacherQuery($request)->paginate(15)->withQueryString();
        $teachers->through(function (Teacher $teacher) use ($schoolId) {
            $account = $teacher->user;
            $teacher->setAttribute('account_status', ! $account ? 'no_account'
                : ((int) $account->school_id !== $schoolId || ! $account->hasRole(['teacher', 'homeroom']) ? 'unavailable'
                    : ($account->must_change_password ? 'default' : 'changed')));
            $teacher->unsetRelation('user');

            return $teacher;
        });
        $all = Teacher::where('school_id', $schoolId);
        $total = (clone $all)->count();
        $active = (clone $all)->where('is_active', true)->count();

        return Inertia::render('Academic/Teachers/Index', [
            'teachers' => $teachers,
            'stats' => ['total' => $total, 'active' => $active, 'inactive' => $total - $active],
            'employment_statuses' => Teacher::where('school_id', $schoolId)->whereNotNull('employment_status')->distinct()->orderBy('employment_status')->pluck('employment_status'),
            'can_manage' => Gate::allows('create', Teacher::class),
            'can_link_accounts' => $request->user()->hasRole('admin'),
            'account_candidates' => $request->user()->hasRole('admin') ? $this->teacherAccountCandidates($schoolId) : [],
            'filters' => [
                'search' => $search,
                'is_active' => $request->query('is_active'),
                'employment_status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Teacher::class);

        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('teachers')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'in:L,P'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'employment_status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('school_id', $schoolId)
                ->where('is_active', true))],
        ]);

        $this->validateTeacherAccountLink($request, $validated['user_id'] ?? null);
        if (! $request->user()->hasRole('admin')) {
            unset($validated['user_id']);
        }

        $validated['school_id'] = $schoolId;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $teacher = Teacher::create($validated);

        $this->auditLogger->log(
            eventType: 'academic.teacher.created',
            targetType: 'teacher',
            targetId: (string) $teacher->id,
            newValues: $teacher->toArray(),
            oldValues: null,
            actor: $request->user(),
            schoolId: $schoolId
        );

        return back()->with('message', "Guru {$teacher->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        abort_unless((int) $teacher->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('update', $teacher);

        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('teachers')->where('school_id', $schoolId)->ignore($teacher->id)],
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'in:L,P'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'employment_status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'user_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('school_id', $schoolId)
                ->where('is_active', true))],
        ]);

        if (! $request->user()->hasRole('admin')) {
            unset($validated['user_id']);
        } elseif (array_key_exists('user_id', $validated)) {
            $this->validateTeacherAccountLink($request, $validated['user_id'], $teacher->id);
        }

        $oldValues = $teacher->toArray();
        $teacher->update($validated);

        $this->auditLogger->log(
            eventType: 'academic.teacher.updated',
            targetType: 'teacher',
            targetId: (string) $teacher->id,
            newValues: $teacher->toArray(),
            oldValues: $oldValues,
            actor: $request->user(),
            schoolId: $schoolId
        );

        return back()->with('message', "Data guru {$teacher->name} berhasil diperbarui.");
    }

    public function import(Request $request): InertiaResponse|RedirectResponse
    {
        return $this->previewImport($request);
    }

    public function previewImport(Request $request): InertiaResponse|RedirectResponse
    {
        Gate::authorize('create', Teacher::class);

        $request->validate([
            'csv_file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'csv_content' => ['required_without:csv_file', 'nullable', 'string', 'max:2097152'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $content = (string) $request->input('csv_content');

        try {
            $rows = $request->hasFile('csv_file')
                ? $this->importService->parseTeacherImportFile($request->file('csv_file')->getRealPath(), strtolower($request->file('csv_file')->getClientOriginalExtension()))
                : $this->importService->parseCsvString($content, ['nip', 'name', 'gender']);

            if (empty($rows)) {
                return back()->withErrors(['csv_file' => 'Berkas CSV kosong atau format header tidak valid.']);
            }

            $validation = $this->importService->validateTeacherRows($schoolId, $rows);
            $token = (string) Str::uuid();
            Cache::put($this->importCacheKey($token), [
                'user_id' => (int) $request->user()->id,
                'school_id' => $schoolId,
                'valid_rows' => $validation['valid_rows'],
                'invalid_rows' => $validation['invalid_rows'],
            ], now()->addMinutes(15));

            $previewRows = collect($validation['valid_rows'])->map(fn (array $row) => [
                'row' => $row['row_index'], 'identifier' => $row['nip'] ?? '', 'name' => $row['name'],
                'gender' => $row['gender'], 'valid' => true, 'errors' => [],
            ])->concat(collect($validation['invalid_rows'])->map(fn (array $row) => [
                'row' => $row['row_index'], 'identifier' => $row['data']['nip'] ?? '',
                'name' => $row['data']['name'] ?? '', 'gender' => $row['data']['gender'] ?? '',
                'valid' => false, 'errors' => $row['errors'],
            ]))->sortBy('row')->values();

            return Inertia::render('Academic/Imports/Preview', [
                'token' => $token,
                'entity' => 'guru',
                'index_url' => '/akademik/guru',
                'confirm_url' => "/akademik/guru/import/{$token}/confirm",
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
        Gate::authorize('create', Teacher::class);
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

            $result = $this->importService->importTeachersBatch(
                schoolId: (int) $request->user()->school_id,
                rows: $stage['valid_rows'],
                actor: $request->user()
            );
            Cache::forget($cacheKey);

            return redirect()->route('academic.teachers.index')->with('message', "Berhasil mengimpor {$result['imported_count']} pendidik/guru.");
        } finally {
            $lock->release();
        }
    }

    private function importCacheKey(string $token): string
    {
        return 'academic.teacher-import.'.hash('sha256', $token);
    }

    private function teacherQuery(Request $request): Builder
    {
        $request->validate(['search' => ['nullable', 'string', 'max:150'],
            'employment_status' => ['nullable', 'string', 'max:50'], 'is_active' => ['nullable', 'in:0,1']]);

        return Teacher::where('school_id', (int) $request->user()->school_id)
            ->with(['user.role', 'homeroomClassGroups' => fn ($query) => $query
                ->whereHas('academicYear', fn ($year) => $year->where('is_active', true))])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->query('search');
                $query->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('employment_status'), fn ($query) => $query->where('employment_status', $request->query('employment_status')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', (int) $request->query('is_active')))
            ->orderBy('name')->orderBy('id');
    }

    /**
     * Akun aktif yang boleh ditautkan ke profil guru.
     * Akun yang sudah dipakai profil lain tetap dikirim agar UI dapat
     * menampilkan statusnya dan mencegah admin memilihnya secara keliru.
     */
    private function teacherAccountCandidates(int $schoolId)
    {
        $linkedTeacherIds = Teacher::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id');

        return User::query()
            ->with('role:id,name,label')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['operator', 'teacher', 'homeroom']))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'role_label' => $user->role?->label,
                'linked_teacher_id' => $linkedTeacherIds->get($user->id),
            ])->values();
    }

    private function validateTeacherAccountLink(Request $request, ?int $userId, ?int $ignoreTeacherId = null): void
    {
        if ($userId === null) {
            return;
        }

        abort_unless($request->user()->hasRole('admin'), 403, 'Hanya admin yang dapat menetapkan akun guru tambahan.');

        $account = User::query()
            ->with('role:id,name')
            ->where('id', $userId)
            ->where('school_id', $request->user()->school_id)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless(in_array($account->role?->name, ['operator', 'teacher', 'homeroom'], true), 422, 'Akun harus memiliki peran TU/operator atau guru.');

        $alreadyLinked = Teacher::query()
            ->where('school_id', $request->user()->school_id)
            ->where('user_id', $userId)
            ->when($ignoreTeacherId !== null, fn ($query) => $query->where('id', '!=', $ignoreTeacherId))
            ->exists();

        abort_unless(! $alreadyLinked, 422, 'Akun tersebut sudah ditautkan ke profil guru lain.');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Teacher::class);
        $teachers = $this->teacherQuery($request);
        $rows = (function () use ($teachers) {
            yield ['NIP', 'Nama', 'L/P', 'Telepon', 'Email', 'Status Kepegawaian', 'Wali Kelas', 'Status'];
            foreach ($teachers->lazy(500) as $teacher) {
                yield [$teacher->nip, $teacher->name, $teacher->gender, $teacher->phone, $teacher->email,
                    $teacher->employment_status, $teacher->homeroomClassGroups->pluck('name')->join(', '), $teacher->is_active ? 'Aktif' : 'Nonaktif'];
            }
        })();

        return $this->spreadsheetDownload('data_guru.xlsx', $rows);
    }

    private function spreadsheetDownload(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('Data Guru');
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

    public function resetPassword(Request $request, Teacher $teacher): RedirectResponse
    {
        abort_unless((int) $teacher->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('update', $teacher);
        $account = $teacher->user;
        if (! $account || (int) $account->school_id !== (int) $teacher->school_id || ! $account->hasRole(['teacher', 'homeroom']) || $account->id === $request->user()->id) {
            return back()->withErrors(['teacher' => 'Akun guru tidak tersedia untuk reset sandi. Gunakan menu profil untuk akun sendiri.']);
        }
        $password = Str::password(16, symbols: false);
        DB::transaction(function () use ($account, $password, $request, $teacher) {
            $account->forceFill(['password' => Hash::make($password), 'must_change_password' => true,
                'password_changed_at' => null, 'remember_token' => Str::random(60)])->save();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $account->id)->delete();
            }
            $this->auditLogger->log('academic.teacher.password_reset', 'teacher', (string) $teacher->id,
                ['user_id' => $account->id], actor: $request->user(), schoolId: (int) $teacher->school_id);
        });

        return back()->with('message', "Sandi sementara {$teacher->name}: {$password}. Guru wajib menggantinya saat login.");
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        abort_unless((int) $teacher->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('delete', $teacher);

        return DB::transaction(function () use ($request, $teacher) {
            Teacher::whereKey($teacher->id)->lockForUpdate()->firstOrFail();
            $hasHistory = $teacher->homeroomClassGroups()->exists() || $teacher->teachingAssignments()->exists();
            foreach (['schedules', 'lesson_sessions', 'teaching_plans', 'teaching_documents', 'teacher_attendance_records', 'teacher_leave_requests'] as $table) {
                $hasHistory = $hasHistory || DB::table($table)->where('teacher_id', $teacher->id)->exists();
            }
            $hasHistory = $hasHistory || DB::table('teacher_substitute_assignments')->where('substitute_teacher_id', $teacher->id)->exists();
            if ($hasHistory) {
                return back()->withErrors(['teacher' => 'Guru memiliki penugasan atau riwayat akademik. Nonaktifkan data guru melalui Ubah.']);
            }
            $snapshot = $teacher->toArray();
            $teacher->delete();
            $this->auditLogger->log('academic.teacher.deleted', 'teacher', (string) $teacher->id,
                oldValues: $snapshot, actor: $request->user(), schoolId: (int) $teacher->school_id);

            return back()->with('message', 'Data guru dihapus. Akun pengguna tetap tersedia di manajemen pengguna.');
        });
    }

    public function template(Request $request): Response|StreamedResponse
    {
        Gate::authorize('create', Teacher::class);
        if ($request->query('format') === 'xlsx') {
            return $this->spreadsheetDownload('template_impor_guru.xlsx', [
                ['nip', 'name', 'gender', 'phone', 'email', 'employment_status'],
                ['198501012010011001', 'Guru Contoh', 'P', '081234567891', 'guru@example.test', 'PNS'],
            ]);
        }

        $csvHeader = "nip,name,gender,phone,email,employment_status\n";
        $csvExample = "198501012010011001,Siti Aminah,P,081234567891,siti.aminah@sekolah.sch.id,PNS\n";

        return response($csvHeader.$csvExample, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_impor_guru.csv"',
        ]);
    }
}
