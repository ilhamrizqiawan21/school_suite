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
use Inertia\Inertia;
use Inertia\Response;

class LearningProgressController extends Controller
{
    public function __construct(private readonly AcademicQueryService $academicQuery) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->hasRole(['admin', 'operator', 'principal', 'teacher', 'homeroom', 'student']), 403);
        $term = $this->academicQuery->getActiveTerm((int) $user->school_id);
        if (! $term) {
            return Inertia::render('Learning/Progress', ['active_term' => null, 'rows' => [], 'personal_attendance' => null, 'is_student' => $user->hasRole('student')]);
        }

        $assignments = $this->academicQuery->getLearningTeachingAssignments($term->id, $user->hasRole(['teacher', 'homeroom']) ? (int) $user->id : null);
        $classIds = $assignments->pluck('class_group_id')->unique();
        $enrollments = $this->academicQuery->getLearningEnrollments($term->id, $classIds->values()->all(), $user->hasRole('student') ? (int) $user->id : null);

        $teachingIds = $assignments->pluck('id');
        $materials = LearningMaterial::whereIn('teaching_assignment_id', $teachingIds)->get(['id', 'teaching_assignment_id']);
        $learningAssignments = LearningAssignment::whereIn('teaching_assignment_id', $teachingIds)->get(['id', 'teaching_assignment_id', 'due_at', 'max_score']);
        $enrollmentIds = $enrollments->pluck('id');
        $reads = LearningMaterialRead::whereIn('enrollment_id', $enrollmentIds)->whereIn('learning_material_id', $materials->pluck('id'))->get();
        $submissions = AssignmentSubmission::whereIn('enrollment_id', $enrollmentIds)->whereIn('learning_assignment_id', $learningAssignments->pluck('id'))->get();

        $rows = [];
        foreach ($enrollments as $enrollment) {
            foreach ($assignments->where('class_group_id', $enrollment->class_group_id) as $assignment) {
                $subjectMaterials = $materials->where('teaching_assignment_id', $assignment->id);
                $subjectTasks = $learningAssignments->where('teaching_assignment_id', $assignment->id);
                $studentReads = $reads->where('enrollment_id', $enrollment->id)->whereIn('learning_material_id', $subjectMaterials->pluck('id'));
                $studentSubmissions = $submissions->where('enrollment_id', $enrollment->id)->whereIn('learning_assignment_id', $subjectTasks->pluck('id'));
                $graded = $studentSubmissions->whereNotNull('score');
                $scorePercentages = $graded->map(function ($submission) use ($subjectTasks): float {
                    $max = (float) ($subjectTasks->firstWhere('id', $submission->learning_assignment_id)?->max_score ?? 0);

                    return $max > 0 ? ((float) $submission->score / $max) * 100 : 0;
                });
                $rows[] = [
                    'enrollment_id' => $enrollment->id, 'nis' => $enrollment->student->nis, 'student_name' => $enrollment->student->name,
                    'class_name' => $assignment->classGroup->name, 'subject_name' => $assignment->subject->name,
                    'materials_read' => $studentReads->count(), 'materials_total' => $subjectMaterials->count(),
                    'assignments_submitted' => $studentSubmissions->count(), 'assignments_total' => $subjectTasks->count(),
                    'late_count' => $studentSubmissions->where('status', 'late')->count(), 'revision_count' => $studentSubmissions->where('review_status', 'needs_revision')->count(),
                    'average_score' => $scorePercentages->isEmpty() ? null : round($scorePercentages->avg(), 1),
                ];
            }
        }

        $personalAttendance = null;
        if ($user->hasRole('student') && $enrollments->isNotEmpty()) {
            $counts = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];
            foreach ($this->academicQuery->getAttendanceAggregates([(int) $enrollments->first()->id]) as $item) {
                $counts[$item->status] = (int) $item->count;
            }
            $total = array_sum($counts);
            $personalAttendance = [...$counts, 'total' => $total, 'percentage' => $total ? round(($counts['present'] / $total) * 100, 1) : 0];
        }

        return Inertia::render('Learning/Progress', ['active_term' => $term, 'rows' => $rows, 'personal_attendance' => $personalAttendance, 'is_student' => $user->hasRole('student')]);
    }
}
