<?php

declare(strict_types=1);

namespace App\Modules\Integration\Application\Services;

use App\Models\User;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Modules\Integration\Infrastructure\Models\ActivityProposal;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProposalLpjService
{
    public const ACTIVITY_TYPES = [
        'academic_event' => 'Kegiatan Akademik / Olimpiade',
        'workshop' => 'Seminar / Workshop / Pelatihan',
        'competition' => 'Lomba & Kompetisi Siswa',
        'religious_day' => 'Peringatan Hari Besar Keagamaan',
        'extracurricular' => 'Kegiatan OSIS / Ekstrakurikuler',
        'other' => 'Kegiatan Lainnya',
    ];

    public const STATUS_LABELS = [
        ActivityProposal::STATUS_SUBMITTED => 'Menunggu Telaah',
        ActivityProposal::STATUS_IN_REVIEW => 'Sedang Ditelaah',
        ActivityProposal::STATUS_NEEDS_REVISION => 'Perlu Revisi',
        ActivityProposal::STATUS_APPROVED => 'Disetujui (Menunggu LPJ)',
        ActivityProposal::STATUS_REJECTED => 'Ditolak',
        ActivityProposal::STATUS_LPJ_SUBMITTED => 'LPJ Diunggah (Menunggu Penutupan)',
        ActivityProposal::STATUS_CLOSED => 'Selesai & Ditutup',
    ];

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function canSubmitTeacherWork(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            RoleEnum::ADMIN->value,
            RoleEnum::TEACHER->value,
            RoleEnum::HOMEROOM->value,
        ]);
    }

    public function canSubmitIndependent(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            RoleEnum::ADMIN->value,
            RoleEnum::OPERATOR->value,
            RoleEnum::TEACHER->value,
            RoleEnum::HOMEROOM->value,
            RoleEnum::STUDENT->value,
            RoleEnum::COMMITTEE->value,
        ]);
    }

    public function canReview(?User $user): bool
    {
        return $this->hasAnyRole($user, [
            RoleEnum::ADMIN->value,
            RoleEnum::OPERATOR->value,
            RoleEnum::PRINCIPAL->value,
            RoleEnum::COMMITTEE->value,
        ]);
    }

    public function assertCanSubmitTeacherWork(?User $user): void
    {
        abort_unless($this->canSubmitTeacherWork($user), 403, 'Hanya guru, wali kelas, atau admin yang dapat mengajukan proposal tugas kerja guru.');
    }

    public function assertCanSubmitIndependent(?User $user): void
    {
        abort_unless($this->canSubmitIndependent($user), 403, 'Peran Anda tidak memiliki akses ke portal proposal & LPJ mandiri.');
    }

    public function assertCanReview(?User $user): void
    {
        abort_unless($this->canReview($user), 403, 'Hanya reviewer berwenang (Admin/Operator/Kepala Sekolah/Komite) yang dapat menelaah proposal.');
    }

    /**
     * Preset example data matching parity reference (ev_72b43c).
     *
     * @return array<string, mixed>
     */
    public function sampleWebinarPreset(): array
    {
        $startDate = now()->addDays(14)->toDateString();
        $budget = $this->normalizeBudgetItems([
            ['name' => 'Honorarium Narasumber Praktisi', 'qty' => 2, 'unit' => 'sesi', 'unit_price' => 500000, 'note' => '2 narasumber utama'],
            ['name' => 'Paket Konsumsi Panitia & Moderator', 'qty' => 25, 'unit' => 'paket', 'unit_price' => 25000, 'note' => 'Snack & air mineral'],
            ['name' => 'Dokumentasi & Desain E-Sertifikat', 'qty' => 1, 'unit' => 'paket', 'unit_price' => 350000, 'note' => 'Template digital & siaran'],
        ]);

        return [
            'preset_code' => 'ev_72b43c',
            'title' => 'Webinar Nasional Literasi Digital & Etika AI Pelajar',
            'activity_type' => 'workshop',
            'organizer_name' => 'OSIS & MPK Bidang TIK',
            'organizer_unit' => 'Organisasi Siswa Intra Madrasah',
            'committee_members' => [
                ['role' => 'Ketua Pelaksana', 'name' => 'Ahmad Fauzan (XI IPA 1)'],
                ['role' => 'Sekretaris & Notulensi', 'name' => 'Nadia Putri (XI IPS 2)'],
                ['role' => 'Koordinator Teknis Siaran', 'name' => 'Rizky Pratama (X-3)'],
            ],
            'start_date' => $startDate,
            'end_date' => $startDate,
            'location' => 'Aula Madrasah & Siaran Daring',
            'purpose' => 'Membekali peserta didik dengan keterampilan literasi digital, verifikasi informasi, dan pemanfaatan AI secara etis.',
            'budget_items' => $budget['items'],
            'total_requested_amount' => $budget['total'],
        ];
    }

    /**
     * Validate and recompute itemized RAB totals strictly on the server.
     *
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return array{items: list<array{name:string, qty:int, unit:string, unit_price:int, subtotal:int, note:?string}>, total:int}
     */
    public function normalizeBudgetItems(array $rawItems): array
    {
        if ($rawItems === []) {
            throw new InvalidArgumentException('Rincian RAB minimal berisi 1 item.');
        }

        $normalized = [];
        $total = 0;

        foreach ($rawItems as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? 'paket'));
            $qty = is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : -1;
            $unitPrice = is_numeric($row['unit_price'] ?? null) ? (int) $row['unit_price'] : -1;
            $note = isset($row['note']) && trim((string) $row['note']) !== '' ? trim((string) $row['note']) : null;

            if ($name === '') {
                throw new InvalidArgumentException('Nama pos anggaran pada baris '.($index + 1).' wajib diisi.');
            }
            if ($qty < 1) {
                throw new InvalidArgumentException('Kuantitas pada pos "'.$name.'" harus minimal 1.');
            }
            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Harga satuan pada pos "'.$name.'" tidak boleh negatif.');
            }

            $subtotal = $qty * $unitPrice;
            $total += $subtotal;
            $normalized[] = [
                'name' => $name,
                'qty' => $qty,
                'unit' => $unit !== '' ? $unit : 'paket',
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'note' => $note,
            ];
        }

        return [
            'items' => $normalized,
            'total' => $total,
        ];
    }

    /**
     * Submit a new teacher work proposal or independent organization/student proposal.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createProposal(
        User $actor,
        string $mode,
        array $payload,
        ?UploadedFile $attachment = null,
    ): ActivityProposal {
        if ($mode === ActivityProposal::MODE_TEACHER_WORK) {
            $this->assertCanSubmitTeacherWork($actor);
        } elseif ($mode === ActivityProposal::MODE_INDEPENDENT) {
            $this->assertCanSubmitIndependent($actor);
        } else {
            throw new InvalidArgumentException('Mode proposal tidak dikenali.');
        }

        $schoolId = (int) $actor->school_id;
        $budget = $this->normalizeBudgetItems((array) ($payload['budget_items'] ?? []));
        $committee = $this->normalizeCommittee((array) ($payload['committee_members'] ?? []));
        $idempotencyKey = isset($payload['idempotency_key']) && trim((string) $payload['idempotency_key']) !== ''
            ? trim((string) $payload['idempotency_key'])
            : null;

        return DB::transaction(function () use ($actor, $schoolId, $mode, $payload, $budget, $committee, $attachment, $idempotencyKey): ActivityProposal {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                $existing = ActivityProposal::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $prefix = $mode === ActivityProposal::MODE_TEACHER_WORK ? 'PRG' : 'PRM';
            $year = (int) $this->schoolScope->localNow($schoolId)->format('Y');
            $proposalCode = $this->ticketService->generateTicketCode($prefix, $year);

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'proposal',
                'prefix' => 'PRP',
                'applicant_user_id' => (int) $actor->id,
                'applicant_snapshot' => [
                    'full_name' => (string) $payload['organizer_name'],
                    'organizer_unit' => $payload['organizer_unit'] ?? null,
                    'proposal_mode' => $mode,
                    'title' => (string) $payload['title'],
                ],
                'internal_status' => ServiceWorkflowMap::STATUS_SUBMITTED,
                'public_summary' => 'Proposal kegiatan diajukan dan menunggu telaah reviewer.',
                'idempotency_key' => $idempotencyKey !== null ? 'tkt-'.$idempotencyKey : null,
            ], $actor);

            $attachmentDocId = null;
            if ($attachment !== null) {
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $attachment,
                    documentKind: 'proposal_lpj',
                    ticket: $ticket,
                    uploader: $actor,
                );
                $attachmentDocId = (int) $doc->id;
            }

            $nowIso = CarbonImmutable::now()->toIso8601String();
            $proposal = ActivityProposal::query()->create([
                'school_id' => $schoolId,
                'service_ticket_id' => (int) $ticket->id,
                'proposal_mode' => $mode,
                'proposal_code' => $proposalCode,
                'title' => trim((string) $payload['title']),
                'activity_type' => (string) $payload['activity_type'],
                'organizer_name' => trim((string) $payload['organizer_name']),
                'organizer_unit' => isset($payload['organizer_unit']) && trim((string) $payload['organizer_unit']) !== ''
                    ? trim((string) $payload['organizer_unit'])
                    : null,
                'committee_members' => $committee,
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'] ?? $payload['start_date'],
                'location' => isset($payload['location']) && trim((string) $payload['location']) !== ''
                    ? trim((string) $payload['location'])
                    : null,
                'purpose' => trim((string) $payload['purpose']),
                'budget_items' => $budget['items'],
                'total_requested_amount' => $budget['total'],
                'revision_number' => 1,
                'revision_history' => [[
                    'version' => 1,
                    'event' => 'submitted',
                    'note' => 'Proposal awal dikirim untuk ditelaah.',
                    'total_requested_amount' => $budget['total'],
                    'actor_name' => (string) $actor->name,
                    'recorded_at' => $nowIso,
                ]],
                'attachment_document_id' => $attachmentDocId,
                'status' => ActivityProposal::STATUS_SUBMITTED,
                'submitted_by_user_id' => (int) $actor->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->auditLogger->log(
                eventType: 'integration.proposal.submitted',
                targetType: 'ActivityProposal',
                targetId: (string) $proposal->id,
                newValues: [
                    'proposal_code' => $proposal->proposal_code,
                    'proposal_mode' => $proposal->proposal_mode,
                    'total_requested_amount' => $proposal->total_requested_amount,
                ],
                schoolId: $schoolId,
                actor: $actor,
            );

            return $proposal;
        });
    }

    /**
     * Revise and resubmit a proposal after reviewer requested revision.
     *
     * @param  array<string, mixed>  $payload
     */
    public function resubmitProposal(
        User $actor,
        ActivityProposal $proposal,
        array $payload,
        ?UploadedFile $attachment = null,
    ): ActivityProposal {
        $this->assertSameSchool($actor, $proposal);
        $this->assertOwnerOrReviewer($actor, $proposal);

        if ($proposal->status !== ActivityProposal::STATUS_NEEDS_REVISION) {
            throw new InvalidArgumentException('Hanya proposal dengan status perlu revisi yang dapat diperbarui.');
        }

        $budget = $this->normalizeBudgetItems((array) ($payload['budget_items'] ?? []));
        $committee = $this->normalizeCommittee((array) ($payload['committee_members'] ?? []));

        return DB::transaction(function () use ($actor, $proposal, $payload, $budget, $committee, $attachment): ActivityProposal {
            $locked = ActivityProposal::query()->whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $nextRevision = (int) $locked->revision_number + 1;
            $history = is_array($locked->revision_history) ? $locked->revision_history : [];
            $history[] = [
                'version' => $nextRevision,
                'event' => 'resubmitted',
                'note' => trim((string) ($payload['revision_note'] ?? 'Revisi proposal dikirim ulang.')),
                'total_requested_amount' => $budget['total'],
                'actor_name' => (string) $actor->name,
                'recorded_at' => CarbonImmutable::now()->toIso8601String(),
            ];

            $attachmentDocId = $locked->attachment_document_id;
            if ($attachment !== null) {
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: (int) $locked->school_id,
                    file: $attachment,
                    documentKind: 'proposal_lpj',
                    uploader: $actor,
                );
                $attachmentDocId = (int) $doc->id;
                if ($locked->service_ticket_id !== null) {
                    $this->documentService->attachDocumentsToTicket(
                        (int) $locked->school_id,
                        [$attachmentDocId],
                        (int) $locked->service_ticket_id,
                    );
                }
            }

            $locked->update([
                'title' => trim((string) $payload['title']),
                'activity_type' => (string) $payload['activity_type'],
                'organizer_name' => trim((string) $payload['organizer_name']),
                'organizer_unit' => isset($payload['organizer_unit']) && trim((string) $payload['organizer_unit']) !== ''
                    ? trim((string) $payload['organizer_unit'])
                    : null,
                'committee_members' => $committee,
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'] ?? $payload['start_date'],
                'location' => isset($payload['location']) && trim((string) $payload['location']) !== ''
                    ? trim((string) $payload['location'])
                    : null,
                'purpose' => trim((string) $payload['purpose']),
                'budget_items' => $budget['items'],
                'total_requested_amount' => $budget['total'],
                'revision_number' => $nextRevision,
                'revision_history' => $history,
                'attachment_document_id' => $attachmentDocId,
                'status' => ActivityProposal::STATUS_SUBMITTED,
            ]);

            if ($locked->service_ticket_id !== null) {
                $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: (int) $locked->school_id,
                    toPublicStatus: ServiceWorkflowMap::STATUS_SUBMITTED,
                    actor: $actor,
                    publicSummary: 'Revisi proposal (v'.$nextRevision.') dikirim ulang untuk ditelaah.',
                );
            }

            $this->auditLogger->log(
                eventType: 'integration.proposal.resubmitted',
                targetType: 'ActivityProposal',
                targetId: (string) $locked->id,
                newValues: [
                    'revision_number' => $nextRevision,
                    'total_requested_amount' => $budget['total'],
                ],
                schoolId: (int) $locked->school_id,
                actor: $actor,
            );

            return $locked->fresh();
        });
    }

    /**
     * Record a reviewer workflow decision (`in_review`, `needs_revision`, `approved`, `rejected`, `closed`).
     */
    public function reviewProposal(
        User $actor,
        ActivityProposal $proposal,
        string $decision,
        ?string $note = null,
    ): ActivityProposal {
        $this->assertSameSchool($actor, $proposal);
        $this->assertCanReview($actor);

        $cleanNote = $note !== null && trim($note) !== '' ? trim($note) : null;

        return DB::transaction(function () use ($actor, $proposal, $decision, $cleanNote): ActivityProposal {
            $locked = ActivityProposal::query()->whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $current = (string) $locked->status;

            $allowed = match ($decision) {
                ActivityProposal::STATUS_IN_REVIEW => [ActivityProposal::STATUS_SUBMITTED],
                ActivityProposal::STATUS_NEEDS_REVISION => [ActivityProposal::STATUS_SUBMITTED, ActivityProposal::STATUS_IN_REVIEW],
                ActivityProposal::STATUS_APPROVED => [ActivityProposal::STATUS_SUBMITTED, ActivityProposal::STATUS_IN_REVIEW],
                ActivityProposal::STATUS_REJECTED => [ActivityProposal::STATUS_SUBMITTED, ActivityProposal::STATUS_IN_REVIEW, ActivityProposal::STATUS_NEEDS_REVISION],
                ActivityProposal::STATUS_CLOSED => [ActivityProposal::STATUS_LPJ_SUBMITTED],
                default => [],
            };

            if (! in_array($current, $allowed, true)) {
                $message = $decision === ActivityProposal::STATUS_CLOSED && $current === ActivityProposal::STATUS_APPROVED
                    ? 'Penutupan proposal membutuhkan unggahan bukti LPJ terlebih dahulu.'
                    : 'Transisi keputusan proposal dari status '.$current.' ke '.$decision.' tidak diizinkan.';
                throw new InvalidArgumentException($message);
            }

            if (in_array($decision, [ActivityProposal::STATUS_NEEDS_REVISION, ActivityProposal::STATUS_REJECTED], true) && $cleanNote === null) {
                throw new InvalidArgumentException('Catatan reviewer wajib diisi saat meminta revisi atau menolak proposal.');
            }

            $history = is_array($locked->revision_history) ? $locked->revision_history : [];
            $history[] = [
                'version' => (int) $locked->revision_number,
                'event' => $decision,
                'note' => $cleanNote ?? (self::STATUS_LABELS[$decision] ?? $decision),
                'total_requested_amount' => (int) $locked->total_requested_amount,
                'actor_name' => (string) $actor->name,
                'recorded_at' => CarbonImmutable::now()->toIso8601String(),
            ];

            $updates = [
                'status' => $decision,
                'review_note' => $cleanNote,
                'reviewed_by_user_id' => (int) $actor->id,
                'reviewed_at' => now(),
                'revision_history' => $history,
            ];

            if ($decision === ActivityProposal::STATUS_REJECTED) {
                $updates['rejection_reason'] = $cleanNote;
            }
            if ($decision === ActivityProposal::STATUS_CLOSED) {
                $updates['closed_at'] = now();
            }

            $locked->update($updates);

            if ($locked->service_ticket_id !== null) {
                $ticketTarget = match ($decision) {
                    ActivityProposal::STATUS_IN_REVIEW => ServiceWorkflowMap::STATUS_IN_REVIEW,
                    ActivityProposal::STATUS_NEEDS_REVISION => ServiceWorkflowMap::STATUS_NEEDS_REVISION,
                    ActivityProposal::STATUS_APPROVED => ServiceWorkflowMap::STATUS_APPROVED,
                    ActivityProposal::STATUS_REJECTED => ServiceWorkflowMap::STATUS_REJECTED,
                    ActivityProposal::STATUS_CLOSED => ServiceWorkflowMap::STATUS_COMPLETED,
                    default => ServiceWorkflowMap::STATUS_IN_REVIEW,
                };

                $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: (int) $locked->school_id,
                    toPublicStatus: $ticketTarget,
                    reason: $cleanNote,
                    actor: $actor,
                    publicSummary: self::STATUS_LABELS[$decision] ?? null,
                );
            }

            $this->auditLogger->log(
                eventType: 'integration.proposal.reviewed',
                targetType: 'ActivityProposal',
                targetId: (string) $locked->id,
                oldValues: ['status' => $current],
                newValues: ['status' => $decision, 'has_note' => $cleanNote !== null],
                schoolId: (int) $locked->school_id,
                actor: $actor,
            );

            return $locked->fresh();
        });
    }

    /**
     * Upload realization summary and LPJ proof attachments for an approved proposal (M4-INT-02).
     *
     * @param  array{lpj_realization_amount:int, lpj_summary:string}  $payload
     * @param  list<UploadedFile>  $proofFiles
     */
    public function submitLpj(
        User $actor,
        ActivityProposal $proposal,
        array $payload,
        array $proofFiles,
    ): ActivityProposal {
        $this->assertSameSchool($actor, $proposal);
        $this->assertOwnerOrReviewer($actor, $proposal);

        if (! in_array($proposal->status, [ActivityProposal::STATUS_APPROVED, ActivityProposal::STATUS_LPJ_SUBMITTED], true)) {
            throw new InvalidArgumentException('LPJ hanya dapat diunggah untuk proposal yang telah disetujui dan belum ditutup.');
        }

        $realizationAmount = (int) ($payload['lpj_realization_amount'] ?? -1);
        if ($realizationAmount < 0) {
            throw new InvalidArgumentException('Nominal realisasi LPJ tidak boleh negatif.');
        }

        $summary = trim((string) ($payload['lpj_summary'] ?? ''));
        if ($summary === '') {
            throw new InvalidArgumentException('Ringkasan realisasi LPJ wajib diisi.');
        }

        return DB::transaction(function () use ($actor, $proposal, $realizationAmount, $summary, $proofFiles): ActivityProposal {
            $locked = ActivityProposal::query()->whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $docIds = is_array($locked->lpj_document_ids) ? $locked->lpj_document_ids : [];
            $newDocIds = [];
            foreach ($proofFiles as $file) {
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: (int) $locked->school_id,
                    file: $file,
                    documentKind: 'proposal_lpj',
                    uploader: $actor,
                );
                $docIds[] = (int) $doc->id;
                $newDocIds[] = (int) $doc->id;
            }

            if ($newDocIds !== [] && $locked->service_ticket_id !== null) {
                $this->documentService->attachDocumentsToTicket(
                    (int) $locked->school_id,
                    $newDocIds,
                    (int) $locked->service_ticket_id,
                );
            }

            if ($docIds === []) {
                throw new InvalidArgumentException('Minimal 1 berkas bukti realisasi LPJ wajib diunggah.');
            }

            $history = is_array($locked->revision_history) ? $locked->revision_history : [];
            $history[] = [
                'version' => (int) $locked->revision_number,
                'event' => 'lpj_submitted',
                'note' => 'LPJ diunggah dengan realisasi Rp '.number_format($realizationAmount, 0, ',', '.').'.',
                'total_requested_amount' => (int) $locked->total_requested_amount,
                'lpj_realization_amount' => $realizationAmount,
                'actor_name' => (string) $actor->name,
                'recorded_at' => CarbonImmutable::now()->toIso8601String(),
            ];

            $locked->update([
                'status' => ActivityProposal::STATUS_LPJ_SUBMITTED,
                'lpj_realization_amount' => $realizationAmount,
                'lpj_summary' => $summary,
                'lpj_document_ids' => array_values(array_unique($docIds)),
                'lpj_submitted_at' => now(),
                'revision_history' => $history,
            ]);

            $this->auditLogger->log(
                eventType: 'integration.proposal.lpj_submitted',
                targetType: 'ActivityProposal',
                targetId: (string) $locked->id,
                newValues: [
                    'lpj_realization_amount' => $realizationAmount,
                    'proof_count' => count($docIds),
                ],
                schoolId: (int) $locked->school_id,
                actor: $actor,
            );

            return $locked->fresh();
        });
    }

    /**
     * Authorize and resolve a private attachment belonging to a proposal.
     *
     * @return array{absolute_path:string, original_name:string, mime_type:string}
     */
    public function resolveProposalDocument(User $actor, ActivityProposal $proposal, int $documentId): array
    {
        $this->assertSameSchool($actor, $proposal);
        $this->assertOwnerOrReviewer($actor, $proposal);

        $allowedIds = array_values(array_filter([
            $proposal->attachment_document_id ? (int) $proposal->attachment_document_id : null,
            ...array_map('intval', is_array($proposal->lpj_document_ids) ? $proposal->lpj_document_ids : []),
        ]));

        abort_unless(in_array($documentId, $allowedIds, true), 404, 'Dokumen proposal tidak ditemukan.');

        $resolved = $this->documentService->resolveDownloadByDocumentId((int) $proposal->school_id, $documentId);
        abort_if($resolved === null, 404, 'Berkas dokumen tidak tersedia di penyimpanan.');

        return $resolved;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForTeacher(User $actor): array
    {
        $this->assertCanSubmitTeacherWork($actor);

        return ActivityProposal::query()
            ->where('school_id', (int) $actor->school_id)
            ->where('proposal_mode', ActivityProposal::MODE_TEACHER_WORK)
            ->when(! $this->canReview($actor), fn ($q) => $q->where('submitted_by_user_id', (int) $actor->id))
            ->with(['submittedBy:id,name', 'reviewedBy:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (ActivityProposal $p): array => $this->serializeProposal($p))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForIndependentPortal(User $actor): array
    {
        $this->assertCanSubmitIndependent($actor);

        return ActivityProposal::query()
            ->where('school_id', (int) $actor->school_id)
            ->where('proposal_mode', ActivityProposal::MODE_INDEPENDENT)
            ->when(! $this->canReview($actor), fn ($q) => $q->where('submitted_by_user_id', (int) $actor->id))
            ->with(['submittedBy:id,name', 'reviewedBy:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (ActivityProposal $p): array => $this->serializeProposal($p))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listReviewQueue(User $actor, ?string $mode = null): array
    {
        $this->assertCanReview($actor);

        return ActivityProposal::query()
            ->where('school_id', (int) $actor->school_id)
            ->when(in_array($mode, [ActivityProposal::MODE_TEACHER_WORK, ActivityProposal::MODE_INDEPENDENT], true), fn ($q) => $q->where('proposal_mode', $mode))
            ->with(['submittedBy:id,name', 'reviewedBy:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (ActivityProposal $p): array => $this->serializeProposal($p))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeProposal(ActivityProposal $proposal): array
    {
        return [
            'id' => (int) $proposal->id,
            'proposal_code' => (string) $proposal->proposal_code,
            'proposal_mode' => (string) $proposal->proposal_mode,
            'proposal_mode_label' => $proposal->proposal_mode === ActivityProposal::MODE_TEACHER_WORK
                ? 'Penugasan Kerja Guru'
                : 'Mandiri (Siswa / Organisasi)',
            'title' => (string) $proposal->title,
            'activity_type' => (string) $proposal->activity_type,
            'activity_type_label' => self::ACTIVITY_TYPES[$proposal->activity_type] ?? $proposal->activity_type,
            'organizer_name' => (string) $proposal->organizer_name,
            'organizer_unit' => $proposal->organizer_unit,
            'committee_members' => is_array($proposal->committee_members) ? $proposal->committee_members : [],
            'start_date' => $proposal->start_date?->toDateString(),
            'end_date' => $proposal->end_date?->toDateString(),
            'location' => $proposal->location,
            'purpose' => (string) $proposal->purpose,
            'budget_items' => is_array($proposal->budget_items) ? $proposal->budget_items : [],
            'total_requested_amount' => (int) $proposal->total_requested_amount,
            'revision_number' => (int) $proposal->revision_number,
            'revision_history' => is_array($proposal->revision_history) ? $proposal->revision_history : [],
            'attachment_document_id' => $proposal->attachment_document_id ? (int) $proposal->attachment_document_id : null,
            'status' => (string) $proposal->status,
            'status_label' => self::STATUS_LABELS[$proposal->status] ?? $proposal->status,
            'review_note' => $proposal->review_note,
            'rejection_reason' => $proposal->rejection_reason,
            'submitted_by_user_id' => $proposal->submitted_by_user_id ? (int) $proposal->submitted_by_user_id : null,
            'submitted_by_name' => $proposal->submittedBy?->name,
            'reviewed_by_name' => $proposal->reviewedBy?->name,
            'reviewed_at' => $proposal->reviewed_at?->toIso8601String(),
            'lpj_realization_amount' => $proposal->lpj_realization_amount !== null ? (int) $proposal->lpj_realization_amount : null,
            'lpj_summary' => $proposal->lpj_summary,
            'lpj_document_ids' => array_map('intval', is_array($proposal->lpj_document_ids) ? $proposal->lpj_document_ids : []),
            'lpj_submitted_at' => $proposal->lpj_submitted_at?->toIso8601String(),
            'closed_at' => $proposal->closed_at?->toIso8601String(),
            'created_at' => $proposal->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     * @return list<array{role:string, name:string}>
     */
    private function normalizeCommittee(array $members): array
    {
        $clean = [];
        foreach ($members as $member) {
            $name = trim((string) ($member['name'] ?? ''));
            $role = trim((string) ($member['role'] ?? 'Anggota'));
            if ($name === '') {
                continue;
            }
            $clean[] = [
                'role' => $role !== '' ? $role : 'Anggota',
                'name' => $name,
            ];
        }

        return $clean;
    }

    private function assertSameSchool(User $actor, ActivityProposal $proposal): void
    {
        abort_unless($this->accessScope->belongsToSchool($actor, (int) $proposal->school_id), 403, 'Akses lintas sekolah tidak diizinkan.');
    }

    private function assertOwnerOrReviewer(User $actor, ActivityProposal $proposal): void
    {
        if ($this->canReview($actor)) {
            return;
        }

        abort_unless((int) $proposal->submitted_by_user_id === (int) $actor->id, 403, 'Anda hanya dapat mengakses proposal milik Anda sendiri.');
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasAnyRole(?User $user, array $roles): bool
    {
        $roleName = $user?->role?->name;

        return is_string($roleName) && in_array($roleName, $roles, true);
    }
}
