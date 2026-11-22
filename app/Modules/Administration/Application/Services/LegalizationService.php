<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\LegalizationRequest;
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

/**
 * Handles alumni legalization requests (`M4-LIF-05`) and versioned
 * Power of Attorney & SPTJM PDF generation linked cleanly to legalization (`M4-LIF-06`).
 */
class LegalizationService
{
    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly NotificationOutboxService $outbox,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $attachments
     * @return array{request: LegalizationRequest, ticket: array<string, mixed>, plain_verification_secret: string}
     */
    public function submit(
        int $schoolId,
        array $data,
        array $attachments = [],
        ?User $actor = null,
        ?int $externalApplicantId = null,
    ): array {
        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            $existing = LegalizationRequest::query()
                ->where('school_id', $schoolId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing !== null) {
                $ticket = $existing->service_ticket_id
                    ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                    : [];

                return [
                    'request' => $existing,
                    'ticket' => $ticket ?? [],
                    'plain_verification_secret' => '',
                ];
            }
        }

        $delivery = (string) ($data['delivery_method'] ?? 'pickup_direct');
        if ($delivery === 'pickup_proxy' && trim((string) ($data['proxy_name'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'proxy_name' => 'Nama penerima kuasa wajib diisi untuk pengambilan melalui kuasa.',
            ]);
        }

        return DB::transaction(function () use ($schoolId, $data, $attachments, $actor, $externalApplicantId, $idempotencyKey, $delivery): array {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                $existing = LegalizationRequest::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing !== null) {
                    $ticket = $existing->service_ticket_id
                        ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                        : [];

                    return [
                        'request' => $existing,
                        'ticket' => $ticket ?? [],
                        'plain_verification_secret' => '',
                    ];
                }
            }

            $code = 'LEG-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(6));
            $plainSecret = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'legalisir',
                'prefix' => 'LEG',
                'applicant_user_id' => $actor?->id,
                'external_applicant_id' => $externalApplicantId,
                'applicant_snapshot' => [
                    'legalization_code' => $code,
                    'applicant_name' => trim((string) $data['applicant_name']),
                    'document_type' => (string) $data['document_type'],
                    'certificate_number' => trim((string) $data['certificate_number']),
                ],
                'verification_secret' => $plainSecret,
                'internal_status' => 'submitted',
                'public_summary' => sprintf('Permohonan legalisir [%s] diterima dan menunggu verifikasi TU.', $code),
                'idempotency_key' => $idempotencyKey,
            ], $actor);

            $attachmentIds = [];
            foreach ($attachments as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'attachment',
                    ticket: $ticket,
                    uploader: $actor,
                );
                $attachmentIds[] = (int) $doc->id;
            }

            $req = LegalizationRequest::query()->create([
                'school_id' => $schoolId,
                'service_ticket_id' => (int) $ticket->id,
                'external_applicant_id' => $externalApplicantId,
                'applicant_user_id' => $actor?->id,
                'legalization_code' => $code,
                'applicant_name' => trim((string) $data['applicant_name']),
                'applicant_nis' => isset($data['applicant_nis']) ? trim((string) $data['applicant_nis']) ?: null : null,
                'applicant_nisn' => isset($data['applicant_nisn']) ? trim((string) $data['applicant_nisn']) ?: null : null,
                'graduation_year' => (int) $data['graduation_year'],
                'phone' => trim((string) $data['phone']),
                'email' => isset($data['email']) ? trim((string) $data['email']) ?: null : null,
                'document_type' => (string) $data['document_type'],
                'certificate_number' => trim((string) $data['certificate_number']),
                'copy_count' => (int) $data['copy_count'],
                'purpose' => trim((string) $data['purpose']),
                'delivery_method' => $delivery,
                'proxy_name' => $delivery === 'pickup_proxy' ? trim((string) ($data['proxy_name'] ?? '')) : null,
                'proxy_nik_last4' => $delivery === 'pickup_proxy' && ! empty($data['proxy_nik']) ? substr(preg_replace('/\D+/', '', (string) $data['proxy_nik']) ?: '', -4) : null,
                'proxy_relation' => $delivery === 'pickup_proxy' ? trim((string) ($data['proxy_relation'] ?? '')) ?: null : null,
                'proxy_phone' => $delivery === 'pickup_proxy' ? trim((string) ($data['proxy_phone'] ?? '')) ?: null : null,
                'signature_data' => trim((string) ($data['signature_data'] ?? '')),
                'attachment_document_ids' => $attachmentIds,
                'power_of_attorney_document_id' => isset($data['power_of_attorney_document_id']) ? (int) $data['power_of_attorney_document_id'] : null,
                'sptjm_document_id' => isset($data['sptjm_document_id']) ? (int) $data['sptjm_document_id'] : null,
                'status' => 'submitted',
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->log(
                eventType: 'administration.legalization.submitted',
                targetType: 'LegalizationRequest',
                targetId: (string) $req->id,
                newValues: ['legalization_code' => $code, 'document_type' => $req->document_type, 'copy_count' => $req->copy_count],
                actor: $actor,
                schoolId: $schoolId,
            );

            return [
                'request' => $req->refresh(),
                'ticket' => $this->ticketService->getTicketSnapshotById($schoolId, (int) $ticket->id) ?? [],
                'plain_verification_secret' => $plainSecret,
            ];
        });
    }

    /**
     * Generate versioned official Surat Kuasa or SPTJM PDF and attach optionally to an existing LegalizationRequest (M4-LIF-06).
     *
     * @param  array<string, mixed>  $data
     * @return array{document_id: int, document_number: string, verification_token: string, template_type: string}
     */
    public function generateAuthorizationDocument(
        int $schoolId,
        string $templateType,
        array $data,
        ?User $actor = null,
    ): array {
        if (! in_array($templateType, ['power_of_attorney', 'sptjm'], true)) {
            throw ValidationException::withMessages(['template_type' => 'Jenis dokumen tidak valid.']);
        }

        return DB::transaction(function () use ($schoolId, $templateType, $data, $actor): array {
            $school = $this->schoolScope->getSchoolDetails($schoolId);

            $linkedReq = null;
            if (! empty($data['legalization_code'])) {
                $linkedReq = LegalizationRequest::query()
                    ->where('school_id', $schoolId)
                    ->where('legalization_code', strtoupper(trim((string) $data['legalization_code'])))
                    ->first();
                if ($linkedReq === null) {
                    throw ValidationException::withMessages(['legalization_code' => 'Kode register legalisir tidak ditemukan.']);
                }
            }

            $grantorNikDigits = preg_replace('/\D+/', '', (string) ($data['grantor_nik'] ?? '')) ?: '';
            $proxyNikDigits = preg_replace('/\D+/', '', (string) ($data['proxy_nik'] ?? '')) ?: '';
            $payload = [
                'grantor_name' => trim((string) $data['grantor_name']),
                'grantor_nisn' => isset($data['grantor_nisn']) ? trim((string) $data['grantor_nisn']) : '',
                'grantor_nik_last4' => $grantorNikDigits !== '' ? substr($grantorNikDigits, -4) : '----',
                'grantor_graduation_year' => $data['grantor_graduation_year'] ?? null,
                'grantor_phone' => trim((string) ($data['grantor_phone'] ?? '')),
                'proxy_name' => trim((string) ($data['proxy_name'] ?? '')),
                'proxy_nik_last4' => $proxyNikDigits !== '' ? substr($proxyNikDigits, -4) : '----',
                'proxy_relation' => trim((string) ($data['proxy_relation'] ?? '')),
                'proxy_phone' => trim((string) ($data['proxy_phone'] ?? '')),
                'document_ref_number' => trim((string) ($data['document_ref_number'] ?? '')),
                'purpose' => trim((string) $data['purpose']),
                'legalization_code' => $linkedReq?->legalization_code,
                'grantor_signature_data' => (string) ($data['grantor_signature_data'] ?? ''),
                'proxy_signature_data' => (string) ($data['proxy_signature_data'] ?? ''),
            ];

            $verificationToken = Str::random(40);
            $docNumberHint = ($templateType === 'power_of_attorney' ? 'SK' : 'SPTJM').'-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(5));

            $view = $templateType === 'power_of_attorney'
                ? 'administration.power-of-attorney'
                : 'administration.sptjm';

            $qrDataUri = $this->buildQrDataUri(url('/verifikasi?code='.urlencode($docNumberHint)));

            $bytes = Pdf::loadView($view, [
                'school' => $school,
                'payload' => $payload,
                'documentNumber' => $docNumberHint,
                'verificationToken' => $verificationToken,
                'qrDataUri' => $qrDataUri,
            ])->setPaper('a4')->output();

            $doc = $this->documentService->issueGeneratedDocument(
                schoolId: $schoolId,
                bytes: $bytes,
                payload: [
                    'document_kind' => $templateType,
                    'number_prefix' => $templateType === 'power_of_attorney' ? 'KSA' : 'SPTJM',
                    'template_key' => $templateType,
                    'template_version' => 1,
                    'verification_token' => $verificationToken,
                    'original_name' => sprintf('%s-%s.pdf', $templateType, strtolower($docNumberHint)),
                    'signatory_name' => $payload['grantor_name'],
                    'signatory_title' => $templateType === 'power_of_attorney' ? 'Pemberi Kuasa' : 'Pembuat Pernyataan',
                    'signature_mode' => 'image',
                    'service_ticket_id' => $linkedReq?->service_ticket_id,
                ],
                actor: $actor,
            );

            if ($linkedReq !== null) {
                $field = $templateType === 'power_of_attorney' ? 'power_of_attorney_document_id' : 'sptjm_document_id';
                $linkedReq->forceFill([$field => (int) $doc->id])->save();
            }

            $this->audit->log(
                eventType: "administration.{$templateType}.generated",
                targetType: 'ServiceDocument',
                targetId: (string) $doc->id,
                newValues: ['legalization_id' => $linkedReq?->id, 'document_number' => $doc->document_number],
                actor: $actor,
                schoolId: $schoolId,
            );

            return [
                'document_id' => (int) $doc->id,
                'document_number' => (string) $doc->document_number,
                'verification_token' => (string) $doc->verification_token,
                'template_type' => $templateType,
            ];
        });
    }

    public function verifyOriginalPhysical(User $actor, LegalizationRequest $req, string $note): LegalizationRequest
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $req->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, $schoolId), 403);

        return DB::transaction(function () use ($actor, $schoolId, $req, $note): LegalizationRequest {
            /** @var LegalizationRequest $locked */
            $locked = LegalizationRequest::query()->where('school_id', $schoolId)->whereKey($req->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'status' => 'verified_original',
                'original_verified_at' => now(),
                'original_verified_by' => $actor->id,
                'original_verification_note' => trim($note) ?: 'Dokumen asli sesuai arsip sekolah.',
            ])->save();

            if ($locked->service_ticket_id !== null) {
                $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
                if ($ticket !== null && ServiceWorkflowMap::canTransition((string) $ticket['public_status'], ServiceWorkflowMap::STATUS_IN_REVIEW)) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                        toInternalStatus: 'verified_original',
                        reason: $locked->original_verification_note,
                        actor: $actor,
                        expectedVersion: (int) $ticket['version'],
                        publicSummary: 'Berkas asli legalisir telah diperiksa fisik dan sesuai.',
                    );
                }
            }

            $this->audit->log(
                eventType: 'administration.legalization.original_verified',
                targetType: 'LegalizationRequest',
                targetId: (string) $locked->id,
                newValues: ['note' => $locked->original_verification_note],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    public function approveAndStamp(User $actor, LegalizationRequest $req, ?string $signatoryName = null, ?string $signatoryTitle = null): LegalizationRequest
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $req->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canApproveFinalDecision($actor, $schoolId) && $this->accessScope->canSignOfficialDocument($actor, $schoolId), 403);

        return DB::transaction(function () use ($actor, $schoolId, $req, $signatoryName, $signatoryTitle): LegalizationRequest {
            /** @var LegalizationRequest $locked */
            $locked = LegalizationRequest::query()->where('school_id', $schoolId)->whereKey($req->id)->lockForUpdate()->firstOrFail();

            if ($locked->original_verified_at === null) {
                throw ValidationException::withMessages(['status' => 'Verifikasi dokumen asli harus dilakukan sebelum pengesahan legalisir.']);
            }

            $school = $this->schoolScope->getSchoolDetails($schoolId);
            $verificationToken = Str::random(40);
            $sigName = trim((string) ($signatoryName ?: $actor->name));
            $sigTitle = trim((string) ($signatoryTitle ?: 'Kepala Tata Usaha / Pejabat Legalisir'));

            $qrDataUri = $this->buildQrDataUri(url('/verifikasi?code='.urlencode($locked->legalization_code)));

            $bytes = Pdf::loadView('administration.legalization-proof', [
                'school' => $school,
                'legalization' => $locked,
                'documentNumber' => $locked->legalization_code,
                'signatoryName' => $sigName,
                'signatoryTitle' => $sigTitle,
                'verificationToken' => $verificationToken,
                'qrDataUri' => $qrDataUri,
            ])->setPaper('a4')->output();

            $doc = $this->documentService->issueGeneratedDocument(
                schoolId: $schoolId,
                bytes: $bytes,
                payload: [
                    'document_kind' => 'legalization',
                    'number_prefix' => 'LEG',
                    'template_key' => 'legalization_certificate',
                    'template_version' => 1,
                    'verification_token' => $verificationToken,
                    'original_name' => sprintf('pengesahan-legalisir-%s.pdf', $locked->legalization_code),
                    'signatory_name' => $sigName,
                    'signatory_title' => $sigTitle,
                    'signature_mode' => 'image',
                    'service_ticket_id' => $locked->service_ticket_id,
                ],
                actor: $actor,
            );

            $locked->forceFill([
                'status' => 'approved',
                'legalization_document_id' => (int) $doc->id,
            ])->save();

            if ($locked->service_ticket_id !== null) {
                $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
                if ($ticket !== null && ServiceWorkflowMap::canTransition((string) $ticket['public_status'], ServiceWorkflowMap::STATUS_APPROVED)) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_APPROVED,
                        toInternalStatus: 'approved',
                        reason: 'Pengesahan legalisir selesai dan siap diambil.',
                        actor: $actor,
                        expectedVersion: (int) $ticket['version'],
                        publicSummary: 'Legalisir telah disahkan dan siap diambil di Tata Usaha.',
                    );
                }
            }

            if ($locked->applicant_user_id !== null) {
                $this->outbox->enqueue(
                    schoolId: $schoolId,
                    eventKey: 'legalisir.approved',
                    channel: 'in_app',
                    recipientType: 'user',
                    recipientId: (int) $locked->applicant_user_id,
                    templateKey: 'legalisir_approved',
                    payload: ['legalization_code' => $locked->legalization_code],
                    idempotencyKey: sprintf('leg-app-%d', $locked->id),
                    dispatchJob: false,
                );
            }

            $this->audit->log(
                eventType: 'administration.legalization.approved',
                targetType: 'LegalizationRequest',
                targetId: (string) $locked->id,
                newValues: ['document_id' => $doc->id],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    public function requestRevisionOrReject(User $actor, LegalizationRequest $req, string $action, string $reason): LegalizationRequest
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $req->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, $schoolId), 403);

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => 'Catatan perbaikan atau penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $req, $action, $cleanReason): LegalizationRequest {
            /** @var LegalizationRequest $locked */
            $locked = LegalizationRequest::query()->where('school_id', $schoolId)->whereKey($req->id)->lockForUpdate()->firstOrFail();

            $targetStatus = $action === 'request_revision' ? 'needs_revision' : 'rejected';
            $locked->forceFill([
                'status' => $targetStatus,
                'revision_note' => $action === 'request_revision' ? $cleanReason : $locked->revision_note,
                'rejection_reason' => $action === 'reject' ? $cleanReason : $locked->rejection_reason,
            ])->save();

            if ($locked->service_ticket_id !== null) {
                $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
                $targetPublic = $action === 'request_revision' ? ServiceWorkflowMap::STATUS_NEEDS_REVISION : ServiceWorkflowMap::STATUS_REJECTED;
                if ($ticket !== null && ServiceWorkflowMap::canTransition((string) $ticket['public_status'], $targetPublic)) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: $targetPublic,
                        toInternalStatus: $targetStatus,
                        reason: $cleanReason,
                        actor: $actor,
                        expectedVersion: (int) $ticket['version'],
                        publicSummary: $action === 'request_revision'
                            ? 'Permohonan legalisir dikembalikan untuk perbaikan data/lampiran.'
                            : 'Permohonan legalisir ditolak.',
                    );
                }
            }

            return $locked->refresh();
        });
    }

    public function recordPickup(User $actor, LegalizationRequest $req, string $pickedUpByName, string $relation = 'Pemohon Langsung', ?string $notes = null): LegalizationRequest
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $req->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canVerifyQueue($actor, $schoolId), 403);

        $cleanName = trim($pickedUpByName);
        if ($cleanName === '') {
            throw ValidationException::withMessages(['picked_up_by_name' => 'Nama pengambil dokumen legalisir wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $req, $cleanName, $relation, $notes): LegalizationRequest {
            /** @var LegalizationRequest $locked */
            $locked = LegalizationRequest::query()->where('school_id', $schoolId)->whereKey($req->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Hanya legalisir berstatus disahkan yang dapat dicatat pengambilannya.']);
            }

            $locked->forceFill([
                'status' => 'completed',
                'picked_up_at' => now(),
                'picked_up_by_name' => $cleanName,
                'pickup_relation' => trim($relation) ?: 'Pemohon Langsung',
                'pickup_notes' => $notes !== null ? trim($notes) ?: null : null,
            ])->save();

            if ($locked->service_ticket_id !== null) {
                $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
                if ($ticket !== null && ServiceWorkflowMap::canTransition((string) $ticket['public_status'], ServiceWorkflowMap::STATUS_COMPLETED)) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_COMPLETED,
                        toInternalStatus: 'completed',
                        reason: sprintf('Diambil oleh %s (%s).', $cleanName, $locked->pickup_relation),
                        actor: $actor,
                        expectedVersion: (int) $ticket['version'],
                        publicSummary: 'Berkas legalisir telah diambil oleh pemohon/kuasa.',
                    );
                }
            }

            $this->audit->log(
                eventType: 'administration.legalization.picked_up',
                targetType: 'LegalizationRequest',
                targetId: (string) $locked->id,
                newValues: ['picked_up_by_name' => $cleanName, 'pickup_relation' => $locked->pickup_relation],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    private function buildQrDataUri(string $url): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(160, 2),
                new SvgImageBackEnd,
            ),
        );

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($url));
    }
}
