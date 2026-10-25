<?php

declare(strict_types=1);

namespace App\Modules\Learning\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;

class LearningPolicy
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
    ) {}

    public function before(User $actor, string $ability): ?bool
    {
        return $actor->is_active ? null : false;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->school_id !== null && $actor->hasRole(['teacher', 'homeroom', 'student']);
    }

    public function manage(User $actor, LearningMaterial|LearningAssignment $resource): bool
    {
        if ((int) $actor->school_id !== (int) $resource->school_id) {
            return false;
        }

        return $actor->hasRole(['teacher', 'homeroom'])
            && (int) $resource->teachingAssignment()->whereHas('teacher', fn ($query) => $query->where('user_id', $actor->id))->value('id') > 0;
    }

    public function view(User $actor, LearningMaterial|LearningAssignment $resource): bool
    {
        if ((int) $actor->school_id !== (int) $resource->school_id) {
            return false;
        }

        if ($actor->hasRole(['teacher', 'homeroom'])) {
            return $resource->teachingAssignment()
                ->whereHas('teacher', fn ($query) => $query->where('user_id', $actor->id))
                ->exists();
        }

        return $actor->hasRole('student')
            && $this->academicQuery->getStudentEnrollmentForClassTerm(
                (int) $actor->id,
                (int) $resource->teachingAssignment->term_id,
                (int) $resource->teachingAssignment->class_group_id
            ) !== null;
    }

    public function submit(User $actor, LearningAssignment $assignment): bool
    {
        if (! $actor->hasRole('student') || (int) $actor->school_id !== (int) $assignment->school_id) {
            return false;
        }

        return $this->academicQuery->getStudentEnrollmentForClassTerm(
            (int) $actor->id,
            (int) $assignment->teachingAssignment->term_id,
            (int) $assignment->teachingAssignment->class_group_id
        ) !== null;
    }

    public function grade(User $actor, AssignmentSubmission $submission): bool
    {
        return $this->manage($actor, $submission->assignment);
    }
}
