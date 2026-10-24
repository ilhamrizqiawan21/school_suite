<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Policies;

use App\Models\User;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Homeroom\Infrastructure\Models\StudentNote;

class StudentNotePolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(PermissionEnum::HOMEROOM_NOTES_VIEW->value)
            || (! $actor->hasRole('student') && ! $actor->hasRole('parent'));
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(PermissionEnum::HOMEROOM_NOTES_MANAGE->value)
            || $actor->hasRole('teacher')
            || $actor->hasRole('homeroom')
            || $actor->hasRole('counselor')
            || $actor->hasRole('principal');
    }

    public function delete(User $actor, StudentNote $note): bool
    {
        if ($actor->school_id !== $note->school_id) {
            return false;
        }

        // Author or Admin can delete
        return $note->author_id === $actor->id || $actor->hasRole('principal');
    }
}
