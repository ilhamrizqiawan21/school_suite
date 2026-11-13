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
            'status' => ['nullable', 'string', 'in:attention,complete,incomplete,late,revision'],
            'sort' => ['nullable', 'string', 'in:student_asc,score_desc,score_asc,completion_asc,completion_desc,attention_desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $term = $this->academicQuery->getActiveTerm((int) $user->school_id);
        if (! $term) {
            return Inertia::render('Learning/Progress', [
                'active_term' => null,
                'rows' => [],
                'student_groups' => [],
                'classes' => [],
                'subjects' => [],
                'pagination' => ['links' => [], 'from' => null, 'to' => null, 'total' => 0],
                'summary' => [
                    'students' => 0, 'materials_read' => 0, 'materials_total' => 0,
                    'assignments_submitted' => 0, 'assignments_total' => 0,
                    'late' => 0, 'revisions' => 0, 'average_score' => null,
                    'complete_rows' => 0, 'attention_rows' => 0, 'incomplete_rows' => 0,
                ],
                'filters' => ['class' => '', 'subject' => '', 'search' => '', 'status' => '', 'sort' => 'student_asc'],
                'personal_attendance' => null,
                'is_student' => $user->hasRole('student'),
                'can_manage_lms' => $user->hasRole(['teacher', 'homeroom', 'student']),
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
                $materialsReadCount = $studentReads->count();
                $materialsTotalCount = $subjectMaterials->count();
                $assignmentsSubmittedCount = $studentSubmissions->count();
                $assignmentsTotalCount = $subjectTasks->count();
                $lateCount = $studentSubmissions->where('status', 'late')->count();
                $revisionCount = $studentSubmissions->where('review_status', 'needs_revision')->count();
                $totalActivities = $materialsTotalCount + $assignmentsTotalCount;
                $completedActivities = $materialsReadCount + $assignmentsSubmittedCount;
                $completionPercentage = $totalActivities > 0 ? (int) round(($completedActivities / $totalActivities) * 100) : 100;
                $isComplete = $totalActivities > 0 && $completedActivities >= $totalActivities && $revisionCount === 0;
                $needsAttention = $lateCount > 0 || $revisionCount > 0 || ($totalActivities > 0 && $completionPercentage < 50);

                $rows[] = [
                    'row_key' => $enrollment->id.'-'.$subjectId,
                    'enrollment_id' => $enrollment->id,
                    'class_group_id' => (int) $enrollment->class_group_id,
                    'subject_id' => (int) $subjectId,
                    'nis' => $enrollment->student->nis,
                    'student_name' => $enrollment->student->name,
                    'class_name' => $assignment->classGroup->name,
                    'subject_name' => $assignment->subject->name,
                    'materials_read' => $materialsReadCount,
                    'materials_total' => $materialsTotalCount,
                    'assignments_submitted' => $assignmentsSubmittedCount,
                    'assignments_total' => $assignmentsTotalCount,
                    'late_count' => $lateCount,
                    'revision_count' => $revisionCount,
                    'graded_count' => $graded->count(),
                    'average_score' => $scorePercentages->isEmpty() ? null : round($scorePercentages->avg(), 1),
                    'completion_percentage' => $completionPercentage,
                    'is_complete' => $isComplete,
                    'needs_attention' => $needsAttention,
                ];
            }
        }

        $query = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        if ($query !== '') {
            $rows = array_values(array_filter($rows, fn (array $row) => str_contains(mb_strtolower(implode(' ', [$row['nis'], $row['student_name'], $row['subject_name'], $row['class_name']])), $query)));
        }

        $completeRowsCount = count(array_filter($rows, fn (array $row) => $row['is_complete']));
        $attentionRowsCount = count(array_filter($rows, fn (array $row) => $row['needs_attention']));
        $incompleteRowsCount = count(array_filter($rows, fn (array $row) => ! $row['is_complete'] && ($row['materials_total'] + $row['assignments_total']) > 0));

        $statusFilter = (string) ($filters['status'] ?? '');
        if ($statusFilter !== '') {
            $rows = array_values(array_filter($rows, function (array $row) use ($statusFilter): bool {
                return match ($statusFilter) {
                    'attention' => $row['needs_attention'],
                    'complete' => $row['is_complete'],
                    'incomplete' => ! $row['is_complete'] && ($row['materials_total'] + $row['assignments_total']) > 0,
                    'late' => $row['late_count'] > 0,
                    'revision' => $row['revision_count'] > 0,
                    default => true,
                };
            }));
        }

        $sortBy = (string) ($filters['sort'] ?? 'student_asc');
        usort($rows, function (array $a, array $b) use ($sortBy): int {
            return match ($sortBy) {
                'score_desc' => ($b['average_score'] ?? -1) <=> ($a['average_score'] ?? -1),
                'score_asc' => ($a['average_score'] ?? 999) <=> ($b['average_score'] ?? 999),
                'completion_asc' => $a['completion_percentage'] <=> $b['completion_percentage'],
                'completion_desc' => $b['completion_percentage'] <=> $a['completion_percentage'],
                'attention_desc' => (($b['revision_count'] * 10 + $b['late_count'] * 5 + (100 - $b['completion_percentage'])) <=> ($a['revision_count'] * 10 + $a['late_count'] * 5 + (100 - $a['completion_percentage']))),
                default => strcmp($a['student_name'].' '.$a['subject_name'], $b['student_name'].' '.$b['subject_name']),
            };
        });

        $summary = [
            'students' => count(array_unique(array_column($rows, 'enrollment_id'))),
            'materials_read' => array_sum(array_column($rows, 'materials_read')),
            'materials_total' => array_sum(array_column($rows, 'materials_total')),
            'assignments_submitted' => array_sum(array_column($rows, 'assignments_submitted')),
            'assignments_total' => array_sum(array_column($rows, 'assignments_total')),
            'late' => array_sum(array_column($rows, 'late_count')),
            'revisions' => array_sum(array_column($rows, 'revision_count')),
            'complete_rows' => $completeRowsCount,
            'attention_rows' => $attentionRowsCount,
            'incomplete_rows' => $incompleteRowsCount,
        ];
        $gradedCount = array_sum(array_column($rows, 'graded_count'));
        $weightedScore = array_sum(array_map(fn (array $row) => ($row['average_score'] ?? 0) * $row['graded_count'], $rows));
        $summary['average_score'] = $gradedCount ? round($weightedScore / $gradedCount, 1) : null;

        $studentGroups = [];
        if (! $user->hasRole('student')) {
            $groupedByEnrollment = collect($rows)->groupBy('enrollment_id');
            foreach ($groupedByEnrollment as $enrollmentId => $studentRows) {
                $firstRow = $studentRows->first();
                $matRead = $studentRows->sum('materials_read');
                $matTotal = $studentRows->sum('materials_total');
                $taskSub = $studentRows->sum('assignments_submitted');
                $taskTotal = $studentRows->sum('assignments_total');
                $lateSum = $studentRows->sum('late_count');
                $revSum = $studentRows->sum('revision_count');
                $gradedSum = $studentRows->sum('graded_count');
                $weightedSum = $studentRows->sum(fn (array $r) => ($r['average_score'] ?? 0) * $r['graded_count']);
                $totAct = $matTotal + $taskTotal;
                $compAct = $matRead + $taskSub;
                $compPct = $totAct > 0 ? (int) round(($compAct / $totAct) * 100) : 100;

                $studentGroups[] = [
                    'enrollment_id' => (int) $enrollmentId,
                    'nis' => $firstRow['nis'],
                    'student_name' => $firstRow['student_name'],
                    'class_name' => $firstRow['class_name'],
                    'class_group_id' => $firstRow['class_group_id'],
                    'subjects_count' => $studentRows->count(),
                    'materials_read' => $matRead,
                    'materials_total' => $matTotal,
                    'assignments_submitted' => $taskSub,
                    'assignments_total' => $taskTotal,
                    'late_count' => $lateSum,
                    'revision_count' => $revSum,
                    'completion_percentage' => $compPct,
                    'average_score' => $gradedSum > 0 ? round($weightedSum / $gradedSum, 1) : null,
                    'needs_attention' => $studentRows->contains(fn (array $r) => $r['needs_attention']),
                    'subjects' => $studentRows->values()->all(),
                ];
            }
        }

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
            'student_groups' => array_slice($studentGroups, 0, 30),
            'classes' => $classes,
            'subjects' => $subjects,
            'pagination' => [
                'links' => $paginator->linkCollection(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'summary' => $summary,
            'filters' => [
                'class' => (string) ($classFilter ?? ''),
                'subject' => (string) ($subjectFilter ?? ''),
                'search' => (string) ($filters['search'] ?? ''),
                'status' => $statusFilter,
                'sort' => $sortBy,
            ],
            'personal_attendance' => $personalAttendance,
            'is_student' => $user->hasRole('student'),
            'can_manage_lms' => $user->hasRole(['teacher', 'homeroom', 'student']),
        ]);
    }
}
