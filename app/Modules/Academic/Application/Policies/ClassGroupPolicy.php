<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Core\Domain\PermissionEnum;

class ClassGroupPolicy
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
        return $actor->hasPermission(PermissionEnum::ACADEMIC_CLASSES_MANAGE->value)
            || $actor->hasRole('operator')
            || $actor->hasRole('principal')
            || $actor->hasRole('teacher')
            || $actor->hasRole('homeroom')
            || $actor->hasRole('student');
    }

    public function view(User $actor, ClassGroup $classGroup): bool
    {
        if ($actor->school_id !== $classGroup->school_id) {
            return false;
        }

        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(PermissionEnum::ACADEMIC_CLASSES_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function update(User $actor, ClassGroup $classGroup): bool
    {
        if ($actor->school_id !== $classGroup->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_CLASSES_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function delete(User $actor, ClassGroup $classGroup): bool
    {
        if ($actor->school_id !== $classGroup->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_CLASSES_MANAGE->value)
            || $actor->hasRole('operator');
    }
}
