<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
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
use Throwable;

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
        private readonly AcademicQueryService $academicQuery,
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
                'verification_token' => $payload['verification_token'] ?? Str::random(40),
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

    /**
     * Link previously uploaded draft attachments to a newly issued ServiceTicket.
     *
     * @param  list<int>  $documentIds
     */
    public function attachDocumentsToTicket(int $schoolId, array $documentIds, int $ticketId): void
    {
        $ids = array_values(array_unique(array_filter($documentIds, static fn (mixed $id): bool => (int) $id > 0)));
        if ($ids === []) {
            return;
        }

        ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->whereNull('service_ticket_id')
            ->update(['service_ticket_id' => $ticketId]);
    }

    /**
     * Store generated bytes (such as a rendered PDF) directly onto private storage and issue it.
     *
     * @param  array{
     *     document_kind: string,
     *     number_prefix: string,
     *     template_key: string,
     *     template_version?: int,
     *     original_name: string,
     *     mime_type?: string,
     *     signatory_name?: ?string,
     *     signatory_title?: ?string,
     *     signature_mode?: string,
     *     service_ticket_id?: ?int
     * }  $payload
     */
    public function issueGeneratedDocument(int $schoolId, string $bytes, array $payload, ?User $actor = null): ServiceDocument
    {
        if ($bytes === '') {
            throw new InvalidArgumentException('Isi dokumen hasil tidak boleh kosong.');
        }

        $safeKind = preg_replace('/[^a-z0-9_-]/i', '_', $payload['document_kind']) ?: 'document';
        $storagePath = sprintf('schools/%d/services/%s/%s.pdf', $schoolId, $safeKind, Str::uuid()->toString());

        try {
            if (! $this->storage->put($storagePath, $bytes)) {
                throw new InvalidArgumentException('Dokumen hasil gagal disimpan. Silakan coba lagi.');
            }

            return $this->issueOfficialDocument($schoolId, [
                ...$payload,
                'storage_path' => $storagePath,
                'size_bytes' => strlen($bytes),
                'sha256' => hash('sha256', $bytes),
            ], $actor);
        } catch (Throwable $exception) {
            // A failed DB/audit/numbering step must not leave an inaccessible private PDF behind.
            $this->storage->delete($storagePath);

            throw $exception;
        }
    }

    /**
     * List safe document metadata for a ticket.
     *
     * @return list<array<string, mixed>>
     */
    public function listDocumentsForTicket(int $schoolId, int $ticketId): array
    {
        return ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->where('service_ticket_id', $ticketId)
            ->orderBy('id')
            ->get()
            ->map(fn (ServiceDocument $doc): array => $this->serializeDocument($doc))
            ->values()
            ->all();
    }

    /**
     * Batch-fetch document metadata keyed by service_ticket_id.
     *
     * @param  list<int>  $ticketIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function getDocumentsMapByTicketIds(int $schoolId, array $ticketIds): array
    {
        $ids = array_values(array_unique(array_filter($ticketIds, static fn (mixed $id): bool => (int) $id > 0)));
        if ($ids === []) {
            return [];
        }

        $grouped = [];
        $docs = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereIn('service_ticket_id', $ids)
            ->orderBy('id')
            ->get();

        foreach ($docs as $doc) {
            $tid = (int) $doc->service_ticket_id;
            $grouped[$tid][] = $this->serializeDocument($doc);
        }

        return $grouped;
    }

    /**
     * Resolve downloadable private file details for an authorized staff member.
     *
     * @return array{absolute_path: string, original_name: string, mime_type: string}|null
     *
     * @throws AuthorizationException
     */
    public function resolveDownloadForStaff(User $actor, int $schoolId, int $documentId): ?array
    {
        /** @var ServiceDocument|null $document */
        $document = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereKey($documentId)
            ->first();

        if ($document === null) {
            return null;
        }

        if (! $this->canAccessDocument($actor, $document)) {
            throw new AuthorizationException('Anda tidak berhak mengakses berkas dokumen ini.');
        }

        if (! $this->storage->exists($document->storage_path)) {
            return null;
        }

        return [
            'absolute_path' => $this->storage->path($document->storage_path),
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Resolve downloadable private file details after caller verified ticket ownership/secret.
     *
     * @return array{absolute_path: string, original_name: string, mime_type: string}|null
     */
    public function resolveDownloadForVerifiedTicket(int $schoolId, int $ticketId, int $documentId): ?array
    {
        /** @var ServiceDocument|null $document */
        $document = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->where('service_ticket_id', $ticketId)
            ->whereKey($documentId)
            ->first();

        if ($document === null || ! $this->storage->exists($document->storage_path)) {
            return null;
        }

        return [
            'absolute_path' => $this->storage->path($document->storage_path),
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Delete an unsubmitted draft attachment safely.
     */
    public function deleteDraftAttachment(int $schoolId, int $documentId): void
    {
        /** @var ServiceDocument|null $document */
        $document = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereKey($documentId)
            ->whereNull('service_ticket_id')
            ->where('status', 'draft')
            ->first();

        if ($document === null) {
            return;
        }

        $path = $document->storage_path;
        $document->delete();

        if ($this->storage->exists($path)) {
            $this->storage->delete($path);
        }
    }

    /**
     * Resolve a file download strictly inside a school by document ID after domain-level authorization.
     *
     * @return array{absolute_path: string, original_name: string, mime_type: string}|null
     */
    public function resolveDownloadByDocumentId(int $schoolId, int $documentId): ?array
    {
        /** @var ServiceDocument|null $document */
        $document = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereKey($documentId)
            ->first();

        if ($document === null || ! $this->storage->exists($document->storage_path)) {
            return null;
        }

        return [
            'absolute_path' => $this->storage->path($document->storage_path),
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Return only document IDs whose private storage object still exists.
     *
     * @param  list<int>  $documentIds
     * @return list<int>
     */
    public function storedDocumentIds(int $schoolId, array $documentIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        return ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->get(['id', 'storage_path'])
            ->filter(fn (ServiceDocument $document): bool => $this->storage->exists($document->storage_path))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $documentIds
     * @return array<int, string>
     */
    public function getVerificationTokensMapByIds(int $schoolId, array $documentIds): array
    {
        $ids = array_values(array_unique(array_filter($documentIds, static fn (mixed $id): bool => (int) $id > 0)));
        if ($ids === []) {
            return [];
        }

        return ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->pluck('verification_token', 'id')
            ->map(static fn ($token): string => (string) $token)
            ->all();
    }

    public function getVerificationTokenById(int $schoolId, int $documentId): ?string
    {
        $token = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereKey($documentId)
            ->value('verification_token');

        return $token !== null ? (string) $token : null;
    }

    /**
     * @param  list<string>  $allowedKinds
     * @return array{status: 'ok'|'not_found'|'forbidden', file?: array{absolute_path: string, original_name: string, mime_type: string}}
     */
    public function authorizeAndResolveGeneratedDownload(
        int $schoolId,
        int $documentId,
        array $allowedKinds,
        ?User $user,
        bool $hasSessionAccess,
        bool $isStaff,
    ): array {
        /** @var ServiceDocument|null $document */
        $document = ServiceDocument::query()
            ->where('school_id', $schoolId)
            ->whereKey($documentId)
            ->first();

        if ($document === null || ! in_array($document->document_kind, $allowedKinds, true)) {
            return ['status' => 'not_found'];
        }

        $isOwner = $user !== null && $this->canAccessDocument($user, $document);
        if (! $isStaff && ! $hasSessionAccess && ! $isOwner) {
            return ['status' => 'forbidden'];
        }

        if (! $this->storage->exists($document->storage_path)) {
            return ['status' => 'not_found'];
        }

        return [
            'status' => 'ok',
            'file' => [
                'absolute_path' => $this->storage->path($document->storage_path),
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
            ],
        ];
    }

    /**
     * Verify a service document publicly by verification_token or document_number without exposing sensitive PII (M4-LIF-04).
     *
     * @return array<string, mixed>
     */
    public function verifyPublicDocument(int $schoolId, string $code): array
    {
        $clean = trim($code);
        if ($clean === '') {
            return [
                'found' => false,
                'status' => 'not_found',
                'message' => 'Kode verifikasi atau nomor dokumen wajib diisi.',
            ];
        }

        /** @var ServiceDocument|null $document */
        $document = ServiceDocument::query()
            ->with('ticket')
            ->where('school_id', $schoolId)
            ->where(fn ($q) => $q->where('verification_token', $clean)->orWhere('document_number', $clean))
            ->first();

        $ticket = $document?->ticket;
        $snapshot = is_array($ticket?->applicant_snapshot) ? $ticket->applicant_snapshot : [];
        if ($document === null || $document->document_kind === 'evidence' || $document->status === 'draft'
            || ($document->document_kind === 'skl' && ! $this->academicQuery->isGraduationDocumentReleased(
                $schoolId,
                (int) $document->id,
                $document->status === 'revoked' && $ticket?->subject_student_id !== null ? (int) $ticket->subject_student_id : null,
                $snapshot,
            ))) {
            return [
                'found' => false,
                'status' => 'not_found',
                'message' => 'Dokumen tidak ditemukan atau tidak tersedia untuk verifikasi publik.',
            ];
        }

        $holderName = (string) ($snapshot['student_name'] ?? $snapshot['applicant_name'] ?? $snapshot['full_name'] ?? $snapshot['contact_name'] ?? 'Pemegang Terverifikasi');

        if ($document->status === 'revoked' || $ticket?->revoked_at !== null) {
            return [
                'found' => true,
                'status' => 'revoked',
                'message' => 'Dokumen ini telah dicabut dan tidak lagi berlaku.',
                'document' => [
                    'document_kind' => (string) $document->document_kind,
                    'document_kind_label' => $this->kindLabel((string) $document->document_kind),
                    'document_number' => $document->document_number,
                    'holder_name' => $this->maskName($holderName),
                    'revoked_at' => ($document->revoked_at ?? $ticket?->revoked_at)?->toDateString(),
                    'revocation_reason' => $document->revocation_reason ?: 'Dicabut oleh sekolah',
                ],
            ];
        }

        if ($ticket !== null && $ticket->expires_at !== null && $ticket->expires_at->isPast()) {
            return [
                'found' => true,
                'status' => 'expired',
                'message' => 'Masa berlaku verifikasi dokumen telah berakhir.',
                'document' => [
                    'document_kind' => (string) $document->document_kind,
                    'document_kind_label' => $this->kindLabel((string) $document->document_kind),
                    'document_number' => $document->document_number,
                    'holder_name' => $this->maskName($holderName),
                    'issued_at' => $document->issued_at?->toDateString(),
                    'expires_at' => $ticket->expires_at->toDateString(),
                ],
            ];
        }

        $this->auditLogger->log(
            eventType: 'service_document.public_verified',
            targetType: 'ServiceDocument',
            targetId: (string) $document->id,
            newValues: ['document_kind' => $document->document_kind, 'status' => $document->status],
            schoolId: $schoolId,
        );

        return [
            'found' => true,
            'status' => 'valid',
            'message' => 'Dokumen resmi terdaftar dan dinyatakan sah oleh sekolah.',
            'document' => [
                'document_kind' => (string) $document->document_kind,
                'document_kind_label' => $this->kindLabel((string) $document->document_kind),
                'document_number' => $document->document_number,
                'holder_name' => $holderName,
                'issued_at' => $document->issued_at?->toDateString(),
                'signatory_name' => $document->signatory_name,
                'signatory_title' => $document->signatory_title,
                'template_version' => (int) $document->template_version,
                'sha256_prefix' => substr((string) $document->sha256, 0, 12),
            ],
        ];
    }

    private function kindLabel(string $kind): string
    {
        return match ($kind) {
            'skl' => 'Surat Keterangan Lulus (SKL)',
            'legalization' => 'Pengesahan / Legalisir',
            'power_of_attorney' => 'Surat Kuasa Pengambilan',
            'sptjm' => 'SPTJM Keabsahan',
            'letter' => 'Surat Keterangan / Mutasi',
            'ekta' => 'Kartu Alumni Elektronik (E-KTA)',
            'clearance' => 'Surat Keterangan Bebas Pustaka',
            'ebook' => 'Koleksi Digital Perpustakaan',
            'proposal_lpj' => 'Lampiran Proposal / Bukti LPJ',
            'receipt' => 'Tanda Terima Layanan',
            default => 'Dokumen Resmi',
        };
    }

    private function maskName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $masked = array_map(static function (string $part): string {
            if (mb_strlen($part) <= 2) {
                return $part;
            }

            return mb_substr($part, 0, 2).str_repeat('*', max(2, mb_strlen($part) - 2));
        }, $parts);

        return implode(' ', $masked);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocument(ServiceDocument $doc): array
    {
        return [
            'id' => (int) $doc->id,
            'document_kind' => (string) $doc->document_kind,
            'document_number' => $doc->document_number,
            'original_name' => (string) $doc->original_name,
            'mime_type' => (string) $doc->mime_type,
            'size_bytes' => (int) $doc->size_bytes,
            'status' => (string) $doc->status,
            'signatory_name' => $doc->signatory_name,
            'signatory_title' => $doc->signatory_title,
            'verification_token' => $doc->verification_token,
            'issued_at' => $doc->issued_at?->toIso8601String(),
            'revoked_at' => $doc->revoked_at?->toIso8601String(),
            'revocation_reason' => $doc->revocation_reason,
        ];
    }
}
