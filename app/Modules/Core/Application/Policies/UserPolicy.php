<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Policies;

use App\Models\User;
use App\Modules\Core\Domain\PermissionEnum;

class UserPolicy
{
    /**
     * Intercept checks for system administrator.
     */
    public function before(User $actor, string $ability): ?bool
    {
        // Admins have blanket access, but cannot delete themselves
        if ($actor->hasRole('admin')) {
            if ($ability === 'delete') {
                return null; // fall through to delete() method
            }

            return true;
        }

        // Inactive users are strictly denied from any action
        if (! $actor->is_active) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(PermissionEnum::USERS_VIEW->value);
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $actor, User $target): bool
    {
        // Self-viewing is always permitted
        if ($actor->id === $target->id) {
            return true;
        }

        // Cross-school inspection is strictly forbidden
        if ($actor->school_id !== null && $target->school_id !== null && $actor->school_id !== $target->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::USERS_VIEW->value);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $actor): bool
    {
        return $actor->hasPermission(PermissionEnum::USERS_CREATE->value);
    }

    /**
     * Determine whether the user can update the specific user.
     */
    public function update(User $actor, User $target): bool
    {
        // Self-profile update
        if ($actor->id === $target->id) {
            return true;
        }

        // Cross-school modification is strictly forbidden
        if ($actor->school_id !== null && $target->school_id !== null && $actor->school_id !== $target->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::USERS_UPDATE->value);
    }

    /**
     * Determine whether the user can delete the specific user.
     */
    public function delete(User $actor, User $target): bool
    {
        // A user cannot delete themselves (prevents accidental lockout)
        if ($actor->id === $target->id) {
            return false;
        }

        // Cross-school deletion is strictly forbidden
        if ($actor->school_id !== null && $target->school_id !== null && $actor->school_id !== $target->school_id) {
            return false;
        }

        // Non-admin target can be deleted if actor has delete permission
        return $actor->hasPermission(PermissionEnum::USERS_DELETE->value);
    }
}
