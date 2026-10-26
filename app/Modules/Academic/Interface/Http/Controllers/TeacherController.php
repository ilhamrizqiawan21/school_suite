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
use Illuminate\Support\Facades\Gate;
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

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Teacher::class);

        $request->validate([
            'csv_file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt'],
            'csv_content' => ['required_without:csv_file', 'nullable', 'string'],
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

            $result = $this->importService->importTeachersBatch(
                schoolId: $schoolId,
                rows: $rows,
                actor: $request->user()
            );

            return back()->with('message', "Berhasil mengimpor {$result['imported_count']} pendidik/guru.");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['csv_file' => $e->getMessage()]);
        }
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
