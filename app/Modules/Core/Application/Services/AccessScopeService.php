<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Domain\RoleEnum;

/**
 * Server-side cross-module authorization and scope enforcement (M4-FND-02, M4-FND-03).
 */
class AccessScopeService
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
    ) {}

    public function belongsToSchool(?User $user, int $schoolId): bool
    {
        return $user !== null
            && $user->is_active
            && $user->school_id !== null
            && (int) $user->school_id === $schoolId;
    }

    /**
     * @param  list<string>  $roles
     */
    public function hasSchoolRole(?User $user, int $schoolId, array $roles): bool
    {
        return $this->belongsToSchool($user, $schoolId) && $user->hasRole($roles);
    }

    public function hasSchoolPermission(?User $user, int $schoolId, string $permission): bool
    {
        return $this->belongsToSchool($user, $schoolId) && $user->hasPermission($permission);
    }

    /**
     * Determine whether an actor can access a specific student's operational records
     * within the same school (staff with school-wide roles, homeroom of active class,
     * verified guardian, or the student themselves).
     */
    public function canAccessStudentScope(?User $user, int $schoolId, int $studentId): bool
    {
        if (! $this->belongsToSchool($user, $schoolId)) {
            return false;
        }

        if ($user->hasRole([
            RoleEnum::ADMIN->value,
            RoleEnum::OPERATOR->value,
            RoleEnum::PRINCIPAL->value,
            RoleEnum::COUNSELOR->value,
            RoleEnum::PTSP->value,
            RoleEnum::PICKET->value,
        ])) {
            return $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId) !== null;
        }

        if ($user->hasRole(RoleEnum::HOMEROOM->value)) {
            return in_array(
                $studentId,
                $this->academicQuery->getHomeroomStudentIdsForUser($schoolId, (int) $user->id),
                true,
            );
        }

        if ($user->hasRole(RoleEnum::PARENT->value)) {
            return in_array(
                $studentId,
                $this->academicQuery->getGuardianStudentIdsForUser($schoolId, (int) $user->id),
                true,
            );
        }

        if ($user->hasRole(RoleEnum::STUDENT->value)) {
            return in_array(
                $studentId,
                $this->academicQuery->getStudentIdsForUser($schoolId, (int) $user->id),
                true,
            );
        }

        return false;
    }
}
