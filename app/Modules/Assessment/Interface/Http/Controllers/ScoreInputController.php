<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\ScoreService;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\Score;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ScoreInputController extends Controller
{
    public function __construct(
        protected ScoreService $scoreService,
        protected AcademicQueryService $academicQuery
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('inputScores', Score::class);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);

        $classes = $activeTerm
            ? $this->academicQuery->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();

        $classGroupId = $request->query('class_group_id');
        $componentId = $request->query('component_id');

        $scheme = AssessmentScheme::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('components')
            ->first();

        $components = $scheme ? $scheme->components : collect();

        $selectedComponent = $componentId ? $components->firstWhere('id', (int) $componentId) : $components->first();

        $studentsWithScores = [];
        if ($activeTerm && $classGroupId && $selectedComponent) {
            $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($activeTerm->id, (int) $classGroupId);
            $scores = Score::where('assessment_component_id', $selectedComponent->id)
                ->whereIn('enrollment_id', $enrollments->pluck('enrollment_id'))
                ->get()
                ->keyBy('enrollment_id');

            foreach ($enrollments as $enr) {
                $sc = $scores->get($enr->enrollment_id);
                $studentsWithScores[] = [
                    'enrollment_id' => $enr->enrollment_id,
                    'student_id' => $enr->student_id,
                    'roll_number' => $enr->roll_number,
                    'nis' => $enr->nis,
                    'name' => $enr->name,
                    'gender' => $enr->gender,
                    'raw_score' => $sc?->raw_score,
                    'remedial_score' => $sc?->remedial_score,
                    'final_score' => $sc?->final_score,
                    'notes' => $sc?->notes,
                ];
            }
        }

        return Inertia::render('Assessment/Scores/InputGrid', [
            'active_term' => $activeTerm,
            'classes' => $classes,
            'scheme' => $scheme,
            'components' => $components,
            'selected_class_id' => $classGroupId ? (string) $classGroupId : ($classes->first()?->id ? (string) $classes->first()->id : ''),
            'selected_component_id' => $selectedComponent?->id ? (string) $selectedComponent->id : '',
            'students' => $studentsWithScores,
        ]);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        Gate::authorize('inputScores', Score::class);

        $validated = $request->validate([
            'component_id' => ['required', 'exists:assessment_components,id'],
            'scores' => ['required', 'array'],
            'scores.*.enrollment_id' => ['required', 'integer'],
            'scores.*.raw_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.remedial_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->scoreService->saveBatchScores(
            (int) $request->user()->school_id,
            (int) $validated['component_id'],
            $validated['scores'],
            $request->user()
        );

        return back()->with('message', "Nilai berhasil disimpan: {$result['saved_count']} entri diperbarui, {$result['remedial_count']} catatan remedial.");
    }
}
