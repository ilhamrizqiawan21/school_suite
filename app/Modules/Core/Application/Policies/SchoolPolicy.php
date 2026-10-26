<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Policies;

use App\Models\User;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Infrastructure\Models\School;

class SchoolPolicy
{
    /**
     * Intercept checks for system administrator.
     */
    public function before(User $actor): ?bool
    {
        if ($actor->hasRole('admin')) {
            return true;
        }

        if (! $actor->is_active) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view the school profile.
     */
    public function view(User $actor, School $school): bool
    {
        // Must belong to this school
        if ($actor->school_id !== $school->id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::SCHOOL_VIEW->value);
    }

    /**
     * Determine whether the user can update the school profile.
     */
    public function update(User $actor, School $school): bool
    {
        if ($actor->hasRole('principal')) {
            return false;
        }

        // Must belong to this school
        if ($actor->school_id !== $school->id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::SCHOOL_UPDATE->value);
    }
}
