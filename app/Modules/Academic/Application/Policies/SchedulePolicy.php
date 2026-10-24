<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Core\Domain\PermissionEnum;

class SchedulePolicy
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
        return true; // Seluruh pengguna terautentikasi dapat melihat jadwal pelajaran
    }

    public function view(User $actor, Schedule $schedule): bool
    {
        return $actor->school_id === $schedule->school_id;
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(PermissionEnum::ACADEMIC_SCHEDULE_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function update(User $actor, Schedule $schedule): bool
    {
        if ($actor->school_id !== $schedule->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_SCHEDULE_MANAGE->value)
            || $actor->hasRole('operator');
    }

    public function delete(User $actor, Schedule $schedule): bool
    {
        if ($actor->school_id !== $schedule->school_id) {
            return false;
        }

        return $actor->hasPermission(PermissionEnum::ACADEMIC_SCHEDULE_MANAGE->value)
            || $actor->hasRole('operator');
    }
}
