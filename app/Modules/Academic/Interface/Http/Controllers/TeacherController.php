<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicImportService;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

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

        $query = Teacher::where('school_id', $schoolId)
            ->with('homeroomClassGroups')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q, $status) => $q->where('employment_status', $status))
            ->orderBy('name');

        $teachers = $query->paginate(15)->withQueryString();

        return Inertia::render('Academic/Teachers/Index', [
            'teachers' => $teachers,
            'can_manage' => Gate::allows('create', Teacher::class),
            'filters' => [
                'search' => $search,
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
        ]);

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
        ]);

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
            'csv_file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'csv_content' => ['required_without:csv_file', 'nullable', 'string', 'max:2097152'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $content = $request->hasFile('csv_file')
            ? file_get_contents($request->file('csv_file')->getRealPath())
            : (string) $request->input('csv_content');

        try {
            $rows = $this->importService->parseCsvString($content, ['nip', 'name', 'gender']);

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

    public function template(): Response
    {
        $csvHeader = "nip,name,gender,phone,email,employment_status\n";
        $csvExample = "198501012010011001,Siti Aminah,P,081234567891,siti.aminah@sekolah.sch.id,PNS\n";

        return response($csvHeader.$csvExample, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_impor_guru.csv"',
        ]);
    }
}
