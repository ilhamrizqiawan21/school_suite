<?php

declare(strict_types=1);

namespace App\Shared\Jobs;

use App\Modules\Core\Application\Services\NotificationOutboxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchNotificationOutboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $outboxId) {}

    public function handle(NotificationOutboxService $outboxService): void
    {
        $outboxService->deliver($this->outboxId);
    }
}
