<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\ServiceDocument;
use App\Modules\Core\Infrastructure\Models\ServiceTicket;
use App\Shared\Audit\AuditLogger;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Private file upload validation, official document numbering/versioning,
 * revocation, and authorized download URLs (M4-FND-05).
 */
class ServiceDocumentService
{
    public function __construct(
        private readonly PrivateStorageService $storage,
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Validate and store an attachment onto private disk with SHA-256 checksum.
     *
     * @param  list<string>  $allowedMimes
     */
    public function storePrivateAttachment(
        int $schoolId,
        UploadedFile $file,
        string $documentKind = 'attachment',
        ?ServiceTicket $ticket = null,
        ?User $uploader = null,
        array $allowedMimes = PrivateStorageService::DEFAULT_ALLOWED_MIMES,
        int $maxBytes = 5_242_880,
    ): ServiceDocument {
        $directory = sprintf('schools/%d/services/%s', $schoolId, preg_replace('/[^a-z0-9_-]/i', '_', $documentKind) ?: 'attachment');
        $stored = $this->storage->storeValidatedUpload($file, $directory, $allowedMimes, $maxBytes);

        $document = ServiceDocument::query()->create([
            'school_id' => $schoolId,
            'service_ticket_id' => $ticket?->id,
            'document_kind' => $documentKind,
            'template_version' => 1,
            'storage_path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
            'size_bytes' => $stored['size_bytes'],
            'sha256' => $stored['sha256'],
            'signature_mode' => 'none',
            'status' => 'draft',
            'uploaded_by' => $uploader?->id,
        ]);

        $this->auditLogger->log(
            eventType: 'service_document.uploaded',
            targetType: 'ServiceDocument',
            targetId: (string) $document->id,
            newValues: [
                'document_kind' => $documentKind,
                'mime_type' => $stored['mime_type'],
                'size_bytes' => $stored['size_bytes'],
            ],
            actor: $uploader,
            schoolId: $schoolId,
        );

        return $document;
    }

    /**
     * Issue an official document with atomic sequential numbering and verification token.
     *
     * @param  array{
     *     document_kind: string,
     *     number_prefix: string,
     *     template_key: string,
     *     template_version?: int,
     *     storage_path: string,
     *     original_name: string,
     *     mime_type?: string,
     *     size_bytes: int,
     *     sha256: string,
     *     signatory_name?: ?string,
     *     signatory_title?: ?string,
     *     signature_mode?: string,
     *     service_ticket_id?: ?int
     * }  $payload
     */
    public function issueOfficialDocument(int $schoolId, array $payload, ?User $actor = null): ServiceDocument
    {
        return DB::transaction(function () use ($schoolId, $payload, $actor): ServiceDocument {
            $this->schoolScope->lockSchool($schoolId);

            $year = $this->schoolScope->localNow($schoolId)->format('Y');
            $kind = $payload['document_kind'];
            $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $payload['number_prefix']) ?: 'DOC');
            $sequence = ServiceDocument::query()
                ->where('school_id', $schoolId)
                ->where('document_kind', $kind)
                ->whereNotNull('document_number')
                ->whereYear('issued_at', $year)
                ->count() + 1;

            $documentNumber = sprintf('%s/%d/%s/%04d', $prefix, $schoolId, $year, $sequence);

            $document = ServiceDocument::query()->create([
                'school_id' => $schoolId,
                'service_ticket_id' => $payload['service_ticket_id'] ?? null,
                'document_kind' => $kind,
                'template_key' => $payload['template_key'],
                'template_version' => (int) ($payload['template_version'] ?? 1),
                'document_number' => $documentNumber,
                'verification_token' => Str::random(40),
                'storage_path' => $payload['storage_path'],
                'original_name' => $payload['original_name'],
                'mime_type' => $payload['mime_type'] ?? 'application/pdf',
                'size_bytes' => $payload['size_bytes'],
                'sha256' => $payload['sha256'],
                'signatory_name' => $payload['signatory_name'] ?? null,
                'signatory_title' => $payload['signatory_title'] ?? null,
                'signature_mode' => $payload['signature_mode'] ?? 'none',
                'status' => 'issued',
                'issued_at' => now(),
                'uploaded_by' => $actor?->id,
            ]);

            $this->auditLogger->log(
                eventType: 'service_document.issued',
                targetType: 'ServiceDocument',
                targetId: (string) $document->id,
                newValues: [
                    'document_kind' => $document->document_kind,
                    'document_number' => $document->document_number,
                    'template_version' => $document->template_version,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $document;
        });
    }

    public function revokeDocument(int $documentId, int $schoolId, string $reason, ?User $actor = null): ServiceDocument
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new InvalidArgumentException('Alasan pembatalan dokumen wajib diisi.');
        }

        return DB::transaction(function () use ($documentId, $schoolId, $cleanReason, $actor): ServiceDocument {
            /** @var ServiceDocument $document */
            $document = ServiceDocument::query()
                ->where('school_id', $schoolId)
                ->whereKey($documentId)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $document->status;

            $document->forceFill([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => $cleanReason,
            ])->save();

            $this->auditLogger->log(
                eventType: 'service_document.revoked',
                targetType: 'ServiceDocument',
                targetId: (string) $document->id,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => 'revoked', 'revocation_reason' => $cleanReason],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $document->refresh();
        });
    }

    public function canAccessDocument(?User $user, ServiceDocument $document): bool
    {
        if (! $this->accessScope->belongsToSchool($user, (int) $document->school_id)) {
            return false;
        }

        if ($document->uploaded_by !== null && (int) $document->uploaded_by === (int) $user->id) {
            return true;
        }

        if ($document->document_kind === 'evidence') {
            return $user->hasRole([
                RoleEnum::ADMIN->value,
                RoleEnum::COUNSELOR->value,
                RoleEnum::TASK_FORCE->value,
            ]);
        }

        if ($user->hasRole([
            RoleEnum::ADMIN->value,
            RoleEnum::OPERATOR->value,
            RoleEnum::PRINCIPAL->value,
            RoleEnum::PTSP->value,
            RoleEnum::SIGNATORY->value,
        ])) {
            return true;
        }

        if ($document->service_ticket_id !== null) {
            $ticket = $document->ticket;
            if ($ticket !== null && $ticket->applicant_user_id !== null && (int) $ticket->applicant_user_id === (int) $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a short-lived signed download URL after server-side authorization.
     *
     * @throws AuthorizationException
     */
    public function temporaryDownloadUrl(User $user, ServiceDocument $document, int $ttlMinutes = 10): string
    {
        if (! $this->canAccessDocument($user, $document)) {
            throw new AuthorizationException('Anda tidak berhak mengakses berkas dokumen ini.');
        }

        return $this->storage->generateTemporaryUrl(
            $document->storage_path,
            now()->addMinutes(max(1, $ttlMinutes)),
        );
    }
}
