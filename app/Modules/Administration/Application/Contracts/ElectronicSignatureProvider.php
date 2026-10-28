<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Contracts;

use App\Modules\Administration\Application\DTO\ElectronicSignatureResult;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatory;

interface ElectronicSignatureProvider
{
    /** @param array<string, mixed> $context */
    public function sign(string $pdfBytes, CorrespondenceSignatory $signatory, string $idempotencyKey, array $context = []): ElectronicSignatureResult;
}
