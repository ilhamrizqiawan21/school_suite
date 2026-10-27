<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\AssessmentAccessService;
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
        protected AcademicQueryService $academicQuery,
        protected AssessmentAccessService $access
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('inputScores', Score::class);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);

        $classes = $activeTerm
            ? $this->academicQuery->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();

        $classes = $classes->filter(fn ($class) => $this->access->canViewClass($request->user(), $activeTerm->id, (int) $class->id))->values();
        $teacherUserId = $request->user()->hasRole(['teacher', 'homeroom']) ? (int) $request->user()->id : null;
        $teachingAssignments = $activeTerm
            ? $this->academicQuery->getLearningTeachingAssignments($activeTerm->id, $teacherUserId)
                ->filter(fn ($assignment) => $this->access->canViewClass($request->user(), $activeTerm->id, (int) $assignment->class_group_id))
                ->values()
            : collect();
        $filters = $request->validate([
            'class_group_id' => ['nullable', 'integer'],
            'teaching_assignment_id' => ['nullable', 'integer'],
            'component_id' => ['nullable', 'integer'],
        ]);
        $selectedAssignment = ! empty($filters['teaching_assignment_id'])
            ? $teachingAssignments->firstWhere('id', (int) $filters['teaching_assignment_id'])
            : null;
        abort_if(! empty($filters['teaching_assignment_id']) && ! $selectedAssignment, 403);
        $scheme = AssessmentScheme::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('components')
            ->first();
        $classGroupId = $filters['class_group_id'] ?? $selectedAssignment?->class_group_id ?? $classes->first()?->id;
        abort_if($classGroupId && ! $classes->contains('id', (int) $classGroupId), 403);
        if (! $selectedAssignment && $teachingAssignments->isNotEmpty()) {
            $selectedAssignment = $teachingAssignments->first(function ($assignment) use ($scheme, $request) {
                return $scheme?->components->contains(fn ($component) =>
                    (int) ($component->teaching_assignment_id ?? 0) === (int) $assignment->id
                    && $this->access->canInputComponent($request->user(), $component, (int) $assignment->term_id, (int) $assignment->class_group_id)
                ) ?? false;
            }) ?? $teachingAssignments->firstWhere('class_group_id', (int) $classGroupId)
                ?? $teachingAssignments->first();
            $classGroupId = $selectedAssignment->class_group_id;
        }
        abort_if($selectedAssignment && (int) $selectedAssignment->class_group_id !== (int) $classGroupId, 403);
        $componentId = $request->query('component_id');

        $components = $scheme && $activeTerm && $classGroupId
            ? $scheme->components
                ->filter(fn ($component) => $this->access->canInputComponent($request->user(), $component, $activeTerm->id, (int) $classGroupId))
                ->when($selectedAssignment, fn ($items) => $items->filter(fn ($component) =>
                    $component->teaching_assignment_id === null
                    || (int) $component->teaching_assignment_id === (int) $selectedAssignment->id
                ))
                ->values()
            : collect();
        abort_if($componentId && ! $components->contains('id', (int) $componentId), 403);

        $selectedComponent = $componentId ? $components->firstWhere('id', (int) $componentId) : $components->first();

        $studentsWithScores = [];
        if ($activeTerm && $classGroupId && $selectedComponent) {
            $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($activeTerm->id, (int) $classGroupId);
            $scores = Score::whereIn('assessment_component_id', $components->pluck('id'))
                ->whereIn('enrollment_id', $enrollments->pluck('enrollment_id'))
                ->get()
                ->groupBy('enrollment_id');

            foreach ($enrollments as $enr) {
                $studentScores = $scores->get($enr->enrollment_id, collect())->keyBy('assessment_component_id');
                $selectedScore = $studentScores->get($selectedComponent->id);
                $componentScores = $components->mapWithKeys(function ($component) use ($studentScores) {
                    $score = $studentScores->get($component->id);

                    return [(string) $component->id => [
                        'raw_score' => $score?->raw_score,
                        'remedial_score' => $score?->remedial_score,
                        'final_score' => $score?->final_score,
                        'notes' => $score?->notes,
                    ]];
                })->all();
                $studentsWithScores[] = [
                    'enrollment_id' => $enr->enrollment_id,
                    'student_id' => $enr->student_id,
                    'roll_number' => $enr->roll_number,
                    'nis' => $enr->nis,
                    'name' => $enr->name,
                    'gender' => $enr->gender,
                    'raw_score' => $selectedScore?->raw_score,
                    'remedial_score' => $selectedScore?->remedial_score,
                    'final_score' => $selectedScore?->final_score,
                    'notes' => $selectedScore?->notes,
                    'scores' => $componentScores,
                ];
            }
        }

        return Inertia::render('Assessment/Scores/InputGrid', [
            'active_term' => $activeTerm,
            'classes' => $classes,
            'courses' => $teachingAssignments->map(fn ($assignment) => [
                'id' => $assignment->id,
                'class_group_id' => $assignment->class_group_id,
                'class_name' => $assignment->classGroup?->name ?? '-',
                'subject_name' => $assignment->subject?->name ?? '-',
                'subject_code' => $assignment->subject?->code ?? null,
            ])->values(),
            'scheme' => $scheme,
            'components' => $components,
            'selected_class_id' => $classGroupId ? (string) $classGroupId : ($classes->first()?->id ? (string) $classes->first()->id : ''),
            'selected_assignment_id' => $selectedAssignment?->id ? (string) $selectedAssignment->id : '',
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
            'score_batches' => ['nullable', 'array'],
            'score_batches.*.component_id' => ['required', 'exists:assessment_components,id'],
            'score_batches.*.scores' => ['required', 'array'],
            'score_batches.*.scores.*.enrollment_id' => ['required', 'integer'],
            'score_batches.*.scores.*.raw_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'score_batches.*.scores.*.remedial_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'score_batches.*.scores.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $batches = $validated['score_batches'] ?? [[
            'component_id' => $validated['component_id'],
            'scores' => $validated['scores'],
        ]];
        $result = ['saved_count' => 0, 'remedial_count' => 0];
        foreach ($batches as $batch) {
            $batchResult = $this->scoreService->saveBatchScores(
                (int) $request->user()->school_id,
                (int) $batch['component_id'],
                $batch['scores'],
                $request->user()
            );
            $result['saved_count'] += $batchResult['saved_count'];
            $result['remedial_count'] += $batchResult['remedial_count'];
        }

        return back()->with('message', "Nilai berhasil disimpan: {$result['saved_count']} entri diperbarui, {$result['remedial_count']} catatan remedial.");
    }
}
