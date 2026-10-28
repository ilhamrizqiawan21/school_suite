<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Signatures;

use App\Modules\Administration\Application\Contracts\ElectronicSignatureProvider;
use App\Modules\Administration\Application\DTO\ElectronicSignatureResult;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatory;
use RuntimeException;

final class UnavailableElectronicSignatureProvider implements ElectronicSignatureProvider
{
    public function sign(string $pdfBytes, CorrespondenceSignatory $signatory, string $idempotencyKey, array $context = []): ElectronicSignatureResult
    {
        throw new RuntimeException('BSrE belum dikonfigurasi. Dokumen tidak ditandatangani.');
    }
}
