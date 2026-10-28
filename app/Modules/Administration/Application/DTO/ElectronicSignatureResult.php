<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

final readonly class ElectronicSignatureResult
{
    /** @param array<string, mixed> $certificateMetadata */
    public function __construct(public string $providerRequestId, public string $signedPdfBytes, public array $certificateMetadata = []) {}
}
