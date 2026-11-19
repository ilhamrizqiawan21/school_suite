<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Core\Domain\ServiceWorkflowMap;

/**
 * Unified cross-service tracking aggregator (M4-TU-04, M4-FND-06).
 * Reads authoritative state from ServiceTicketService & ServiceDocumentService
 * and enforces applicant identity verification before exposing details.
 */
class UnifiedTrackingService
{
    /** @var array<string, string> */
    public const TRACKING_CATEGORIES = [
        'all' => 'Semua Layanan',
        'ptsp' => 'PTSP & Persuratan',
        'izin' => 'Izin & Dispensasi',
        'legalisir' => 'Legalisir Ijazah',
        'mutasi' => 'Mutasi Siswa',
        'surat_eksternal' => 'Surat Eksternal',
        'aduan' => 'Pengaduan (Siap Lapor)',
        'proposal' => 'Proposal & LPJ',
    ];

    public function __construct(
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function track(
        int $schoolId,
        string $ticketCode,
        string $category = 'all',
        ?string $verificationSecret = null,
        ?User $viewer = null,
        ?int $externalApplicantId = null,
    ): array {
        $normalizedCode = strtoupper(trim($ticketCode));
        $normalizedCategory = array_key_exists($category, self::TRACKING_CATEGORIES) ? $category : 'all';

        if ($normalizedCode === '') {
            return [
                'found' => false,
                'authorized' => false,
                'reason' => 'empty_query',
            ];
        }

        $snapshot = $this->ticketService->getTicketSnapshotByCode($schoolId, $normalizedCode);
        if ($snapshot === null) {
            return [
                'found' => false,
                'authorized' => false,
                'reason' => 'not_found',
            ];
        }

        if ($normalizedCategory !== 'all' && $snapshot['service_type'] !== $normalizedCategory) {
            return [
                'found' => false,
                'authorized' => false,
                'reason' => 'category_mismatch',
            ];
        }

        $isOwnerExternal = $externalApplicantId !== null
            && $snapshot['external_applicant_id'] !== null
            && (int) $snapshot['external_applicant_id'] === $externalApplicantId;

        if ($isOwnerExternal) {
            return $this->buildAuthorizedPayload($schoolId, $snapshot);
        }

        $lookup = $this->ticketService->lookupPublicStatus(
            schoolId: $schoolId,
            ticketCode: $normalizedCode,
            verificationSecret: $verificationSecret,
            viewer: $viewer,
        );

        if (! ($lookup['found'] ?? false)) {
            return [
                'found' => false,
                'authorized' => false,
                'reason' => $lookup['reason'] ?? 'not_found',
            ];
        }

        if (! ($lookup['authorized'] ?? false)) {
            return [
                'found' => true,
                'authorized' => false,
                'reason' => $lookup['reason'] ?? 'identity_verification_required',
                'ticket' => [
                    'ticket_code' => $snapshot['ticket_code'],
                    'service_type' => $snapshot['service_type'],
                    'service_label' => self::TRACKING_CATEGORIES[$snapshot['service_type']] ?? strtoupper((string) $snapshot['service_type']),
                ],
            ];
        }

        return $this->buildAuthorizedPayload($schoolId, $snapshot, $lookup['ticket'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>|null  $lookupTicket
     * @return array<string, mixed>
     */
    private function buildAuthorizedPayload(int $schoolId, array $snapshot, ?array $lookupTicket = null): array
    {
        $serviceType = (string) $snapshot['service_type'];
        $ticketCode = (string) $snapshot['ticket_code'];
        $publicStatus = (string) ($lookupTicket['public_status'] ?? $snapshot['public_status']);
        $publicSummary = (string) ($lookupTicket['public_summary'] ?? $snapshot['public_summary']);

        $isExpiredOrCancelled = in_array($publicStatus, [
            ServiceWorkflowMap::STATUS_EXPIRED,
            ServiceWorkflowMap::STATUS_CANCELLED,
        ], true);

        $documents = $isExpiredOrCancelled || $serviceType === 'aduan'
            ? []
            : array_values(array_map(
                static fn (array $doc): array => [
                    'id' => $doc['id'],
                    'document_kind' => $doc['document_kind'],
                    'document_number' => $doc['document_number'],
                    'original_name' => $doc['original_name'],
                    'status' => $doc['status'],
                    'issued_at' => $doc['issued_at'],
                ],
                $this->documentService->listDocumentsForTicket($schoolId, (int) $snapshot['id']),
            ));

        $actions = [];
        if ($serviceType === 'ptsp') {
            $actions['receipt_url'] = route('administration.ptsp.public.receipt', $ticketCode, false);
            if ($publicStatus === ServiceWorkflowMap::STATUS_NEEDS_REVISION) {
                $actions['revision_url'] = route('administration.ptsp.public.revision', $ticketCode, false);
            }
        } elseif ($serviceType === 'surat_eksternal') {
            $actions['receipt_url'] = route('administration.external-letters.receipt', $ticketCode, false);
        }

        // For anonymous complaints (aduan) or privacy, never expose applicant personal details here.
        $safeSnapshotMeta = $serviceType === 'aduan'
            ? []
            : array_filter([
                'service_name' => $snapshot['applicant_snapshot']['service_name'] ?? null,
                'agenda_number' => $snapshot['applicant_snapshot']['agenda_number'] ?? null,
            ], static fn (mixed $v): bool => $v !== null && $v !== '');

        return [
            'found' => true,
            'authorized' => true,
            'ticket' => [
                'ticket_code' => $ticketCode,
                'service_type' => $serviceType,
                'service_label' => self::TRACKING_CATEGORIES[$serviceType] ?? strtoupper($serviceType),
                'public_status' => $publicStatus,
                'public_summary' => $publicSummary,
                'updated_at' => $lookupTicket['updated_at'] ?? $snapshot['updated_at'],
                'meta' => $safeSnapshotMeta,
                'actions' => $actions,
                'documents' => $documents,
                'timeline' => array_map(
                    static fn (array $item): array => [
                        'public_status' => $item['public_status'],
                        'created_at' => $item['created_at'],
                    ],
                    $snapshot['transitions'] ?? [],
                ),
            ],
        ];
    }
}
