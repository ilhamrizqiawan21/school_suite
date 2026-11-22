<?php

declare(strict_types=1);

namespace App\Shared\Navigation;

use App\Models\User;
use App\Modules\Admissions\Application\Policies\AdmissionsPolicy;
use Illuminate\Support\Facades\Gate;

class SidebarNavigationService
{
    /** @var list<string> */
    private const ADMISSIONS_NAV_KEYS = [
        'admissions',
        'admissions-settings',
        'admissions-periods',
        'admissions-tracks',
        'admissions-committee',
        'admissions-applicants',
        'admissions-documents',
        'admissions-selection',
        'admissions-announcement',
        'admissions-re-registration',
        'admissions-reports',
    ];

    /** @return list<string> */
    public function visibleKeys(User $user): array
    {
        if (! $user->is_active || $user->school_id === null) {
            return [];
        }
        $visibleKeys = [];

        foreach (config('navigation.roles', []) as $key => $roles) {
            if ($key === 'users') {
                if (Gate::forUser($user)->allows('viewAny', User::class)) {
                    $visibleKeys[] = $key;
                }

                continue;
            }
            if ($key === 'school') {
                if ($user->school && Gate::forUser($user)->allows('view', $user->school)) {
                    $visibleKeys[] = $key;
                }

                continue;
            }
            if ($key === 'admissions') {
                if ($this->canAccessAdmissions($user)) {
                    array_push($visibleKeys, ...self::ADMISSIONS_NAV_KEYS);
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
        return app(AdmissionsPolicy::class)->viewAny($user);
    }
}
