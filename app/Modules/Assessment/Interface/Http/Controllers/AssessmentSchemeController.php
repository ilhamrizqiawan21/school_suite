<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AssessmentSchemeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', AssessmentScheme::class);

        $schoolId = (int) $request->user()->school_id;

        $schemes = AssessmentScheme::where('school_id', $schoolId)
            ->with('components')
            ->orderByDesc('version')
            ->get();

        return Inertia::render('Assessment/Schemes/Index', [
            'schemes' => $schemes,
            'can_manage' => Gate::allows('manageSchemes', AssessmentScheme::class),
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

        // Cari versi terakhir untuk kode skema ini
        $lastVersion = (int) AssessmentScheme::where('school_id', $schoolId)
            ->where('code', $validated['code'])
            ->max('version');

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

        return back()->with('message', 'Skema penilaian baru berhasil disimpan.');
    }

    public function storeComponent(Request $request, AssessmentScheme $scheme): RedirectResponse
    {
        Gate::authorize('manageSchemes', AssessmentScheme::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'weight_percentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'passing_grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'sequence' => ['nullable', 'integer'],
        ]);

        AssessmentComponent::create([
            'scheme_id' => $scheme->id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'weight_percentage' => (float) $validated['weight_percentage'],
            'passing_grade' => (float) $validated['passing_grade'],
            'sequence' => (int) ($validated['sequence'] ?? 1),
        ]);

        return back()->with('message', 'Komponen penilaian berhasil ditambahkan.');
    }
}
