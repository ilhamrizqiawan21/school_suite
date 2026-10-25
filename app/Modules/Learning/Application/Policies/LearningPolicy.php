<?php

declare(strict_types=1);

namespace App\Modules\Learning\Application\Policies;

use App\Models\User;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;

class LearningPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        return $actor->is_active ? null : false;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->school_id !== null;
    }

    public function manage(User $actor, LearningMaterial|LearningAssignment $resource): bool
    {
        if ((int) $actor->school_id !== (int) $resource->school_id) {
            return false;
        }

        if ($actor->hasRole(['admin', 'operator'])) {
            return true;
        }

        return $actor->hasRole(['teacher', 'homeroom'])
            && (int) $resource->teachingAssignment()->whereHas('teacher', fn ($query) => $query->where('user_id', $actor->id))->value('id') > 0;
    }

    public function view(User $actor, LearningMaterial|LearningAssignment $resource): bool
    {
        if ((int) $actor->school_id !== (int) $resource->school_id) {
            return false;
        }

        if ($actor->hasRole(['admin', 'operator', 'principal'])) {
            return true;
        }

        if ($actor->hasRole(['teacher', 'homeroom'])) {
            return $resource->teachingAssignment()
                ->whereHas('teacher', fn ($query) => $query->where('user_id', $actor->id))
                ->exists();
        }

        return $actor->hasRole('student')
            && Enrollment::query()
                ->whereHas('student', fn ($query) => $query->where('user_id', $actor->id))
                ->where('term_id', $resource->teachingAssignment->term_id)
                ->where('class_group_id', $resource->teachingAssignment->class_group_id)
                ->where('status', 'active')
                ->exists();
    }

    public function submit(User $actor, LearningAssignment $assignment): bool
    {
        if (! $actor->hasRole('student') || (int) $actor->school_id !== (int) $assignment->school_id) {
            return false;
        }

        return Enrollment::query()
            ->whereHas('student', fn ($query) => $query->where('user_id', $actor->id))
            ->where('term_id', $assignment->teachingAssignment->term_id)
            ->where('class_group_id', $assignment->teachingAssignment->class_group_id)
            ->where('status', 'active')
            ->exists();
    }

    public function grade(User $actor, AssignmentSubmission $submission): bool
    {
        return $this->manage($actor, $submission->assignment);
    }
}
