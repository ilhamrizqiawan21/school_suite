<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Policies;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Core\Domain\PermissionEnum;

class AttendancePolicy
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
        return $actor->hasPermission(PermissionEnum::ACADEMIC_ATTENDANCE_RECORD->value)
            || $actor->hasRole('operator')
            || $actor->hasRole('principal')
            || $actor->hasRole('teacher')
            || $actor->hasRole('homeroom');
    }

    public function record(User $actor, mixed $target = null): bool
    {
        if ($actor->hasRole('principal')) {
            return false;
        }

        if ($actor->hasRole('admin') || $actor->hasRole('operator') || $actor->hasPermission(PermissionEnum::ACADEMIC_ATTENDANCE_RECORD->value)) {
            return true;
        }

        if ($actor->hasRole('teacher') || $actor->hasRole('homeroom')) {
            if ($target instanceof LessonSession) {
                return $target->teacher?->user_id === $actor->id;
            }

            if ($target instanceof AttendanceRecord) {
                if ($target->lesson_session_id && $target->lessonSession) {
                    return $target->lessonSession->teacher?->user_id === $actor->id;
                }

                return true;
            }

            return true;
        }

        return false;
    }
}
