<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Application\Services\TeacherDirectoryService;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use Illuminate\Support\Facades\DB;

class AdmissionsPolicy
{
    public function __construct(private readonly TeacherDirectoryService $teacherDirectory) {}

    public function before(User $actor, string $ability): ?bool
    {
        return $actor->is_active ? null : false;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->school_id !== null && ($actor->hasRole('admin') || $this->isCommitteeMember($actor));
    }

    public function manageCommittee(User $actor): bool
    {
        return $actor->hasRole('admin') && $actor->school_id !== null;
    }

    public function manageApplicant(User $actor, AdmissionApplicant $applicant): bool
    {
        return (int) $actor->school_id === (int) $applicant->school_id
            && ($actor->hasRole('admin') || $this->isCommitteeMember($actor));
    }

    public function isCommitteeMember(User $actor): bool
    {
        return $actor->school_id !== null
            && $actor->hasRole('teacher')
            && DB::table('admission_committee_members')
                ->where('school_id', $actor->school_id)
                ->where('user_id', $actor->id)
                ->exists()
            && $this->teacherDirectory->isActiveTeacherUser((int) $actor->school_id, (int) $actor->id);
    }
}
