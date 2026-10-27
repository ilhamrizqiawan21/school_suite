<?php

declare(strict_types=1);

namespace App\Modules\Learning\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;
use App\Modules\Learning\Infrastructure\Models\LearningMaterialRead;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class LearningProgressController extends Controller
{
    public function __construct(private readonly AcademicQueryService $academicQuery) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->hasRole(['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student']), 403);
        $filters = $request->validate([
            'class' => ['nullable', 'integer', 'min:1'],
            'subject' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $term = $this->academicQuery->getActiveTerm((int) $user->school_id);
        if (! $term) {
            return Inertia::render('Learning/Progress', [
                'active_term' => null,
                'rows' => [],
                'classes' => [],
                'subjects' => [],
                'pagination' => ['links' => [], 'from' => null, 'to' => null, 'total' => 0],
                'summary' => [
                    'students' => 0, 'materials_read' => 0, 'materials_total' => 0,
                    'assignments_submitted' => 0, 'assignments_total' => 0,
                    'late' => 0, 'revisions' => 0, 'average_score' => null,
                ],
                'filters' => ['class' => '', 'subject' => '', 'search' => ''],
                'personal_attendance' => null,
                'is_student' => $user->hasRole('student'),
            ]);
        }

        $assignments = $this->academicQuery->getLearningTeachingAssignments($term->id, $user->hasRole(['teacher', 'homeroom']) ? (int) $user->id : null);
        $classes = $user->hasRole('student') ? collect() : $assignments->map(fn ($assignment) => ['id' => $assignment->class_group_id, 'name' => $assignment->classGroup->name])
            ->unique('id')->sortBy('name')->values();
        $subjects = $user->hasRole('student') ? collect() : $assignments->map(fn ($assignment) => ['id' => $assignment->subject_id, 'name' => $assignment->subject->name])
            ->unique('id')->sortBy('name')->values();
        $classFilter = $user->hasRole('student') ? null : ($filters['class'] ?? null);
        $subjectFilter = $user->hasRole('student') ? null : ($filters['subject'] ?? null);
        $assignments = $assignments
            ->when($classFilter, fn ($items) => $items->where('class_group_id', (int) $classFilter))
            ->when($subjectFilter, fn ($items) => $items->where('subject_id', (int) $subjectFilter));
        $classIds = $assignments->pluck('class_group_id')->unique();
        $enrollments = $this->academicQuery->getLearningEnrollments($term->id, $classIds->values()->all(), $user->hasRole('student') ? (int) $user->id : null);

        $teachingIds = $assignments->pluck('id');
        $materials = LearningMaterial::whereIn('teaching_assignment_id', $teachingIds)->whereNotNull('published_at')->get(['id', 'teaching_assignment_id']);
        $learningAssignments = LearningAssignment::whereIn('teaching_assignment_id', $teachingIds)->whereNotNull('published_at')->get(['id', 'teaching_assignment_id', 'due_at', 'max_score']);
        $enrollmentIds = $enrollments->pluck('id');
        $reads = LearningMaterialRead::whereIn('enrollment_id', $enrollmentIds)->whereIn('learning_material_id', $materials->pluck('id'))->get();
        $submissions = AssignmentSubmission::whereIn('enrollment_id', $enrollmentIds)
            ->whereIn('learning_assignment_id', $learningAssignments->pluck('id'))
            ->whereNotNull('submitted_at')
            ->get();

        $rows = [];
        foreach ($enrollments as $enrollment) {
            $subjectAssignments = $assignments->where('class_group_id', $enrollment->class_group_id)->groupBy('subject_id');
            foreach ($subjectAssignments as $subjectId => $assignmentGroup) {
                $assignmentIds = $assignmentGroup->pluck('id');
                $assignment = $assignmentGroup->first();
                $subjectMaterials = $materials->whereIn('teaching_assignment_id', $assignmentIds);
                $subjectTasks = $learningAssignments->whereIn('teaching_assignment_id', $assignmentIds);
                $studentReads = $reads->where('enrollment_id', $enrollment->id)->whereIn('learning_material_id', $subjectMaterials->pluck('id'));
                $studentSubmissions = $submissions->where('enrollment_id', $enrollment->id)->whereIn('learning_assignment_id', $subjectTasks->pluck('id'));
                $graded = $studentSubmissions->whereNotNull('score');
                $scorePercentages = $graded->map(function ($submission) use ($subjectTasks): float {
                    $max = (float) ($subjectTasks->firstWhere('id', $submission->learning_assignment_id)?->max_score ?? 0);

                    return $max > 0 ? ((float) $submission->score / $max) * 100 : 0;
                });
                $rows[] = [
                    'row_key' => $enrollment->id.'-'.$subjectId, 'enrollment_id' => $enrollment->id, 'subject_id' => (int) $subjectId, 'nis' => $enrollment->student->nis, 'student_name' => $enrollment->student->name,
                    'class_name' => $assignment->classGroup->name, 'subject_name' => $assignment->subject->name,
                    'materials_read' => $studentReads->count(), 'materials_total' => $subjectMaterials->count(),
                    'assignments_submitted' => $studentSubmissions->count(), 'assignments_total' => $subjectTasks->count(),
                    'late_count' => $studentSubmissions->where('status', 'late')->count(), 'revision_count' => $studentSubmissions->where('review_status', 'needs_revision')->count(),
                    'graded_count' => $graded->count(),
                    'average_score' => $scorePercentages->isEmpty() ? null : round($scorePercentages->avg(), 1),
                ];
            }
        }

        $query = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        if ($query !== '') {
            $rows = array_values(array_filter($rows, fn (array $row) => str_contains(mb_strtolower(implode(' ', [$row['nis'], $row['student_name'], $row['subject_name']])), $query)));
        }

        $summary = [
            'students' => count(array_unique(array_column($rows, 'enrollment_id'))),
            'materials_read' => array_sum(array_column($rows, 'materials_read')),
            'materials_total' => array_sum(array_column($rows, 'materials_total')),
            'assignments_submitted' => array_sum(array_column($rows, 'assignments_submitted')),
            'assignments_total' => array_sum(array_column($rows, 'assignments_total')),
            'late' => array_sum(array_column($rows, 'late_count')),
            'revisions' => array_sum(array_column($rows, 'revision_count')),
        ];
        $gradedCount = array_sum(array_column($rows, 'graded_count'));
        $weightedScore = array_sum(array_map(fn (array $row) => ($row['average_score'] ?? 0) * $row['graded_count'], $rows));
        $summary['average_score'] = $gradedCount ? round($weightedScore / $gradedCount, 1) : null;

        $perPage = 15;
        $lastPage = max(1, (int) ceil(count($rows) / $perPage));
        $currentPage = min($lastPage, max(1, (int) ($filters['page'] ?? 1)));
        $paginator = new LengthAwarePaginator(
            array_slice($rows, ($currentPage - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $personalAttendance = null;
        if ($user->hasRole('student') && $enrollments->isNotEmpty()) {
            $counts = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];
            foreach ($this->academicQuery->getAttendanceAggregates([(int) $enrollments->first()->id]) as $item) {
                $counts[$item->status] = (int) $item->count;
            }
            $total = array_sum($counts);
            $personalAttendance = [...$counts, 'total' => $total, 'percentage' => $total ? round(($counts['present'] / $total) * 100, 1) : 0];
        }

        return Inertia::render('Learning/Progress', [
            'active_term' => $term,
            'rows' => $paginator->items(),
            'classes' => $classes,
            'subjects' => $subjects,
            'pagination' => [
                'links' => $paginator->linkCollection(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'summary' => $summary,
            'filters' => ['class' => (string) ($classFilter ?? ''), 'subject' => (string) ($subjectFilter ?? ''), 'search' => (string) ($filters['search'] ?? '')],
            'personal_attendance' => $personalAttendance,
            'is_student' => $user->hasRole('student'),
        ]);
    }
}
