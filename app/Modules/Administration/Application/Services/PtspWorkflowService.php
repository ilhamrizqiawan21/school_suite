<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\PtspRequest;
use App\Modules\Administration\Infrastructure\Models\PtspService;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\NotificationOutboxService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Shared\Audit\AuditLogger;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PtspWorkflowService
{
    public const CATEGORIES = [
        'persuratan' => 'Persuratan & Keterangan',
        'kesiswaan' => 'Layanan Siswa & Wali',
        'alumni' => 'Alumni & Ijazah',
        'umum' => 'Layanan Umum & Mitra',
    ];

    public function __construct(
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly NotificationOutboxService $outbox,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listCatalogForPublic(int $schoolId): array
    {
        return PtspService::query()
            ->where('school_id', $schoolId)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PtspService $service): array => $this->serializeCatalogService($service))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listCatalogForStaff(int $schoolId): array
    {
        return PtspService::query()
            ->where('school_id', $schoolId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PtspService $service): array => $this->serializeCatalogService($service))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCatalogService(User $actor, array $data): PtspService
    {
        $schoolId = (int) $actor->school_id;

        $service = PtspService::query()->create([
            'school_id' => $schoolId,
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'category' => (string) ($data['category'] ?? 'persuratan'),
            'description' => isset($data['description']) ? trim((string) $data['description']) ?: null : null,
            'requirements' => $this->normalizeRequirements($data['requirements'] ?? []),
            'required_documents' => $this->normalizeRequiredDocuments($data['required_documents'] ?? []),
            'estimated_duration' => trim((string) ($data['estimated_duration'] ?? '2 Hari Kerja')),
            'service_hours' => isset($data['service_hours']) ? trim((string) $data['service_hours']) ?: null : null,
            'delivery_mode' => (string) ($data['delivery_mode'] ?? 'both'),
            'requires_verification_secret' => (bool) ($data['requires_verification_secret'] ?? true),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_published' => (bool) ($data['is_published'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_by' => $actor->id,
        ]);

        $this->audit->log(
            eventType: 'administration.ptsp_service.created',
            targetType: 'PtspService',
            targetId: (string) $service->id,
            newValues: ['code' => $service->code, 'name' => $service->name],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $service;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCatalogService(User $actor, PtspService $service, array $data): PtspService
    {
        abort_unless((int) $service->school_id === (int) $actor->school_id, 404);

        $oldValues = $service->only(['name', 'estimated_duration', 'is_active', 'is_published']);

        $service->forceFill([
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'category' => (string) ($data['category'] ?? $service->category),
            'description' => isset($data['description']) ? trim((string) $data['description']) ?: null : null,
            'requirements' => $this->normalizeRequirements($data['requirements'] ?? []),
            'required_documents' => $this->normalizeRequiredDocuments($data['required_documents'] ?? []),
            'estimated_duration' => trim((string) ($data['estimated_duration'] ?? $service->estimated_duration)),
            'service_hours' => isset($data['service_hours']) ? trim((string) $data['service_hours']) ?: null : null,
            'delivery_mode' => (string) ($data['delivery_mode'] ?? $service->delivery_mode),
            'requires_verification_secret' => (bool) ($data['requires_verification_secret'] ?? $service->requires_verification_secret),
            'is_active' => (bool) ($data['is_active'] ?? $service->is_active),
            'is_published' => (bool) ($data['is_published'] ?? $service->is_published),
            'sort_order' => (int) ($data['sort_order'] ?? $service->sort_order),
        ])->save();

        $this->audit->log(
            eventType: 'administration.ptsp_service.updated',
            targetType: 'PtspService',
            targetId: (string) $service->id,
            oldValues: $oldValues,
            newValues: $service->only(['name', 'estimated_duration', 'is_active', 'is_published']),
            actor: $actor,
            schoolId: (int) $actor->school_id,
        );

        return $service->refresh();
    }

    public function toggleCatalogStatus(User $actor, PtspService $service, ?bool $isActive, ?bool $isPublished): PtspService
    {
        abort_unless((int) $service->school_id === (int) $actor->school_id, 404);

        $oldValues = $service->only(['is_active', 'is_published']);

        $service->forceFill([
            'is_active' => $isActive ?? $service->is_active,
            'is_published' => $isPublished ?? $service->is_published,
        ])->save();

        $this->audit->log(
            eventType: 'administration.ptsp_service.status_toggled',
            targetType: 'PtspService',
            targetId: (string) $service->id,
            oldValues: $oldValues,
            newValues: $service->only(['is_active', 'is_published']),
            actor: $actor,
            schoolId: (int) $actor->school_id,
        );

        return $service->refresh();
    }

    /**
     * Save or update a PTSP draft while freezing the catalog requirements snapshot (M4-TU-01, M4-TU-02).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $uploadedFiles
     */
    public function saveDraft(
        int $schoolId,
        array $data,
        array $uploadedFiles = [],
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?string $sessionDraftToken = null,
    ): PtspRequest {
        return DB::transaction(function () use ($schoolId, $data, $uploadedFiles, $user, $externalApplicantId, $sessionDraftToken): PtspRequest {
            $requestId = isset($data['request_id']) ? (int) $data['request_id'] : 0;

            if ($requestId > 0) {
                /** @var PtspRequest $ptspRequest */
                $ptspRequest = PtspRequest::query()
                    ->where('school_id', $schoolId)
                    ->whereKey($requestId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($ptspRequest->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'status' => 'Hanya draf permohonan yang masih berstatus draf yang dapat diubah.',
                    ]);
                }

                if (! $this->isRequestOwner($ptspRequest, $user, $externalApplicantId, $sessionDraftToken)) {
                    abort(403, 'Anda tidak berhak mengubah draf permohonan ini.');
                }

                $targetServiceId = isset($data['ptsp_service_id']) ? (int) $data['ptsp_service_id'] : (int) $ptspRequest->ptsp_service_id;
                $serviceSnapshot = $ptspRequest->service_snapshot;

                if ($targetServiceId !== (int) $ptspRequest->ptsp_service_id) {
                    $service = $this->findActiveCatalogService($schoolId, $targetServiceId);
                    $serviceSnapshot = $this->snapshotService($service);
                } else {
                    $targetServiceId = (int) $ptspRequest->ptsp_service_id;
                }
            } else {
                $service = $this->findActiveCatalogService($schoolId, (int) $data['ptsp_service_id']);
                $targetServiceId = (int) $service->id;
                $serviceSnapshot = $this->snapshotService($service);

                $ptspRequest = new PtspRequest([
                    'school_id' => $schoolId,
                    'ptsp_service_id' => $targetServiceId,
                    'applicant_user_id' => $user?->id,
                    'external_applicant_id' => $externalApplicantId,
                    'draft_token' => ($user === null && $externalApplicantId === null)
                        ? ($sessionDraftToken ?: Str::random(40))
                        : null,
                    'service_snapshot' => $serviceSnapshot,
                    'status' => 'draft',
                    'attachment_document_ids' => [],
                ]);
            }

            $attachments = is_array($ptspRequest->attachment_document_ids) ? $ptspRequest->attachment_document_ids : [];
            foreach ($uploadedFiles as $reqKey => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'attachment',
                    ticket: null,
                    uploader: $user,
                );

                // Replace existing attachment for the same requirement key inside draft
                $attachments = array_values(array_filter(
                    $attachments,
                    static fn (array $item): bool => (string) ($item['requirement_key'] ?? '') !== (string) $reqKey,
                ));

                $attachments[] = [
                    'requirement_key' => (string) $reqKey,
                    'document_id' => (int) $doc->id,
                    'original_name' => (string) $doc->original_name,
                    'mime_type' => (string) $doc->mime_type,
                    'size_bytes' => (int) $doc->size_bytes,
                ];
            }

            $ptspRequest->fill([
                'ptsp_service_id' => $targetServiceId,
                'service_snapshot' => $serviceSnapshot,
                'applicant_type' => (string) ($data['applicant_type'] ?? $ptspRequest->applicant_type ?? 'general'),
                'applicant_name' => trim((string) $data['applicant_name']),
                'applicant_phone' => isset($data['applicant_phone']) ? trim((string) $data['applicant_phone']) ?: null : null,
                'applicant_email' => isset($data['applicant_email']) ? trim((string) $data['applicant_email']) ?: null : null,
                'applicant_organization' => isset($data['applicant_organization']) ? trim((string) $data['applicant_organization']) ?: null : null,
                'purpose' => trim((string) $data['purpose']),
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) ?: null : null,
                'attachment_document_ids' => $attachments,
            ]);
            $ptspRequest->save();

            return $ptspRequest->refresh();
        });
    }

    public function removeDraftAttachment(
        int $schoolId,
        PtspRequest $ptspRequest,
        int $documentId,
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?string $sessionDraftToken = null,
    ): PtspRequest {
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);
        abort_unless($this->isRequestOwner($ptspRequest, $user, $externalApplicantId, $sessionDraftToken), 403);

        if ($ptspRequest->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Lampiran hanya dapat dihapus saat permohonan masih berstatus draf.',
            ]);
        }

        $attachments = is_array($ptspRequest->attachment_document_ids) ? $ptspRequest->attachment_document_ids : [];
        $remaining = [];
        $found = false;

        foreach ($attachments as $item) {
            if ((int) ($item['document_id'] ?? 0) === $documentId) {
                $found = true;

                continue;
            }
            $remaining[] = $item;
        }

        if ($found) {
            $ptspRequest->forceFill(['attachment_document_ids' => array_values($remaining)])->save();
            $this->documentService->deleteDraftAttachment($schoolId, $documentId);
        }

        return $ptspRequest->refresh();
    }

    /**
     * Submit a draft atomically, validating mandatory attachments and issuing exactly one ticket/receipt (M4-TU-02).
     *
     * @return array{request: PtspRequest, ticket: array<string, mixed>, plain_verification_secret: ?string}
     */
    public function submitRequest(
        int $schoolId,
        int $ptspRequestId,
        ?string $verificationSecret = null,
        ?string $idempotencyKey = null,
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?string $sessionDraftToken = null,
    ): array {
        return DB::transaction(function () use (
            $schoolId,
            $ptspRequestId,
            $verificationSecret,
            $idempotencyKey,
            $user,
            $externalApplicantId,
            $sessionDraftToken,
        ): array {
            /** @var PtspRequest $ptspRequest */
            $ptspRequest = PtspRequest::query()
                ->where('school_id', $schoolId)
                ->whereKey($ptspRequestId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isRequestOwner($ptspRequest, $user, $externalApplicantId, $sessionDraftToken)) {
                abort(403, 'Anda tidak berhak mengajukan draf permohonan ini.');
            }

            if ($ptspRequest->service_ticket_id !== null && $ptspRequest->status !== 'draft') {
                $existingTicket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $ptspRequest->service_ticket_id);

                return [
                    'request' => $ptspRequest,
                    'ticket' => $existingTicket ?? [],
                    'plain_verification_secret' => null,
                ];
            }

            $serviceSnapshot = is_array($ptspRequest->service_snapshot) ? $ptspRequest->service_snapshot : [];
            $attachments = is_array($ptspRequest->attachment_document_ids) ? $ptspRequest->attachment_document_ids : [];
            $this->validateRequiredAttachments($serviceSnapshot, $attachments);

            $resolvedExternalId = $ptspRequest->external_applicant_id;
            if ($resolvedExternalId === null && $ptspRequest->applicant_user_id === null) {
                $registeredExternal = $this->ticketService->registerExternalApplicant($schoolId, [
                    'applicant_type' => $ptspRequest->applicant_type,
                    'full_name' => $ptspRequest->applicant_name,
                    'phone' => $ptspRequest->applicant_phone,
                    'email' => $ptspRequest->applicant_email,
                    'organization_name' => $ptspRequest->applicant_organization,
                    'is_verified' => false,
                ]);
                $resolvedExternalId = (int) $registeredExternal->id;
            }

            $requiresSecret = (bool) ($serviceSnapshot['requires_verification_secret'] ?? true);
            $plainSecret = null;
            if ($requiresSecret) {
                $candidateSecret = trim((string) $verificationSecret);
                $plainSecret = $candidateSecret !== '' ? $candidateSecret : $this->generateVerificationPin();
            }

            $effectiveIdempotency = $idempotencyKey !== null && trim($idempotencyKey) !== ''
                ? trim($idempotencyKey)
                : sprintf('ptsp-req-%d', $ptspRequest->id);

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'ptsp',
                'prefix' => 'PTSP',
                'applicant_user_id' => $ptspRequest->applicant_user_id,
                'external_applicant_id' => $resolvedExternalId,
                'applicant_snapshot' => [
                    'name' => $ptspRequest->applicant_name,
                    'applicant_type' => $ptspRequest->applicant_type,
                    'organization_name' => $ptspRequest->applicant_organization,
                    'service_id' => $ptspRequest->ptsp_service_id,
                    'service_code' => $serviceSnapshot['code'] ?? null,
                    'service_name' => $serviceSnapshot['name'] ?? null,
                    'estimated_duration' => $serviceSnapshot['estimated_duration'] ?? null,
                ],
                'verification_secret' => $plainSecret,
                'internal_status' => 'submitted',
                'public_summary' => 'Permohonan PTSP diterima dan menunggu verifikasi petugas tata usaha.',
                'idempotency_key' => $effectiveIdempotency,
            ], $user);

            $docIds = array_values(array_map(
                static fn (array $item): int => (int) ($item['document_id'] ?? 0),
                $attachments,
            ));
            $this->documentService->attachDocumentsToTicket($schoolId, $docIds, (int) $ticket->id);

            $ptspRequest->forceFill([
                'external_applicant_id' => $resolvedExternalId,
                'service_ticket_id' => (int) $ticket->id,
                'status' => ServiceWorkflowMap::STATUS_SUBMITTED,
                'draft_token' => null,
                'submitted_at' => now(),
            ])->save();

            $this->audit->log(
                eventType: 'administration.ptsp.submitted',
                targetType: 'PtspRequest',
                targetId: (string) $ptspRequest->id,
                newValues: [
                    'ticket_code' => $ticket->ticket_code,
                    'service_code' => $serviceSnapshot['code'] ?? null,
                ],
                actor: $user,
                schoolId: $schoolId,
            );

            $this->enqueueApplicantNotification(
                schoolId: $schoolId,
                ptspRequest: $ptspRequest,
                ticketCode: $ticket->ticket_code,
                eventKey: 'ptsp.submitted',
                templateKey: 'ptsp_submitted',
                status: ServiceWorkflowMap::STATUS_SUBMITTED,
            );

            $snapshot = $this->ticketService->getTicketSnapshotById($schoolId, (int) $ticket->id);

            return [
                'request' => $ptspRequest->refresh(),
                'ticket' => $snapshot ?? [],
                'plain_verification_secret' => $plainSecret,
            ];
        });
    }

    /**
     * Applicant submits revision for a request marked as needs_revision (M4-TU-03).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $uploadedFiles
     */
    public function resubmitRevision(
        int $schoolId,
        PtspRequest $ptspRequest,
        array $data,
        array $uploadedFiles = [],
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?string $verificationSecret = null,
    ): PtspRequest {
        return DB::transaction(function () use (
            $schoolId,
            $ptspRequest,
            $data,
            $uploadedFiles,
            $user,
            $externalApplicantId,
            $verificationSecret,
        ): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()
                ->where('school_id', $schoolId)
                ->whereKey($ptspRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Permohonan belum memiliki tiket.']);
            }

            $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
            if ($ticket === null) {
                abort(404);
            }

            $authorizedOwner = $this->isRequestOwner($locked, $user, $externalApplicantId, null);
            $authorizedSecret = $verificationSecret !== null
                && $this->ticketService->verifyTicketSecret($schoolId, (string) $ticket['ticket_code'], $verificationSecret);

            if (! $authorizedOwner && ! $authorizedSecret) {
                abort(403, 'Verifikasi identitas pemohon diperlukan untuk memperbaiki permohonan.');
            }

            if ($ticket['public_status'] !== ServiceWorkflowMap::STATUS_NEEDS_REVISION) {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan tidak sedang berada pada status perlu perbaikan.',
                ]);
            }

            $attachments = is_array($locked->attachment_document_ids) ? $locked->attachment_document_ids : [];
            $newlyAttachedIds = [];

            foreach ($uploadedFiles as $reqKey => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'attachment',
                    ticket: null,
                    uploader: $user,
                );
                $newlyAttachedIds[] = (int) $doc->id;

                $attachments = array_values(array_filter(
                    $attachments,
                    static fn (array $item): bool => (string) ($item['requirement_key'] ?? '') !== (string) $reqKey,
                ));

                $attachments[] = [
                    'requirement_key' => (string) $reqKey,
                    'document_id' => (int) $doc->id,
                    'original_name' => (string) $doc->original_name,
                    'mime_type' => (string) $doc->mime_type,
                    'size_bytes' => (int) $doc->size_bytes,
                ];
            }

            $this->documentService->attachDocumentsToTicket($schoolId, $newlyAttachedIds, (int) $locked->service_ticket_id);
            $this->validateRequiredAttachments($locked->service_snapshot ?? [], $attachments);

            $locked->forceFill([
                'purpose' => trim((string) ($data['purpose'] ?? $locked->purpose)),
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) ?: null : $locked->notes,
                'attachment_document_ids' => $attachments,
                'status' => ServiceWorkflowMap::STATUS_SUBMITTED,
            ])->save();

            $this->ticketService->transition(
                ticketId: (int) $locked->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: ServiceWorkflowMap::STATUS_SUBMITTED,
                toInternalStatus: 'resubmitted',
                reason: isset($data['revision_reply']) ? trim((string) $data['revision_reply']) ?: null : null,
                actor: $user,
                expectedVersion: (int) $ticket['version'],
                publicSummary: 'Perbaikan berkas pemohon diterima dan menunggu pemeriksaan ulang petugas.',
                metadata: ['event' => 'applicant_resubmitted'],
            );

            $this->audit->log(
                eventType: 'administration.ptsp.resubmitted',
                targetType: 'PtspRequest',
                targetId: (string) $locked->id,
                newValues: ['ticket_code' => $ticket['ticket_code']],
                actor: $user,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * List requests for internal TU/PTSP staff queue with filtering (M4-TU-03).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listQueueForStaff(int $schoolId, array $filters): array
    {
        $serviceId = isset($filters['service_id']) ? (int) $filters['service_id'] : 0;
        $status = trim((string) ($filters['status'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        $matchingTicketIds = [];
        if ($search !== '') {
            $snapshot = $this->ticketService->getTicketSnapshotByCode($schoolId, $search);
            if ($snapshot !== null) {
                $matchingTicketIds[] = (int) $snapshot['id'];
            }
        }

        $query = PtspRequest::query()
            ->where('school_id', $schoolId)
            ->where('status', '!=', 'draft')
            ->when($serviceId > 0, fn ($q) => $q->where('ptsp_service_id', $serviceId))
            ->when(in_array($status, ServiceWorkflowMap::PUBLIC_STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('submitted_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('submitted_at', '<=', $dateTo))
            ->when($search !== '', function ($q) use ($search, $matchingTicketIds): void {
                $q->where(function ($sub) use ($search, $matchingTicketIds): void {
                    $sub->where('applicant_name', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('applicant_organization', 'like', "%{$search}%");
                    if ($matchingTicketIds !== []) {
                        $sub->orWhereIn('service_ticket_id', $matchingTicketIds);
                    }
                });
            })
            ->latest('submitted_at')
            ->latest('id');

        $paginated = $query->paginate(15)->withQueryString();
        $items = collect($paginated->items());

        $ticketIds = $items->pluck('service_ticket_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $ticketsMap = $this->ticketService->getTicketsMapByIds($schoolId, $ticketIds);
        $docsMap = $this->documentService->getDocumentsMapByTicketIds($schoolId, $ticketIds);

        $rows = $items->map(function (PtspRequest $item) use ($ticketsMap, $docsMap): array {
            $ticketId = (int) ($item->service_ticket_id ?? 0);
            $ticket = $ticketsMap[$ticketId] ?? null;
            $docs = $docsMap[$ticketId] ?? [];

            return $this->serializeRequestWithRelations($item, $ticket, $docs);
        })->values()->all();

        $countsBase = PtspRequest::query()->where('school_id', $schoolId)->where('status', '!=', 'draft');

        return [
            'requests' => $rows,
            'pagination' => [
                'current' => $paginated->currentPage(),
                'last' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'previous' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
            'summary' => [
                'submitted' => (clone $countsBase)->where('status', ServiceWorkflowMap::STATUS_SUBMITTED)->count(),
                'in_review' => (clone $countsBase)->where('status', ServiceWorkflowMap::STATUS_IN_REVIEW)->count(),
                'needs_revision' => (clone $countsBase)->where('status', ServiceWorkflowMap::STATUS_NEEDS_REVISION)->count(),
                'approved' => (clone $countsBase)->where('status', ServiceWorkflowMap::STATUS_APPROVED)->count(),
                'completed' => (clone $countsBase)->where('status', ServiceWorkflowMap::STATUS_COMPLETED)->count(),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listApplicantRequests(
        int $schoolId,
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?string $sessionDraftToken = null,
    ): array {
        if ($user === null && $externalApplicantId === null && ($sessionDraftToken === null || $sessionDraftToken === '')) {
            return [];
        }

        $items = PtspRequest::query()
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($user, $externalApplicantId, $sessionDraftToken): void {
                if ($user !== null) {
                    $query->orWhere('applicant_user_id', $user->id);
                }
                if ($externalApplicantId !== null) {
                    $query->orWhere('external_applicant_id', $externalApplicantId);
                }
                if ($sessionDraftToken !== null && $sessionDraftToken !== '') {
                    $query->orWhere('draft_token', $sessionDraftToken);
                }
            })
            ->latest('updated_at')
            ->limit(30)
            ->get();

        $ticketIds = $items->pluck('service_ticket_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $ticketsMap = $this->ticketService->getTicketsMapByIds($schoolId, $ticketIds);
        $docsMap = $this->documentService->getDocumentsMapByTicketIds($schoolId, $ticketIds);

        return $items->map(function (PtspRequest $item) use ($ticketsMap, $docsMap): array {
            $ticketId = (int) ($item->service_ticket_id ?? 0);

            return $this->serializeRequestWithRelations($item, $ticketsMap[$ticketId] ?? null, $docsMap[$ticketId] ?? []);
        })->values()->all();
    }

    public function findByTicketCode(int $schoolId, string $ticketCode): ?array
    {
        $ticket = $this->ticketService->getTicketSnapshotByCode($schoolId, $ticketCode);
        if ($ticket === null || $ticket['service_type'] !== 'ptsp') {
            return null;
        }

        /** @var PtspRequest|null $request */
        $request = PtspRequest::query()
            ->where('school_id', $schoolId)
            ->where('service_ticket_id', (int) $ticket['id'])
            ->first();

        if ($request === null) {
            return null;
        }

        $docs = $this->documentService->listDocumentsForTicket($schoolId, (int) $ticket['id']);

        return [
            'model' => $request,
            'serialized' => $this->serializeRequestWithRelations($request, $ticket, $docs),
            'ticket' => $ticket,
            'documents' => $docs,
        ];
    }

    public function startReview(User $actor, PtspRequest $ptspRequest, ?int $expectedVersion = null): PtspRequest
    {
        abort_unless((int) $ptspRequest->school_id === (int) $actor->school_id, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, (int) $ptspRequest->school_id), 403);

        return $this->applyOfficerTransition(
            actor: $actor,
            ptspRequest: $ptspRequest,
            toStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
            internalStatus: 'in_review',
            reason: null,
            expectedVersion: $expectedVersion,
            publicSummary: 'Permohonan sedang diverifikasi dan diproses oleh petugas TU.',
            eventKey: 'ptsp.in_review',
        );
    }

    public function requestRevision(User $actor, PtspRequest $ptspRequest, string $reason, ?int $expectedVersion = null): PtspRequest
    {
        return $this->applyOfficerTransition(
            actor: $actor,
            ptspRequest: $ptspRequest,
            toStatus: ServiceWorkflowMap::STATUS_NEEDS_REVISION,
            internalStatus: 'needs_revision',
            reason: $reason,
            expectedVersion: $expectedVersion,
            publicSummary: 'Permohonan memerlukan perbaikan berkas atau catatan dari pemohon.',
            eventKey: 'ptsp.needs_revision',
            extraUpdate: ['revision_note' => trim($reason)],
        );
    }

    public function rejectRequest(User $actor, PtspRequest $ptspRequest, string $reason, ?int $expectedVersion = null): PtspRequest
    {
        return $this->applyOfficerTransition(
            actor: $actor,
            ptspRequest: $ptspRequest,
            toStatus: ServiceWorkflowMap::STATUS_REJECTED,
            internalStatus: 'rejected',
            reason: $reason,
            expectedVersion: $expectedVersion,
            publicSummary: 'Permohonan ditolak oleh petugas tata usaha.',
            eventKey: 'ptsp.rejected',
            extraUpdate: ['rejection_reason' => trim($reason)],
        );
    }

    public function assignDisposition(
        User $actor,
        PtspRequest $ptspRequest,
        string $dispositionTo,
        ?string $dispositionNote = null,
        ?int $expectedVersion = null,
    ): PtspRequest {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, $schoolId), 403);

        return DB::transaction(function () use ($actor, $ptspRequest, $schoolId, $dispositionTo, $dispositionNote, $expectedVersion): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()->where('school_id', $schoolId)->whereKey($ptspRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Tiket permohonan tidak ditemukan.']);
            }

            $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
            if ($ticket === null) {
                abort(404);
            }

            if ($ticket['public_status'] === ServiceWorkflowMap::STATUS_SUBMITTED) {
                $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: $schoolId,
                    toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                    toInternalStatus: 'dispositioned',
                    reason: sprintf('Disposisi ke %s: %s', trim($dispositionTo), trim((string) $dispositionNote)),
                    actor: $actor,
                    expectedVersion: $expectedVersion,
                    publicSummary: sprintf('Permohonan diteruskan (disposisi) ke %s.', trim($dispositionTo)),
                    metadata: ['disposition_to' => trim($dispositionTo), 'disposition_note' => $dispositionNote],
                );
                $locked->status = ServiceWorkflowMap::STATUS_IN_REVIEW;
            } elseif ($expectedVersion !== null && (int) $ticket['version'] !== $expectedVersion) {
                throw ValidationException::withMessages(['version' => 'Data permohonan telah diperbarui oleh proses lain.']);
            }

            $locked->disposition_to = trim($dispositionTo);
            $locked->disposition_note = $dispositionNote !== null ? trim($dispositionNote) ?: null : null;
            $locked->save();

            $this->audit->log(
                eventType: 'administration.ptsp.disposition_assigned',
                targetType: 'PtspRequest',
                targetId: (string) $locked->id,
                newValues: ['disposition_to' => $locked->disposition_to],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Issue digital or physical-ready result document for a PTSP request (M4-TU-03).
     *
     * @param  array<string, mixed>  $data
     */
    public function issueResult(
        User $actor,
        PtspRequest $ptspRequest,
        array $data,
        ?UploadedFile $resultFile = null,
        ?int $expectedVersion = null,
    ): PtspRequest {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canApproveFinalDecision($actor, $schoolId) && $this->accessScope->canSignOfficialDocument($actor, $schoolId), 403);

        return DB::transaction(function () use ($actor, $ptspRequest, $schoolId, $data, $resultFile, $expectedVersion): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()->where('school_id', $schoolId)->whereKey($ptspRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Tiket permohonan tidak ditemukan.']);
            }

            $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
            if ($ticket === null) {
                abort(404);
            }

            $deliveryMode = (string) ($data['delivery_mode'] ?? 'digital');
            $markCompleted = (bool) ($data['mark_completed'] ?? ($deliveryMode !== 'physical'));

            if ($locked->result_document_id !== null) {
                $this->documentService->revokeDocument(
                    documentId: (int) $locked->result_document_id,
                    schoolId: $schoolId,
                    reason: 'Digantikan oleh penerbitan ulang dokumen hasil layanan PTSP.',
                    actor: $actor,
                );
            }

            if ($resultFile !== null) {
                $uploaded = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $resultFile,
                    documentKind: 'letter',
                    ticket: null,
                    uploader: $actor,
                );
                $doc = $this->documentService->issueOfficialDocument($schoolId, [
                    'document_kind' => 'letter',
                    'number_prefix' => 'PTSP',
                    'template_key' => 'ptsp_uploaded_result',
                    'storage_path' => $uploaded->storage_path,
                    'original_name' => $uploaded->original_name,
                    'mime_type' => $uploaded->mime_type,
                    'size_bytes' => $uploaded->size_bytes,
                    'sha256' => $uploaded->sha256,
                    'signatory_name' => $data['signatory_name'] ?? $actor->name,
                    'signatory_title' => $data['signatory_title'] ?? 'Petugas Tata Usaha',
                    'signature_mode' => (string) ($data['signature_mode'] ?? 'manual_wet'),
                    'service_ticket_id' => (int) $locked->service_ticket_id,
                ], $actor);
                $uploaded->delete();
            } else {
                $schoolSnapshot = $this->schoolScope->getSchoolLegalSnapshot($schoolId);
                $logo = $this->schoolScope->getSchoolLogoDataUri($schoolId);
                if ($logo !== null) {
                    $schoolSnapshot['logo_data_uri'] = $logo;
                }

                $verificationToken = Str::random(40);
                $verifyUrl = rtrim((string) config('app.url'), '/').'/verifikasi?code='.urlencode($verificationToken);
                $writer = new Writer(new ImageRenderer(new RendererStyle(180, 2), new SvgImageBackEnd));
                $qrVerify = base64_encode($writer->writeString($verifyUrl));

                try {
                    $pdfBytes = Pdf::loadView('administration.ptsp-result', [
                        'school' => $schoolSnapshot,
                        'request' => $locked,
                        'ticket' => $ticket,
                        'result_summary' => trim((string) ($data['result_summary'] ?? 'Permohonan layanan PTSP telah disetujui dan diterbitkan.')),
                        'signatory_name' => trim((string) ($data['signatory_name'] ?? $actor->name)),
                        'signatory_title' => trim((string) ($data['signatory_title'] ?? 'Kepala Tata Usaha / Petugas PTSP')),
                        'issued_date' => $this->schoolScope->localNow($schoolId)->locale('id')->translatedFormat('d F Y'),
                        'verification_token' => $verificationToken,
                        'verify_url' => $verifyUrl,
                        'qr_verify' => $qrVerify,
                    ])->setPaper('a4')->output();
                } catch (\Throwable $exception) {
                    report($exception);
                    throw ValidationException::withMessages(['result_file' => 'PDF hasil layanan gagal dibuat. Tidak ada dokumen atau perubahan status yang disimpan; coba lagi.']);
                }

                $doc = $this->documentService->issueGeneratedDocument($schoolId, $pdfBytes, [
                    'document_kind' => 'letter',
                    'number_prefix' => 'PTSP',
                    'template_key' => 'ptsp_standard_result',
                    'verification_token' => $verificationToken,
                    'original_name' => sprintf('hasil-%s.pdf', strtolower((string) $ticket['ticket_code'])),
                    'signatory_name' => trim((string) ($data['signatory_name'] ?? $actor->name)),
                    'signatory_title' => trim((string) ($data['signatory_title'] ?? 'Kepala Tata Usaha / Petugas PTSP')),
                    'signature_mode' => (string) ($data['signature_mode'] ?? 'none'),
                    'service_ticket_id' => (int) $locked->service_ticket_id,
                ], $actor);
            }

            $targetStatus = $markCompleted ? ServiceWorkflowMap::STATUS_COMPLETED : ServiceWorkflowMap::STATUS_APPROVED;
            $publicSummary = $markCompleted
                ? 'Hasil layanan PTSP telah terbit dan tersedia untuk diunduh.'
                : 'Dokumen telah diterbitkan dan siap diambil di loket PTSP.';

            // If currently submitted, step safely through in_review before completed when needed
            $currentVersion = $expectedVersion;
            if ($ticket['public_status'] === ServiceWorkflowMap::STATUS_SUBMITTED && $targetStatus === ServiceWorkflowMap::STATUS_COMPLETED) {
                $intermediate = $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: $schoolId,
                    toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                    toInternalStatus: 'in_review',
                    reason: null,
                    actor: $actor,
                    expectedVersion: $currentVersion,
                );
                $currentVersion = (int) $intermediate->version;
            } elseif (
                in_array($ticket['public_status'], [ServiceWorkflowMap::STATUS_APPROVED, ServiceWorkflowMap::STATUS_COMPLETED], true)
                && $ticket['public_status'] === $targetStatus
            ) {
                // Re-issuing a document while already in the same public status: skip redundant state transition
                $currentVersion = null;
            }

            if ($ticket['public_status'] !== $targetStatus) {
                $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: $schoolId,
                    toPublicStatus: $targetStatus,
                    toInternalStatus: $markCompleted ? 'issued_completed' : 'ready_for_pickup',
                    reason: null,
                    actor: $actor,
                    expectedVersion: $currentVersion,
                    publicSummary: $publicSummary,
                    metadata: [
                        'document_id' => (int) $doc->id,
                        'document_number' => $doc->document_number,
                        'delivery_mode' => $deliveryMode,
                    ],
                );
            }

            $locked->forceFill([
                'status' => $targetStatus,
                'result_document_id' => (int) $doc->id,
                'result_delivery_mode' => $deliveryMode,
                'completed_at' => $markCompleted ? now() : null,
            ])->save();

            $this->audit->log(
                eventType: 'administration.ptsp.result_issued',
                targetType: 'PtspRequest',
                targetId: (string) $locked->id,
                newValues: [
                    'ticket_code' => $ticket['ticket_code'],
                    'document_number' => $doc->document_number,
                    'status' => $targetStatus,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            $this->enqueueApplicantNotification(
                schoolId: $schoolId,
                ptspRequest: $locked,
                ticketCode: (string) $ticket['ticket_code'],
                eventKey: 'ptsp.result_issued',
                templateKey: 'ptsp_result_issued',
                status: $targetStatus,
            );

            return $locked->refresh();
        });
    }

    /**
     * Record physical pickup / handover at the TU counter (M4-TU-03).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordPhysicalPickup(
        User $actor,
        PtspRequest $ptspRequest,
        array $data,
        ?int $expectedVersion = null,
    ): PtspRequest {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, $schoolId), 403);

        return DB::transaction(function () use ($actor, $ptspRequest, $schoolId, $data, $expectedVersion): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()->where('school_id', $schoolId)->whereKey($ptspRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Tiket permohonan tidak ditemukan.']);
            }

            $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
            if ($ticket === null) {
                abort(404);
            }

            if ($locked->physical_pickup_at !== null) {
                throw ValidationException::withMessages([
                    'physical_pickup_name' => 'Dokumen fisik untuk permohonan ini sudah pernah tercatat diambil.',
                ]);
            }

            $pickupName = trim((string) $data['physical_pickup_name']);
            $pickupNotes = isset($data['physical_pickup_notes']) ? trim((string) $data['physical_pickup_notes']) ?: null : null;

            if ($ticket['public_status'] !== ServiceWorkflowMap::STATUS_COMPLETED) {
                $currentVersion = $expectedVersion;
                if ($ticket['public_status'] === ServiceWorkflowMap::STATUS_SUBMITTED) {
                    $intermediate = $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                        toInternalStatus: 'in_review',
                        reason: null,
                        actor: $actor,
                        expectedVersion: $currentVersion,
                    );
                    $currentVersion = (int) $intermediate->version;
                }

                $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: $schoolId,
                    toPublicStatus: ServiceWorkflowMap::STATUS_COMPLETED,
                    toInternalStatus: 'picked_up',
                    reason: null,
                    actor: $actor,
                    expectedVersion: $currentVersion,
                    publicSummary: sprintf('Dokumen hasil layanan telah diambil oleh %s.', $pickupName),
                    metadata: ['pickup_by' => $pickupName],
                );
            }

            $locked->forceFill([
                'status' => ServiceWorkflowMap::STATUS_COMPLETED,
                'result_delivery_mode' => $locked->result_delivery_mode ?: 'physical',
                'physical_pickup_name' => $pickupName,
                'physical_pickup_at' => now(),
                'physical_pickup_notes' => $pickupNotes,
                'completed_at' => $locked->completed_at ?? now(),
            ])->save();

            $this->audit->log(
                eventType: 'administration.ptsp.physical_pickup_recorded',
                targetType: 'PtspRequest',
                targetId: (string) $locked->id,
                newValues: ['pickup_name' => $pickupName],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Cancel a PTSP request and revoke any issued official result document (AP-10.03, AP-10.09, AP-11.06).
     */
    public function cancelByStaff(
        User $actor,
        PtspRequest $ptspRequest,
        string $reason,
        ?int $expectedVersion = null,
    ): PtspRequest {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);
        $canVerifyQueue = $this->accessScope->canVerifyQueue($actor, $schoolId);
        $canApproveAndSign = $this->accessScope->canApproveFinalDecision($actor, $schoolId)
            && $this->accessScope->canSignOfficialDocument($actor, $schoolId);
        abort_unless($canVerifyQueue || $canApproveAndSign, 403);

        return DB::transaction(function () use ($actor, $ptspRequest, $schoolId, $reason, $expectedVersion, $canApproveAndSign): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()->where('school_id', $schoolId)->whereKey($ptspRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Tiket permohonan tidak ditemukan.']);
            }

            $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
            if ($ticket === null) {
                abort(404);
            }

            if (
                $locked->result_document_id !== null
                || in_array($ticket['public_status'], [ServiceWorkflowMap::STATUS_APPROVED, ServiceWorkflowMap::STATUS_COMPLETED], true)
            ) {
                abort_unless($canApproveAndSign, 403);
            }

            $cleanReason = trim($reason);
            if ($cleanReason === '') {
                throw ValidationException::withMessages(['reason' => 'Alasan pembatalan atau pencabutan wajib diisi.']);
            }

            if ($locked->result_document_id !== null) {
                $this->documentService->revokeDocument(
                    documentId: (int) $locked->result_document_id,
                    schoolId: $schoolId,
                    reason: $cleanReason,
                    actor: $actor,
                );
            }

            $updatedTicket = $this->ticketService->transition(
                ticketId: (int) $locked->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: ServiceWorkflowMap::STATUS_CANCELLED,
                toInternalStatus: 'cancelled_by_staff',
                reason: $cleanReason,
                actor: $actor,
                expectedVersion: $expectedVersion,
                publicSummary: 'Permohonan dibatalkan atau dokumen hasil dicabut oleh pihak sekolah.',
                metadata: ['cancelled_by' => 'staff', 'document_revoked' => $locked->result_document_id !== null],
            );

            $locked->forceFill([
                'status' => ServiceWorkflowMap::STATUS_CANCELLED,
                'rejection_reason' => $cleanReason,
            ])->save();

            $this->audit->log(
                eventType: 'administration.ptsp.cancelled',
                targetType: 'PtspRequest',
                targetId: (string) $locked->id,
                newValues: ['ticket_code' => $updatedTicket->ticket_code, 'reason' => $cleanReason],
                actor: $actor,
                schoolId: $schoolId,
            );

            $this->enqueueApplicantNotification(
                schoolId: $schoolId,
                ptspRequest: $locked,
                ticketCode: $updatedTicket->ticket_code,
                eventKey: 'ptsp.cancelled',
                templateKey: 'ptsp_cancelled',
                status: ServiceWorkflowMap::STATUS_CANCELLED,
            );

            return $locked->refresh();
        });
    }

    /**
     * Allow an authenticated/verified applicant to cancel their own PTSP request while still submitted or needs_revision (AP-11.06).
     */
    public function cancelByApplicant(
        int $schoolId,
        PtspRequest $ptspRequest,
        string $reason,
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?string $verificationSecret = null,
        bool $hasSessionReceiptAccess = false,
    ): PtspRequest {
        return DB::transaction(function () use (
            $schoolId,
            $ptspRequest,
            $reason,
            $user,
            $externalApplicantId,
            $verificationSecret,
            $hasSessionReceiptAccess,
        ): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()->where('school_id', $schoolId)->whereKey($ptspRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Permohonan belum memiliki tiket.']);
            }

            $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
            if ($ticket === null) {
                abort(404);
            }

            $authorizedOwner = $this->isRequestOwner($locked, $user, $externalApplicantId, null);
            $authorizedSecret = $verificationSecret !== null
                && $this->ticketService->verifyTicketSecret($schoolId, (string) $ticket['ticket_code'], $verificationSecret);

            if (! $authorizedOwner && ! $authorizedSecret && ! $hasSessionReceiptAccess) {
                abort(403, 'Verifikasi identitas pemohon diperlukan untuk membatalkan permohonan.');
            }

            if (! in_array($ticket['public_status'], [ServiceWorkflowMap::STATUS_SUBMITTED, ServiceWorkflowMap::STATUS_NEEDS_REVISION], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan yang sudah diproses atau diterbitkan tidak dapat dibatalkan sendiri oleh pemohon.',
                ]);
            }

            $cleanReason = trim($reason) !== '' ? trim($reason) : 'Dibatalkan atas permintaan pemohon.';

            $updatedTicket = $this->ticketService->transition(
                ticketId: (int) $locked->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: ServiceWorkflowMap::STATUS_CANCELLED,
                toInternalStatus: 'cancelled_by_applicant',
                reason: $cleanReason,
                actor: $user,
                expectedVersion: (int) $ticket['version'],
                publicSummary: 'Permohonan dibatalkan oleh pemohon.',
                metadata: ['cancelled_by' => 'applicant'],
            );

            $locked->forceFill([
                'status' => ServiceWorkflowMap::STATUS_CANCELLED,
                'rejection_reason' => $cleanReason,
            ])->save();

            $this->audit->log(
                eventType: 'administration.ptsp.cancelled_by_applicant',
                targetType: 'PtspRequest',
                targetId: (string) $locked->id,
                newValues: ['ticket_code' => $updatedTicket->ticket_code, 'reason' => $cleanReason],
                actor: $user,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    public function renderReceiptPdfBytes(int $schoolId, array $serializedRequest): string
    {
        $schoolSnapshot = $this->schoolScope->getSchoolLegalSnapshot($schoolId);
        $logo = $this->schoolScope->getSchoolLogoDataUri($schoolId);
        if ($logo !== null) {
            $schoolSnapshot['logo_data_uri'] = $logo;
        }

        $ticketCode = (string) ($serializedRequest['ticket_code'] ?? '');
        $trackUrl = rtrim((string) config('app.url'), '/').'/lacak?code='.urlencode($ticketCode).'&category=ptsp';
        $writer = new Writer(new ImageRenderer(new RendererStyle(180, 2), new SvgImageBackEnd));
        $qr = base64_encode($writer->writeString($trackUrl));

        $pdf = Pdf::loadView('administration.ptsp-receipt', [
            'school' => $schoolSnapshot,
            'request' => $serializedRequest,
            'qr' => $qr,
            'track_url' => $trackUrl,
        ])->setPaper('a4');

        return $pdf->output();
    }

    public function isRequestOwner(
        PtspRequest $ptspRequest,
        ?User $user,
        ?int $externalApplicantId,
        ?string $sessionDraftToken,
    ): bool {
        if ($user !== null && $ptspRequest->applicant_user_id !== null && (int) $ptspRequest->applicant_user_id === (int) $user->id) {
            return true;
        }

        if ($externalApplicantId !== null && $ptspRequest->external_applicant_id !== null && (int) $ptspRequest->external_applicant_id === $externalApplicantId) {
            return true;
        }

        if ($sessionDraftToken !== null && $sessionDraftToken !== '' && $ptspRequest->draft_token !== null && hash_equals($ptspRequest->draft_token, $sessionDraftToken)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $extraUpdate
     */
    private function applyOfficerTransition(
        User $actor,
        PtspRequest $ptspRequest,
        string $toStatus,
        string $internalStatus,
        ?string $reason,
        ?int $expectedVersion,
        string $publicSummary,
        string $eventKey,
        array $extraUpdate = [],
    ): PtspRequest {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, $schoolId), 403);

        return DB::transaction(function () use (
            $actor,
            $ptspRequest,
            $schoolId,
            $toStatus,
            $internalStatus,
            $reason,
            $expectedVersion,
            $publicSummary,
            $eventKey,
            $extraUpdate,
        ): PtspRequest {
            /** @var PtspRequest $locked */
            $locked = PtspRequest::query()->where('school_id', $schoolId)->whereKey($ptspRequest->id)->lockForUpdate()->firstOrFail();
            if ($locked->service_ticket_id === null) {
                throw ValidationException::withMessages(['ticket' => 'Tiket permohonan tidak ditemukan.']);
            }

            $ticket = $this->ticketService->transition(
                ticketId: (int) $locked->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: $toStatus,
                toInternalStatus: $internalStatus,
                reason: $reason,
                actor: $actor,
                expectedVersion: $expectedVersion,
                publicSummary: $publicSummary,
            );

            $locked->forceFill([
                ...$extraUpdate,
                'status' => $toStatus,
            ])->save();

            $this->enqueueApplicantNotification(
                schoolId: $schoolId,
                ptspRequest: $locked,
                ticketCode: $ticket->ticket_code,
                eventKey: $eventKey,
                templateKey: str_replace('.', '_', $eventKey),
                status: $toStatus,
            );

            return $locked->refresh();
        });
    }

    private function findActiveCatalogService(int $schoolId, int $serviceId): PtspService
    {
        /** @var PtspService|null $service */
        $service = PtspService::query()
            ->where('school_id', $schoolId)
            ->whereKey($serviceId)
            ->first();

        if ($service === null || ! $service->is_published || ! $service->is_active) {
            throw ValidationException::withMessages([
                'ptsp_service_id' => 'Layanan PTSP yang dipilih sedang tidak aktif atau ditutup.',
            ]);
        }

        return $service;
    }

    /**
     * @param  array<string, mixed>  $serviceSnapshot
     * @param  list<array<string, mixed>>  $attachments
     */
    private function validateRequiredAttachments(array $serviceSnapshot, array $attachments): void
    {
        $requiredDocs = is_array($serviceSnapshot['required_documents'] ?? null)
            ? $serviceSnapshot['required_documents']
            : [];

        $uploadedKeys = array_values(array_map(
            static fn (array $item): string => (string) ($item['requirement_key'] ?? ''),
            $attachments,
        ));

        $errors = [];
        foreach ($requiredDocs as $docRule) {
            if (! is_array($docRule) || ! ($docRule['required'] ?? false)) {
                continue;
            }
            $key = (string) ($docRule['key'] ?? '');
            $label = (string) ($docRule['label'] ?? $key);

            if ($key !== '' && ! in_array($key, $uploadedKeys, true)) {
                $errors["attachments.{$key}"] = sprintf('Berkas wajib "%s" belum diunggah.', $label);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotService(PtspService $service): array
    {
        return [
            'id' => (int) $service->id,
            'code' => (string) $service->code,
            'name' => (string) $service->name,
            'category' => (string) $service->category,
            'estimated_duration' => (string) $service->estimated_duration,
            'service_hours' => $service->service_hours,
            'delivery_mode' => (string) $service->delivery_mode,
            'requires_verification_secret' => (bool) $service->requires_verification_secret,
            'requirements' => $service->requirements ?? [],
            'required_documents' => $service->required_documents ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeCatalogService(PtspService $service): array
    {
        return [
            'id' => (int) $service->id,
            'code' => (string) $service->code,
            'name' => (string) $service->name,
            'category' => (string) $service->category,
            'description' => $service->description,
            'requirements' => $service->requirements ?? [],
            'required_documents' => $service->required_documents ?? [],
            'estimated_duration' => (string) $service->estimated_duration,
            'service_hours' => $service->service_hours,
            'delivery_mode' => (string) $service->delivery_mode,
            'requires_verification_secret' => (bool) $service->requires_verification_secret,
            'is_active' => (bool) $service->is_active,
            'is_published' => (bool) $service->is_published,
            'sort_order' => (int) $service->sort_order,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $ticket
     * @param  list<array<string, mixed>>  $documents
     * @return array<string, mixed>
     */
    public function serializeRequestWithRelations(PtspRequest $request, ?array $ticket, array $documents): array
    {
        return [
            'id' => (int) $request->id,
            'ptsp_service_id' => (int) $request->ptsp_service_id,
            'service_ticket_id' => $request->service_ticket_id ? (int) $request->service_ticket_id : null,
            'ticket_code' => $ticket['ticket_code'] ?? null,
            'ticket_version' => $ticket['version'] ?? null,
            'service_snapshot' => $request->service_snapshot ?? [],
            'applicant_type' => (string) $request->applicant_type,
            'applicant_name' => (string) $request->applicant_name,
            'applicant_phone' => $request->applicant_phone,
            'applicant_email' => $request->applicant_email,
            'applicant_organization' => $request->applicant_organization,
            'purpose' => (string) $request->purpose,
            'notes' => $request->notes,
            'attachment_document_ids' => $request->attachment_document_ids ?? [],
            'status' => $ticket['public_status'] ?? (string) $request->status,
            'public_summary' => $ticket['public_summary'] ?? null,
            'revision_note' => $request->revision_note,
            'rejection_reason' => $request->rejection_reason,
            'disposition_to' => $request->disposition_to,
            'disposition_note' => $request->disposition_note,
            'result_document_id' => $request->result_document_id ? (int) $request->result_document_id : null,
            'result_delivery_mode' => $request->result_delivery_mode,
            'physical_pickup_name' => $request->physical_pickup_name,
            'physical_pickup_at' => $request->physical_pickup_at?->toIso8601String(),
            'physical_pickup_notes' => $request->physical_pickup_notes,
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'completed_at' => $request->completed_at?->toIso8601String(),
            'updated_at' => $request->updated_at?->toIso8601String(),
            'documents' => $documents,
            'transitions' => $ticket['transitions'] ?? [],
        ];
    }

    private function enqueueApplicantNotification(
        int $schoolId,
        PtspRequest $ptspRequest,
        string $ticketCode,
        string $eventKey,
        string $templateKey,
        string $status,
    ): void {
        if ($ptspRequest->applicant_user_id === null && $ptspRequest->external_applicant_id === null) {
            return;
        }

        $recipientType = $ptspRequest->applicant_user_id !== null ? 'user' : 'external_applicant';
        $recipientId = $ptspRequest->applicant_user_id ?? $ptspRequest->external_applicant_id;

        $this->outbox->enqueue(
            schoolId: $schoolId,
            eventKey: $eventKey,
            channel: 'in_app',
            recipientType: $recipientType,
            recipientId: (int) $recipientId,
            templateKey: $templateKey,
            payload: [
                'ticket_code' => $ticketCode,
                'status' => $status,
                'service_name' => $ptspRequest->service_snapshot['name'] ?? null,
            ],
            idempotencyKey: sprintf('%s-%s-%s', $eventKey, $ticketCode, $status),
            dispatchJob: false,
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeRequirements(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\R+/u', $raw) ?: [];
        }

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $raw,
        ), static fn (string $line): bool => $line !== ''));
    }

    /**
     * @return list<array{key: string, label: string, required: bool}>
     */
    private function normalizeRequiredDocuments(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $rawKey = trim((string) ($item['key'] ?? ''));
            $key = $rawKey !== '' ? Str::slug($rawKey, '_') : 'dokumen_'.($index + 1);
            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'required' => (bool) ($item['required'] ?? true),
            ];
        }

        return array_values($normalized);
    }

    private function generateVerificationPin(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
