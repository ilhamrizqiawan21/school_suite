<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Core\Domain\PermissionEnum;

class TeacherPolicy
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
        return $actor->hasPermission(PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value)
            || $actor->hasRole('operator')
            || $actor->hasRole('principal')
            || $actor->hasRole('teacher')
            || $actor->hasRole('homeroom');
    }

    public function view(User $actor, Teacher $teacher): bool
    {
        if ($actor->school_id !== $teacher->school_id) {
            return false;
        }

        if ($teacher->user_id === $actor->id) {
            return true;
        }

        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        if ($actor->hasRole('principal')) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function update(User $actor, Teacher $teacher): bool
    {
        if ($actor->hasRole('principal') || $actor->school_id !== $teacher->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function delete(User $actor, Teacher $teacher): bool
    {
        if ($actor->hasRole('principal') || $actor->school_id !== $teacher->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value)
            || $actor->hasRole('operator');
    }
}
