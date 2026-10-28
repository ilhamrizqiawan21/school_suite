<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Jobs;

use App\Modules\Administration\Application\Services\CorrespondenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCorrespondenceSignature implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $signatureRequestId) {}

    public function handle(CorrespondenceService $service): void
    {
        $service->processSignatureRequest($this->signatureRequestId);
    }
}
