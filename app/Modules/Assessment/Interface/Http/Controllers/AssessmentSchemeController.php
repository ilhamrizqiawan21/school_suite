<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Application\Services\AssessmentAccessService;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AssessmentSchemeController extends Controller
{
    public function __construct(private readonly AssessmentAccessService $access) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', AssessmentScheme::class);

        $schoolId = (int) $request->user()->school_id;

        $schemes = AssessmentScheme::where('school_id', $schoolId)
            ->with('components')
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->orderByDesc('version')
            ->get();

        return Inertia::render('Assessment/Schemes/Index', [
            'schemes' => $schemes,
            'can_manage' => Gate::allows('manageSchemes', AssessmentScheme::class),
            'teaching_assignments' => DB::table('teaching_assignments')
                ->join('class_groups', 'class_groups.id', '=', 'teaching_assignments.class_group_id')
                ->join('subjects', 'subjects.id', '=', 'teaching_assignments.subject_id')
                ->join('terms', 'terms.id', '=', 'teaching_assignments.term_id')
                ->join('academic_years', 'academic_years.id', '=', 'terms.academic_year_id')
                ->where('class_groups.school_id', $schoolId)->where('academic_years.school_id', $schoolId)
                ->whereColumn('class_groups.academic_year_id', 'academic_years.id')
                ->orderByDesc('terms.start_date')->orderBy('class_groups.name')->orderBy('subjects.name')
                ->get(['teaching_assignments.id', 'class_groups.name as class_name', 'subjects.name as subject_name', 'terms.name as term_name', 'academic_years.name as academic_year_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manageSchemes', AssessmentScheme::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50'],
            'rounding_rule' => ['required', 'in:half_up,floor,ceil'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'passing_grade_default' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $schoolId = (int) $request->user()->school_id;

        DB::transaction(function () use ($schoolId, $validated): void {
            AssessmentScheme::where('school_id', $schoolId)->lockForUpdate()->get(['id']);
            $lastVersion = (int) AssessmentScheme::where('school_id', $schoolId)
                ->where('code', $validated['code'])
                ->max('version');

            // Raport memakai satu skema aktif per sekolah, jadi versi baru menggantikan versi aktif sebelumnya.
            AssessmentScheme::where('school_id', $schoolId)->where('is_active', true)->update(['is_active' => false]);

            AssessmentScheme::create([
                'school_id' => $schoolId,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'version' => $lastVersion + 1,
                'is_active' => true,
                'rounding_rule' => RoundingRuleEnum::from($validated['rounding_rule']),
                'decimal_places' => (int) $validated['decimal_places'],
                'passing_grade_default' => (float) $validated['passing_grade_default'],
            ]);
        });

        return back()->with('message', 'Skema penilaian baru berhasil disimpan.');
    }

    public function storeComponent(Request $request, AssessmentScheme $scheme): RedirectResponse
    {
        Gate::authorize('manageSchemes', AssessmentScheme::class);
        abort_unless((int) $scheme->school_id === (int) $request->user()->school_id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'weight_percentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'sequence' => ['nullable', 'integer'],
            'teaching_assignment_id' => ['nullable', 'integer', 'exists:teaching_assignments,id'],
        ]);

        if (! empty($validated['teaching_assignment_id'])) {
            $this->authorizeAssignment((int) $request->user()->school_id, (int) $validated['teaching_assignment_id']);
        }

        AssessmentComponent::create([
            'teaching_assignment_id' => $validated['teaching_assignment_id'] ?? null,
            'scheme_id' => $scheme->id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'weight_percentage' => (float) $validated['weight_percentage'],
            'passing_grade' => (float) $validated['passing_grade'],
            'sequence' => (int) ($validated['sequence'] ?? 1),
        ]);

        return back()->with('message', 'Komponen penilaian berhasil ditambahkan.');
    }

    public function bindComponent(Request $request, AssessmentScheme $scheme, AssessmentComponent $component): RedirectResponse
    {
        Gate::authorize('manageSchemes', AssessmentScheme::class);
        abort_unless((int) $scheme->school_id === (int) $request->user()->school_id && (int) $component->scheme_id === (int) $scheme->id, 404);
        $data = $request->validate(['teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id']]);
        $this->authorizeAssignment((int) $request->user()->school_id, (int) $data['teaching_assignment_id']);

        DB::transaction(function () use ($component, $data): void {
            $locked = AssessmentComponent::whereKey($component->id)->lockForUpdate()->firstOrFail();
            if ($locked->teaching_assignment_id !== null && (int) $locked->teaching_assignment_id !== (int) $data['teaching_assignment_id']) {
                throw ValidationException::withMessages(['teaching_assignment_id' => 'Komponen yang sudah dipetakan tidak dapat dipindah. Buat komponen baru untuk mata pelajaran lain.']);
            }
            $assignment = DB::table('teaching_assignments')->find($data['teaching_assignment_id']);
            if ($locked->scores()->whereIn('enrollment_id', DB::table('enrollments')->where(fn ($query) => $query->where('term_id', '!=', $assignment->term_id)->orWhere('class_group_id', '!=', $assignment->class_group_id))->select('id'))->exists()) {
                throw ValidationException::withMessages(['teaching_assignment_id' => 'Komponen memuat nilai dari kelas atau semester lain. Pisahkan nilai tersebut sebelum memetakan komponen.']);
            }
            $locked->update(['teaching_assignment_id' => $data['teaching_assignment_id']]);
        });

        return back()->with('message', 'Komponen berhasil dipetakan ke mata pelajaran.');
    }

    private function authorizeAssignment(int $schoolId, int $assignmentId): void
    {
        $assignment = DB::table('teaching_assignments')->find($assignmentId);
        abort_unless($assignment && $this->access->classBelongsToSchool($schoolId, (int) $assignment->term_id, (int) $assignment->class_group_id), 404);
    }
}
