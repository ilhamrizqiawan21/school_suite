<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherDirectoryService
{
    /** @param list<int> $excludedUserIds
     * @return Collection<int, object>
     */
    public function activeTeacherUsersForSchool(int $schoolId, array $excludedUserIds = []): Collection
    {
        return DB::table('teachers')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('teachers.school_id', $schoolId)
            ->where('teachers.is_active', true)
            ->where('users.school_id', $schoolId)
            ->where('users.is_active', true)
            ->where('roles.name', 'teacher')
            ->when($excludedUserIds !== [], fn ($query) => $query->whereNotIn('users.id', $excludedUserIds))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email']);
    }

    public function isActiveTeacherUser(int $schoolId, int $userId): bool
    {
        return DB::table('teachers')
            ->join('users', 'users.id', '=', 'teachers.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('teachers.school_id', $schoolId)
            ->where('teachers.user_id', $userId)
            ->where('teachers.is_active', true)
            ->where('users.school_id', $schoolId)
            ->where('users.is_active', true)
            ->where('roles.name', 'teacher')
            ->exists();
    }
}
