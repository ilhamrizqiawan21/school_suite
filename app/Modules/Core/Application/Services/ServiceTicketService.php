<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Modules\Core\Infrastructure\Models\ExternalApplicant;
use App\Modules\Core\Infrastructure\Models\ServiceTicket;
use App\Modules\Core\Infrastructure\Models\ServiceTicketTransition;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;

class ServiceTicketService
{
    private const TICKET_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Register or update an external applicant identity separate from internal staff accounts (M4-FND-03).
     *
     * @param  array{
     *     applicant_type: string,
     *     full_name: string,
     *     raw_identifier?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     organization_name?: ?string,
     *     user_id?: ?int,
     *     is_verified?: bool
     * }  $data
     */
    public function registerExternalApplicant(int $schoolId, array $data): ExternalApplicant
    {
        $rawIdentifier = isset($data['raw_identifier']) ? trim((string) $data['raw_identifier']) : '';
        $identifierLast4 = $rawIdentifier !== '' ? substr($rawIdentifier, -4) : null;
        $identifierHash = $rawIdentifier !== '' ? hash('sha256', $schoolId.'|'.$rawIdentifier) : null;
        $isVerified = (bool) ($data['is_verified'] ?? false);

        $applicant = ExternalApplicant::query()->create([
            'school_id' => $schoolId,
            'user_id' => $data['user_id'] ?? null,
            'applicant_type' => $data['applicant_type'],
            'full_name' => trim($data['full_name']),
            'identifier_last4' => $identifierLast4,
            'identifier_hash' => $identifierHash,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'is_verified' => $isVerified,
            'verified_at' => $isVerified ? now() : null,
        ]);

        $this->auditLogger->log(
            eventType: 'core.external_applicant.registered',
            targetType: 'ExternalApplicant',
            targetId: (string) $applicant->id,
            newValues: [
                'applicant_type' => $applicant->applicant_type,
                'is_verified' => $applicant->is_verified,
            ],
            schoolId: $schoolId,
        );

        return $applicant;
    }

    /**
     * Generate a hard-to-guess non-sequential ticket code (M4-FND-06).
     */
    public function generateTicketCode(string $prefix, int $year): string
    {
        $cleanPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'SRV');
        $alphabetLength = strlen(self::TICKET_ALPHABET);
        $randomPart = '';

        for ($i = 0; $i < 8; $i++) {
            $randomPart .= self::TICKET_ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return sprintf('%s-%d-%s', $cleanPrefix, $year, $randomPart);
    }

    /**
     * Issue a new service ticket atomically with idempotency protection (M4-FND-04, M4-FND-06).
     *
     * @param  array{
     *     service_type: string,
     *     prefix?: string,
     *     applicant_user_id?: ?int,
     *     external_applicant_id?: ?int,
     *     subject_student_id?: ?int,
     *     applicant_snapshot: array<string, mixed>,
     *     verification_secret?: ?string,
     *     internal_status?: string,
     *     public_summary?: ?string,
     *     is_anonymous?: bool,
     *     idempotency_key?: ?string,
     *     expires_at?: ?CarbonInterface
     * }  $payload
     */
    public function issueTicket(int $schoolId, array $payload, ?User $actor = null): ServiceTicket
    {
        $serviceType = trim($payload['service_type']);
        if ($serviceType === '') {
            throw new InvalidArgumentException('Jenis layanan tiket wajib diisi.');
        }

        $idempotencyKey = isset($payload['idempotency_key']) && trim((string) $payload['idempotency_key']) !== ''
            ? trim((string) $payload['idempotency_key'])
            : null;

        return DB::transaction(function () use ($schoolId, $payload, $serviceType, $idempotencyKey, $actor): ServiceTicket {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                $existing = ServiceTicket::query()
                    ->where('school_id', $schoolId)
                    ->where('service_type', $serviceType)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $year = (int) $this->schoolScope->localNow($schoolId)->format('Y');
            $prefix = $payload['prefix'] ?? strtoupper($serviceType);
            $ticketCode = $this->uniqueTicketCode($prefix, $year);

            $rawSecret = isset($payload['verification_secret']) ? trim((string) $payload['verification_secret']) : '';
            $secretHash = $rawSecret !== '' ? Hash::make($rawSecret) : null;

            $initialPublicStatus = ServiceWorkflowMap::STATUS_SUBMITTED;
            $initialInternalStatus = $payload['internal_status'] ?? $initialPublicStatus;
            $publicSummary = $payload['public_summary'] ?? ServiceWorkflowMap::publicSummaryFor($initialPublicStatus);

            $ticket = ServiceTicket::query()->create([
                'school_id' => $schoolId,
                'service_type' => $serviceType,
                'ticket_code' => $ticketCode,
                'verification_secret_hash' => $secretHash,
                'applicant_user_id' => $payload['applicant_user_id'] ?? null,
                'external_applicant_id' => $payload['external_applicant_id'] ?? null,
                'subject_student_id' => $payload['subject_student_id'] ?? null,
                'applicant_snapshot' => $payload['applicant_snapshot'],
                'public_status' => $initialPublicStatus,
                'internal_status' => $initialInternalStatus,
                'public_summary' => $publicSummary,
                'is_anonymous' => (bool) ($payload['is_anonymous'] ?? false),
                'idempotency_key' => $idempotencyKey,
                'version' => 1,
                'expires_at' => $payload['expires_at'] ?? null,
            ]);

            ServiceTicketTransition::query()->create([
                'school_id' => $schoolId,
                'service_ticket_id' => $ticket->id,
                'actor_id' => $actor?->id,
                'actor_role' => $actor?->role?->name,
                'from_status' => null,
                'to_status' => $initialInternalStatus,
                'public_status' => $initialPublicStatus,
                'reason' => null,
                'metadata' => ['event' => 'issued'],
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                eventType: 'service_ticket.issued',
                targetType: 'ServiceTicket',
                targetId: (string) $ticket->id,
                newValues: [
                    'ticket_code' => $ticket->ticket_code,
                    'service_type' => $ticket->service_type,
                    'public_status' => $ticket->public_status,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $ticket;
        });
    }

    /**
     * Transition a ticket status atomically with optimistic concurrency control and audit history (M4-FND-04).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function transition(
        int $ticketId,
        int $schoolId,
        string $toPublicStatus,
        ?string $toInternalStatus = null,
        ?string $reason = null,
        ?User $actor = null,
        ?int $expectedVersion = null,
        ?string $publicSummary = null,
        array $metadata = [],
    ): ServiceTicket {
        return DB::transaction(function () use (
            $ticketId,
            $schoolId,
            $toPublicStatus,
            $toInternalStatus,
            $reason,
            $actor,
            $expectedVersion,
            $publicSummary,
            $metadata,
        ): ServiceTicket {
            /** @var ServiceTicket $ticket */
            $ticket = ServiceTicket::query()
                ->where('school_id', $schoolId)
                ->whereKey($ticketId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && $ticket->version !== $expectedVersion) {
                throw new RuntimeException('Data tiket telah diperbarui oleh proses lain. Muat ulang sebelum melanjutkan.');
            }

            ServiceWorkflowMap::assertCanTransition($ticket->public_status, $toPublicStatus, $reason);

            $previousPublicStatus = $ticket->public_status;
            $previousInternalStatus = $ticket->internal_status;
            $nextInternalStatus = $toInternalStatus ?? $toPublicStatus;

            $ticket->forceFill([
                'public_status' => $toPublicStatus,
                'internal_status' => $nextInternalStatus,
                'public_summary' => $publicSummary ?? ServiceWorkflowMap::publicSummaryFor($toPublicStatus),
                'version' => $ticket->version + 1,
                'revoked_at' => $toPublicStatus === ServiceWorkflowMap::STATUS_CANCELLED ? now() : $ticket->revoked_at,
            ])->save();

            ServiceTicketTransition::query()->create([
                'school_id' => $schoolId,
                'service_ticket_id' => $ticket->id,
                'actor_id' => $actor?->id,
                'actor_role' => $actor?->role?->name,
                'from_status' => $previousInternalStatus,
                'to_status' => $nextInternalStatus,
                'public_status' => $toPublicStatus,
                'reason' => $reason !== null ? trim($reason) : null,
                'metadata' => $metadata !== [] ? $this->auditLogger->sanitize($metadata) : null,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                eventType: 'service_ticket.transitioned',
                targetType: 'ServiceTicket',
                targetId: (string) $ticket->id,
                oldValues: [
                    'public_status' => $previousPublicStatus,
                    'internal_status' => $previousInternalStatus,
                ],
                newValues: [
                    'public_status' => $toPublicStatus,
                    'internal_status' => $nextInternalStatus,
                    'version' => $ticket->version,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $ticket->refresh();
        });
    }

    /**
     * Public/owner ticket lookup returning only approved minimum status fields (M4-FND-06).
     *
     * @return array{
     *     found: bool,
     *     authorized: bool,
     *     reason?: string,
     *     ticket?: array{
     *         ticket_code: string,
     *         service_type: string,
     *         public_status: string,
     *         public_summary: string,
     *         updated_at: ?string,
     *         timeline: list<array{public_status: string, created_at: ?string}>
     *     }
     * }
     */
    public function lookupPublicStatus(
        int $schoolId,
        string $ticketCode,
        ?string $verificationSecret = null,
        ?User $viewer = null,
    ): array {
        $normalizedCode = strtoupper(trim($ticketCode));
        if ($normalizedCode === '') {
            return ['found' => false, 'authorized' => false, 'reason' => 'not_found'];
        }

        /** @var ServiceTicket|null $ticket */
        $ticket = ServiceTicket::query()
            ->with('transitions')
            ->where('school_id', $schoolId)
            ->where('ticket_code', $normalizedCode)
            ->first();

        if ($ticket === null) {
            return ['found' => false, 'authorized' => false, 'reason' => 'not_found'];
        }

        $isAuthorizedViewer = $this->isAuthorizedViewer($ticket, $viewer);

        if (! $isAuthorizedViewer) {
            if ($ticket->verification_secret_hash === null) {
                return ['found' => true, 'authorized' => false, 'reason' => 'identity_verification_required'];
            }

            $candidateSecret = trim((string) $verificationSecret);
            if ($candidateSecret === '' || ! Hash::check($candidateSecret, $ticket->verification_secret_hash)) {
                return ['found' => true, 'authorized' => false, 'reason' => 'identity_verification_failed'];
            }
        }

        if ($ticket->revoked_at !== null) {
            return [
                'found' => true,
                'authorized' => true,
                'ticket' => [
                    'ticket_code' => $ticket->ticket_code,
                    'service_type' => $ticket->service_type,
                    'public_status' => ServiceWorkflowMap::STATUS_CANCELLED,
                    'public_summary' => ServiceWorkflowMap::publicSummaryFor(ServiceWorkflowMap::STATUS_CANCELLED),
                    'updated_at' => $ticket->updated_at?->toIso8601String(),
                    'timeline' => $this->publicTimeline($ticket),
                ],
            ];
        }

        $effectiveStatus = $ticket->expires_at !== null && $ticket->expires_at->isPast()
            ? ServiceWorkflowMap::STATUS_EXPIRED
            : $ticket->public_status;

        $effectiveSummary = $effectiveStatus === ServiceWorkflowMap::STATUS_EXPIRED
            ? ServiceWorkflowMap::publicSummaryFor(ServiceWorkflowMap::STATUS_EXPIRED)
            : $ticket->public_summary;

        return [
            'found' => true,
            'authorized' => true,
            'ticket' => [
                'ticket_code' => $ticket->ticket_code,
                'service_type' => $ticket->service_type,
                'public_status' => $effectiveStatus,
                'public_summary' => $effectiveSummary,
                'updated_at' => $ticket->updated_at?->toIso8601String(),
                'timeline' => $this->publicTimeline($ticket),
            ],
        ];
    }

    private function isAuthorizedViewer(ServiceTicket $ticket, ?User $viewer): bool
    {
        if ($viewer === null || ! $this->accessScope->belongsToSchool($viewer, (int) $ticket->school_id)) {
            return false;
        }

        if ($ticket->applicant_user_id !== null && (int) $ticket->applicant_user_id === (int) $viewer->id) {
            return true;
        }

        // Anonymous reports (aduan) must stay restricted to task_force / admin.
        if ($ticket->is_anonymous && $ticket->service_type === 'aduan') {
            return $viewer->hasRole([RoleEnum::ADMIN->value, RoleEnum::TASK_FORCE->value]);
        }

        return $viewer->hasRole([
            RoleEnum::ADMIN->value,
            RoleEnum::OPERATOR->value,
            RoleEnum::PRINCIPAL->value,
            RoleEnum::PTSP->value,
        ]);
    }

    /**
     * Verify a ticket verification secret without exposing ticket details.
     */
    public function verifyTicketSecret(int $schoolId, string $ticketCode, string $secret): bool
    {
        $normalizedCode = strtoupper(trim($ticketCode));
        $candidate = trim($secret);

        if ($normalizedCode === '' || $candidate === '') {
            return false;
        }

        /** @var ServiceTicket|null $ticket */
        $ticket = ServiceTicket::query()
            ->where('school_id', $schoolId)
            ->where('ticket_code', $normalizedCode)
            ->first(['id', 'verification_secret_hash']);

        if ($ticket === null || $ticket->verification_secret_hash === null) {
            return false;
        }

        return Hash::check($candidate, $ticket->verification_secret_hash);
    }

    /**
     * Return a safe ticket snapshot by ticket code for cross-module Application services.
     *
     * @return array<string, mixed>|null
     */
    public function getTicketSnapshotByCode(int $schoolId, string $ticketCode): ?array
    {
        $normalizedCode = strtoupper(trim($ticketCode));
        if ($normalizedCode === '') {
            return null;
        }

        /** @var ServiceTicket|null $ticket */
        $ticket = ServiceTicket::query()
            ->with(['transitions' => fn ($q) => $q->orderBy('id')])
            ->where('school_id', $schoolId)
            ->where('ticket_code', $normalizedCode)
            ->first();

        return $ticket ? $this->serializeTicketSnapshot($ticket) : null;
    }

    /**
     * Return a safe ticket snapshot by ID for cross-module Application services.
     *
     * @return array<string, mixed>|null
     */
    public function getTicketSnapshotById(int $schoolId, int $ticketId): ?array
    {
        /** @var ServiceTicket|null $ticket */
        $ticket = ServiceTicket::query()
            ->with(['transitions' => fn ($q) => $q->orderBy('id')])
            ->where('school_id', $schoolId)
            ->whereKey($ticketId)
            ->first();

        return $ticket ? $this->serializeTicketSnapshot($ticket) : null;
    }

    /**
     * Batch-fetch ticket snapshots keyed by ticket ID to avoid N+1 queries.
     *
     * @param  list<int>  $ticketIds
     * @return array<int, array<string, mixed>>
     */
    public function getTicketsMapByIds(int $schoolId, array $ticketIds): array
    {
        $ids = array_values(array_unique(array_filter($ticketIds, static fn (mixed $id): bool => (int) $id > 0)));
        if ($ids === []) {
            return [];
        }

        return ServiceTicket::query()
            ->with(['transitions' => fn ($q) => $q->orderBy('id')])
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->map(fn (ServiceTicket $ticket): array => $this->serializeTicketSnapshot($ticket))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTicketSnapshot(ServiceTicket $ticket): array
    {
        $effectiveStatus = $ticket->revoked_at !== null
            ? ServiceWorkflowMap::STATUS_CANCELLED
            : ($ticket->expires_at !== null && $ticket->expires_at->isPast()
                ? ServiceWorkflowMap::STATUS_EXPIRED
                : $ticket->public_status);

        return [
            'id' => (int) $ticket->id,
            'school_id' => (int) $ticket->school_id,
            'service_type' => (string) $ticket->service_type,
            'ticket_code' => (string) $ticket->ticket_code,
            'has_verification_secret' => $ticket->verification_secret_hash !== null,
            'applicant_user_id' => $ticket->applicant_user_id ? (int) $ticket->applicant_user_id : null,
            'external_applicant_id' => $ticket->external_applicant_id ? (int) $ticket->external_applicant_id : null,
            'applicant_snapshot' => $ticket->applicant_snapshot ?? [],
            'public_status' => $effectiveStatus,
            'internal_status' => (string) $ticket->internal_status,
            'public_summary' => (string) $ticket->public_summary,
            'version' => (int) $ticket->version,
            'expires_at' => $ticket->expires_at?->toIso8601String(),
            'revoked_at' => $ticket->revoked_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'transitions' => $ticket->transitions
                ->map(static fn (ServiceTicketTransition $transition): array => [
                    'id' => (int) $transition->id,
                    'actor_role' => $transition->actor_role,
                    'from_status' => $transition->from_status,
                    'to_status' => $transition->to_status,
                    'public_status' => $transition->public_status,
                    'reason' => $transition->reason,
                    'metadata' => $transition->metadata,
                    'created_at' => $transition->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{public_status: string, created_at: ?string}>
     */
    private function publicTimeline(ServiceTicket $ticket): array
    {
        return $ticket->transitions
            ->map(static fn (ServiceTicketTransition $transition): array => [
                'public_status' => $transition->public_status,
                'created_at' => $transition->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function uniqueTicketCode(string $prefix, int $year): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $this->generateTicketCode($prefix, $year);
            if (! ServiceTicket::query()->where('ticket_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Gagal menghasilkan kode tiket unik.');
    }
}
