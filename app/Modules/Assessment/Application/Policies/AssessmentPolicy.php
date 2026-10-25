<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Policies;

use App\Models\User;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use Illuminate\Support\Facades\DB;

class AssessmentPolicy
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
        return ! $actor->hasRole('guest');
    }

    public function inputScores(User $actor): bool
    {
        return $actor->hasRole('teacher') || $actor->hasRole('homeroom') || $actor->hasRole('operator');
    }

    public function manageSchemes(User $actor): bool
    {
        return $actor->hasRole('principal') || $actor->hasRole('operator');
    }

    public function publishReports(User $actor): bool
    {
        return $actor->hasRole('homeroom') || $actor->hasRole('principal');
    }

    public function viewReport(User $actor, ReportSnapshot $report): bool
    {
        if ($actor->school_id !== $report->school_id) {
            return false;
        }

        if ($actor->hasRole('admin') || $actor->hasRole('principal') || $actor->hasRole('teacher') || $actor->hasRole('homeroom')) {
            return true;
        }

        // Jika siswa, hanya dapat melihat rapor miliknya sendiri
        if ($actor->hasRole('student')) {
            $student = DB::table('students')
                ->where('user_id', $actor->id)
                ->first();

            return $student !== null && (int) $report->student_id === (int) $student->id;
        }

        return false;
    }
}
