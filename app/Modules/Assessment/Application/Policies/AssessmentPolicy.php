<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Policies;

use App\Models\User;
use App\Modules\Assessment\Application\Services\AssessmentAccessService;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use Illuminate\Support\Facades\DB;

class AssessmentPolicy
{
    public function __construct(private readonly AssessmentAccessService $access) {}

    public function before(User $actor, string $ability): ?bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->hasRole('admin') && ! in_array($ability, ['viewReport', 'publishReports'], true)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $actor): bool
    {
        return ! $actor->hasRole('guest');
    }

    public function inputScores(User $actor): bool
    {
        return $actor->hasRole('teacher') || $actor->hasRole('homeroom') || $actor->hasRole('operator');
    }

    public function manageSchemes(User $actor): bool
    {
        return $actor->hasRole('operator');
    }

    public function publishReports(User $actor, ?ReportSnapshot $report = null): bool
    {
        if ($report) {
            return (int) $actor->school_id === (int) $report->school_id
                && $this->access->canPublishClass($actor, (int) $report->term_id, (int) $report->class_group_id);
        }

        return $actor->hasRole(['admin', 'homeroom']);
    }

    public function viewReport(User $actor, ReportSnapshot $report): bool
    {
        if ($actor->school_id !== $report->school_id) {
            return false;
        }

        if (! $actor->hasRole('student')) {
            return $this->access->canViewClass($actor, (int) $report->term_id, (int) $report->class_group_id);
        }

        // Jika siswa, hanya dapat melihat rapor miliknya sendiri
        if ($actor->hasRole('student')) {
            $student = DB::table('students')
                ->where('user_id', $actor->id)
                ->first();

            return $student !== null
                && (int) $report->student_id === (int) $student->id
                && $report->status === ReportStatusEnum::PUBLISHED;
        }

        return false;
    }
}
