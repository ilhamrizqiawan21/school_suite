<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Modules\Homeroom\Infrastructure\Models\ComplaintReport;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Siap Lapor named & anonymous complaint reporting (`M4-SERV-01`)
 * and Task Force restricted handling + moderated public transparency (`M4-SERV-02`).
 */
class ComplaintReportService
{
    public const CATEGORIES = [
        'akademik' => 'Akademik & Layanan Belajar',
        'perundungan' => 'Perundungan & Kekerasan (Anti-Bullying)',
        'fasilitas' => 'Sarana, Prasarana & Fasilitas',
        'staf' => 'Etika & Pelayanan Staf',
        'keselamatan' => 'Keselamatan & Keamanan Lingkungan',
        'lainnya' => 'Pengaduan Lainnya',
    ];

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly AuditLogger $audit,
    ) {}

    public function assertTaskForceActor(?User $actor): void
    {
        if ($actor === null || ! $actor->is_active || ! $actor->hasRole([
            RoleEnum::ADMIN->value,
            RoleEnum::TASK_FORCE->value,
            RoleEnum::COUNSELOR->value,
        ])) {
            abort(403, 'Akses antrean Satgas Aduan hanya untuk Satgas, Guru BK, dan Admin.');
        }
    }

    public function assertReportAccess(User $actor, ComplaintReport $report): void
    {
        $this->assertTaskForceActor($actor);
        abort_unless((int) $report->school_id === (int) $actor->school_id, 404);
        abort_unless($actor->hasRole(RoleEnum::ADMIN->value) || $report->assigned_to_user_id === null
            || (int) $report->assigned_to_user_id === (int) $actor->id, 403);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $evidenceFiles
     * @return array{report: ComplaintReport, ticket: array<string, mixed>, plain_verification_secret: string}
     */
    public function submitReport(
        int $schoolId,
        array $data,
        array $evidenceFiles = [],
        ?User $actor = null,
    ): array {
        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            $existing = ComplaintReport::query()
                ->where('school_id', $schoolId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing !== null) {
                $ticket = $existing->service_ticket_id
                    ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                    : [];

                return [
                    'report' => $existing,
                    'ticket' => $ticket ?? [],
                    'plain_verification_secret' => '',
                ];
            }
        }

        $isAnonymous = (bool) ($data['is_anonymous'] ?? false);
        $category = (string) ($data['category'] ?? 'lainnya');
        if (! array_key_exists($category, self::CATEGORIES)) {
            throw ValidationException::withMessages(['category' => 'Kategori pengaduan tidak valid.']);
        }

        if (! $isAnonymous && trim((string) ($data['reporter_name'] ?? ($actor?->name ?? ''))) === '') {
            throw ValidationException::withMessages([
                'reporter_name' => 'Nama pelapor wajib diisi untuk laporan bernama, atau aktifkan mode anonim.',
            ]);
        }

        return DB::transaction(function () use ($schoolId, $data, $evidenceFiles, $actor, $isAnonymous, $category, $idempotencyKey): array {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                $existing = ComplaintReport::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing !== null) {
                    $ticket = $existing->service_ticket_id
                        ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                        : [];

                    return [
                        'report' => $existing,
                        'ticket' => $ticket ?? [],
                        'plain_verification_secret' => '',
                    ];
                }
            }

            $code = 'ADU-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(6));
            $plainSecret = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Anonymous reports NEVER store reporter identity or user linkage
            $reporterUserId = $isAnonymous ? null : $actor?->id;
            $reporterName = $isAnonymous ? null : trim((string) ($data['reporter_name'] ?? ($actor?->name ?? 'Pelapor')));
            $reporterContact = $isAnonymous ? null : (isset($data['reporter_contact']) ? trim((string) $data['reporter_contact']) ?: null : null);
            $reporterRole = $isAnonymous ? 'anonim' : (isset($data['reporter_role']) ? trim((string) $data['reporter_role']) ?: null : ($actor?->role?->name ?? 'umum'));

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'aduan',
                'prefix' => 'ADU',
                'applicant_user_id' => $reporterUserId,
                'is_anonymous' => $isAnonymous,
                'applicant_snapshot' => [
                    'report_code' => $code,
                    'category' => $category,
                    'reporter_label' => $isAnonymous ? 'Pelapor Anonim' : $reporterName,
                ],
                'verification_secret' => $plainSecret,
                'internal_status' => 'submitted',
                'public_summary' => sprintf('Laporan %s (%s) diterima oleh Satgas Sekolah.', $code, self::CATEGORIES[$category]),
                'idempotency_key' => $idempotencyKey,
            ], $isAnonymous ? null : $actor);

            $evidenceIds = [];
            foreach ($evidenceFiles as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'evidence',
                    ticket: $ticket,
                    uploader: $reporterUserId ? $actor : null,
                );
                $evidenceIds[] = (int) $doc->id;
            }

            $report = ComplaintReport::query()->create([
                'school_id' => $schoolId,
                'service_ticket_id' => (int) $ticket->id,
                'report_code' => $code,
                'category' => $category,
                'is_anonymous' => $isAnonymous,
                'reporter_user_id' => $reporterUserId,
                'reporter_name' => $reporterName,
                'reporter_contact' => $reporterContact,
                'reporter_role' => $reporterRole,
                'title' => trim((string) $data['title']),
                'description' => trim((string) $data['description']),
                'incident_date' => $data['incident_date'] ?? null,
                'location' => isset($data['location']) ? trim((string) $data['location']) ?: null : null,
                'evidence_document_ids' => $evidenceIds,
                'resolution_document_ids' => [],
                'status' => 'submitted',
                'sla_due_at' => now()->addHours(48),
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->log(
                eventType: 'homeroom.complaint.submitted',
                targetType: 'ComplaintReport',
                targetId: (string) $report->id,
                newValues: ['report_code' => $code, 'category' => $category, 'is_anonymous' => $isAnonymous],
                actor: $reporterUserId ? $actor : null,
                schoolId: $schoolId,
            );

            return [
                'report' => $report->refresh(),
                'ticket' => $this->ticketService->getTicketSnapshotById($schoolId, (int) $ticket->id) ?? [],
                'plain_verification_secret' => $plainSecret,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $resolutionFiles
     */
    public function handleReport(
        User $actor,
        ComplaintReport $report,
        array $data,
        array $resolutionFiles = [],
    ): ComplaintReport {
        $this->assertTaskForceActor($actor);
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $report->school_id === $schoolId, 404);

        $newStatus = (string) ($data['status'] ?? $report->status);
        if (! in_array($newStatus, ['submitted', 'in_review', 'needs_revision', 'completed', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Status penanganan aduan tidak valid.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $report, $data, $newStatus, $resolutionFiles): ComplaintReport {
            /** @var ComplaintReport $locked */
            $locked = ComplaintReport::query()->where('school_id', $schoolId)->whereKey($report->id)->lockForUpdate()->firstOrFail();

            $this->assertReportAccess($actor, $locked);
            if (in_array($newStatus, ['needs_revision', 'rejected'], true) && trim((string) ($data['task_force_reply'] ?? '')) === '') {
                throw ValidationException::withMessages(['task_force_reply' => 'Alasan revisi atau penolakan wajib diisi.']);
            }
            if (! empty($data['assigned_to_user_id'])) {
                $validAssignee = User::query()->whereKey((int) $data['assigned_to_user_id'])->where('school_id', $schoolId)
                    ->where('is_active', true)->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'counselor', 'task_force']))->exists();
                if (! $validAssignee) {
                    throw ValidationException::withMessages(['assigned_to_user_id' => 'Petugas penanganan tidak valid pada sekolah ini.']);
                }
            }
            $resIds = is_array($locked->resolution_document_ids) ? $locked->resolution_document_ids : [];
            $ticket = $locked->service_ticket_id
                ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id)
                : null;

            if ($ticket !== null && $newStatus !== $ticket['public_status']
                && ! ServiceWorkflowMap::canTransition((string) $ticket['public_status'], $newStatus)
                && ! ($ticket['public_status'] === ServiceWorkflowMap::STATUS_SUBMITTED && $newStatus === ServiceWorkflowMap::STATUS_COMPLETED)) {
                throw ValidationException::withMessages(['status' => 'Status aduan sudah berubah. Muat ulang sebelum melanjutkan.']);
            }

            foreach ($resolutionFiles as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'evidence',
                    ticket: null,
                    uploader: $actor,
                );
                $resIds[] = (int) $doc->id;
            }

            $oldStatus = $locked->status;
            $locked->forceFill([
                'assigned_to_user_id' => ! empty($data['assigned_to_user_id']) ? (int) $data['assigned_to_user_id'] : ($locked->assigned_to_user_id ?? $actor->id),
                'status' => $newStatus,
                'task_force_reply' => isset($data['task_force_reply']) ? trim((string) $data['task_force_reply']) ?: null : $locked->task_force_reply,
                'resolution_summary' => isset($data['resolution_summary']) ? trim((string) $data['resolution_summary']) ?: null : $locked->resolution_summary,
                'moderated_public_summary' => isset($data['moderated_public_summary']) ? trim((string) $data['moderated_public_summary']) ?: null : $locked->moderated_public_summary,
                'publish_to_transparency' => array_key_exists('publish_to_transparency', $data)
                    ? (bool) $data['publish_to_transparency']
                    : $locked->publish_to_transparency,
                'resolution_document_ids' => $resIds,
                'resolved_at' => in_array($newStatus, ['completed', 'rejected'], true) ? ($locked->resolved_at ?? now()) : $locked->resolved_at,
            ])->save();

            if ($ticket !== null && $newStatus !== $ticket['public_status']) {
                $currentPublic = (string) $ticket['public_status'];
                $currentVersion = (int) $ticket['version'];

                if ($currentPublic === ServiceWorkflowMap::STATUS_SUBMITTED && $newStatus === ServiceWorkflowMap::STATUS_COMPLETED) {
                    $mid = $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                        toInternalStatus: 'in_review',
                        reason: 'Laporan ditelaah oleh Satgas.',
                        actor: $actor,
                        expectedVersion: $currentVersion,
                    );
                    $currentPublic = $mid->public_status;
                    $currentVersion = (int) $mid->version;
                }

                if (ServiceWorkflowMap::canTransition($currentPublic, $newStatus)) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: $newStatus,
                        toInternalStatus: $newStatus,
                        reason: $locked->task_force_reply ?: 'Pembaruan status penanganan Satgas.',
                        actor: $actor,
                        expectedVersion: $currentVersion,
                        publicSummary: $locked->task_force_reply ?: sprintf('Status aduan diperbarui menjadi %s.', $newStatus),
                    );
                }
            }

            $this->audit->log(
                eventType: 'homeroom.complaint.handled',
                targetType: 'ComplaintReport',
                targetId: (string) $locked->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => $newStatus,
                    'publish_to_transparency' => $locked->publish_to_transparency,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Public moderated transparency statistics & anonymized summaries without leaking any PII (M4-SERV-02).
     *
     * @return array<string, mixed>
     */
    public function publicTransparencySummary(int $schoolId): array
    {
        $all = ComplaintReport::query()->where('school_id', $schoolId)->get(['id', 'category', 'status', 'moderated_public_summary', 'publish_to_transparency', 'resolved_at']);

        $byCategory = [];
        foreach (self::CATEGORIES as $key => $label) {
            $subset = $all->where('category', $key);
            $byCategory[] = [
                'category' => $key,
                'label' => $label,
                'total' => $subset->count(),
                'resolved' => $subset->whereIn('status', ['completed', 'rejected'])->count(),
            ];
        }

        $publishedSummaries = $all
            ->where('publish_to_transparency', true)
            ->filter(fn (ComplaintReport $r): bool => trim((string) $r->moderated_public_summary) !== '')
            ->sortByDesc('resolved_at')
            ->take(20)
            ->map(fn (ComplaintReport $r): array => [
                'id' => (int) $r->id,
                'category' => (string) $r->category,
                'category_label' => self::CATEGORIES[$r->category] ?? $r->category,
                'status' => (string) $r->status,
                'summary' => (string) $r->moderated_public_summary,
                'resolved_at' => $r->resolved_at?->toDateString(),
            ])
            ->values()
            ->all();

        return [
            'totals' => [
                'total' => $all->count(),
                'in_progress' => $all->whereIn('status', ['submitted', 'in_review', 'needs_revision'])->count(),
                'resolved' => $all->whereIn('status', ['completed', 'rejected'])->count(),
            ],
            'by_category' => $byCategory,
            'published_summaries' => $publishedSummaries,
        ];
    }
}
