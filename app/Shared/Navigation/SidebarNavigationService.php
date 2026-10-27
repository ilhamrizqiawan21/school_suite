<?php

declare(strict_types=1);

namespace App\Shared\Navigation;

use App\Models\User;
use App\Modules\Academic\Application\Services\TeacherDirectoryService;
use Illuminate\Support\Facades\DB;

class SidebarNavigationService
{
    /** @return list<string> */
    public function visibleKeys(User $user): array
    {
        $visibleKeys = [];

        foreach (config('navigation.roles', []) as $key => $roles) {
            if ($key === 'admissions') {
                if ($this->canAccessAdmissions($user)) {
                    $visibleKeys[] = $key;
                }

                continue;
            }

            if ($roles !== [] && $user->hasRole($roles)) {
                $visibleKeys[] = $key;
            }
        }

        return $visibleKeys;
    }

    public function canAccessAdmissions(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('teacher') || ! $user->school_id) {
            return false;
        }

        $isCommitteeMember = DB::table('admission_committee_members')
            ->where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->exists();

        return $isCommitteeMember
            && app(TeacherDirectoryService::class)->isActiveTeacherUser((int) $user->school_id, (int) $user->id);
    }
}
