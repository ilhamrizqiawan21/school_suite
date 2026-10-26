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
    public function before(User $actor, string $ability): ?bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->hasRole('admin')) {
            if (in_array($ability, ['view', 'update'], true)) {
                return null; // object-level abilities must still enforce school boundaries
            }

            return true;
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
