<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Signatures;

use App\Modules\Administration\Application\Contracts\ElectronicSignatureProvider;
use App\Modules\Administration\Application\DTO\ElectronicSignatureResult;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatory;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class BsreElectronicSignatureProvider implements ElectronicSignatureProvider
{
    public function sign(string $pdfBytes, CorrespondenceSignatory $signatory, string $idempotencyKey, array $context = []): ElectronicSignatureResult
    {
        $endpoint = rtrim((string) config('correspondence.bsre.endpoint'), '/');
        $token = (string) config('correspondence.bsre.token');
        if ($endpoint === '' || $token === '') {
            throw new RuntimeException('BSrE belum dikonfigurasi.');
        }

        $response = Http::asMultipart()->acceptJson()->withToken($token)
            ->timeout((int) config('correspondence.bsre.timeout', 30))
            ->attach('file', $pdfBytes, 'document.pdf')
            ->post($endpoint.'/sign', [
                ['name' => 'idempotency_key', 'contents' => $idempotencyKey],
                ['name' => 'signatory_reference', 'contents' => (string) ($signatory->user_id ?? $signatory->id)],
            ]);
        if (! $response->successful()) {
            throw new RuntimeException('BSrE menolak permintaan tanda tangan (HTTP '.$response->status().').');
        }
        $payload = $response->json();
        $signed = base64_decode((string) ($payload['signed_pdf_base64'] ?? ''), true);
        if (! is_string($signed) || ! str_starts_with($signed, '%PDF-')) {
            throw new RuntimeException('Respons BSrE tidak memuat PDF bertanda tangan yang valid.');
        }

        return new ElectronicSignatureResult((string) ($payload['request_id'] ?? $idempotencyKey), $signed, (array) ($payload['certificate'] ?? []));
    }
}
