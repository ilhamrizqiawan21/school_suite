<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Core\Domain\PermissionEnum;

class StudentPolicy
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

    public function view(User $actor, Student $student): bool
    {
        if ($actor->school_id !== $student->school_id) {
            return false;
        }

        // Student can view their own profile
        if ($actor->hasRole('student') && $student->user_id === $actor->id) {
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

    public function update(User $actor, Student $student): bool
    {
        if ($actor->hasRole('principal') || $actor->school_id !== $student->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function delete(User $actor, Student $student): bool
    {
        if ($actor->hasRole('principal') || $actor->school_id !== $student->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_STUDENTS_MANAGE->value)
            || $actor->hasRole('operator');
    }
}
