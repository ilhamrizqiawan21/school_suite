<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Modules\Administration\Infrastructure\Models\UserNotification;

class NotificationService
{
    /** @param iterable<int|string> $userIds */
    public function notifyUsers(iterable $userIds, string $title, string $body, string $actionUrl): void
    {
        foreach (collect($userIds)->map(fn ($id): int => (int) $id)->unique() as $userId) {
            UserNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
            ]);
        }
    }
}
