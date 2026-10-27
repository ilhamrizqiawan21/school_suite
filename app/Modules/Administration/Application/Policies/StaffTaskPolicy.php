<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Policies;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\StaffTask;

class StaffTaskPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active && $actor->school_id !== null
            && $actor->hasRole(['admin', 'operator', 'principal', 'teacher', 'homeroom', 'counselor', 'librarian']);
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && $actor->school_id !== null && $actor->hasRole(['admin', 'operator']);
    }

    public function manage(User $actor, StaffTask $task): bool
    {
        return $actor->is_active
            && (int) $actor->school_id === (int) $task->school_id
            && $actor->hasRole(['admin', 'operator']);
    }

    public function updateProgress(User $actor, StaffTask $task): bool
    {
        return $actor->is_active
            && (int) $actor->school_id === (int) $task->school_id
            && (int) $actor->id === (int) $task->assignee_id
            && $actor->hasRole(['principal', 'teacher', 'homeroom', 'counselor', 'librarian']);
    }
}
