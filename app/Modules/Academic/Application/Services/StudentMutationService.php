<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Domain\Enums\EnrollmentStatusEnum;
use App\Modules\Academic\Domain\Enums\StudentStatusEnum;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLifecycleEvent;
use App\Modules\Academic\Infrastructure\Models\StudentMutation;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\NotificationOutboxService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Shared\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Handles incoming and outgoing student mutations (M4-LIF-02) while preserving historical
 * enrollment/academic records (SS-D-004) and emitting verifiable ServiceDocuments.
 */
class StudentMutationService
{
    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly EnrollmentService $enrollmentService,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly NotificationOutboxService $outbox,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $attachments
     * @return array{mutation: StudentMutation, ticket: array<string, mixed>, plain_verification_secret: string}
     */
    public function submitMutation(
        int $schoolId,
        array $data,
        array $attachments = [],
        ?User $actor = null,
    ): array {
        $activeTerm = Term::query()
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        if ($activeTerm === null) {
            throw ValidationException::withMessages([
                'direction' => 'Belum ada semester aktif untuk pengajuan mutasi saat ini.',
            ]);
        }

        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            $existing = StudentMutation::query()
                ->where('school_id', $schoolId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                $ticket = $existing->service_ticket_id
                    ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                    : [];

                return [
                    'mutation' => $existing,
                    'ticket' => $ticket ?? [],
                    'plain_verification_secret' => '',
                ];
            }
        }

        $direction = (string) ($data['direction'] ?? 'outgoing');
        if (! in_array($direction, ['incoming', 'outgoing'], true)) {
            throw ValidationException::withMessages(['direction' => 'Jenis mutasi harus masuk atau keluar.']);
        }

        $student = null;
        if ($direction === 'outgoing') {
            $studentId = (int) ($data['student_id'] ?? 0);
            /** @var Student|null $student */
            $student = Student::query()->where('school_id', $schoolId)->whereKey($studentId)->first();
            if ($student === null || $student->status !== StudentStatusEnum::ACTIVE->value) {
                throw ValidationException::withMessages([
                    'student_id' => 'Siswa aktif untuk mutasi keluar tidak ditemukan.',
                ]);
            }

            $isPrivilegedStaff = $actor !== null
                && (int) $actor->school_id === $schoolId
                && $actor->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PTSP->value]);

            if (! $isPrivilegedStaff && ($actor === null || ! $this->accessScope->canAccessStudentScope($actor, $schoolId, (int) $student->id))) {
                throw ValidationException::withMessages([
                    'student_id' => 'Anda hanya berhak mengajukan mutasi keluar untuk siswa perwalian/anak Anda.',
                ]);
            }
        }

        return DB::transaction(function () use ($schoolId, $activeTerm, $data, $direction, $student, $attachments, $actor, $idempotencyKey): array {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                $existing = StudentMutation::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing !== null) {
                    $ticket = $existing->service_ticket_id
                        ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                        : [];

                    return [
                        'mutation' => $existing,
                        'ticket' => $ticket ?? [],
                        'plain_verification_secret' => '',
                    ];
                }
            }

            $plainSecret = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $mutationCode = 'MTS-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(6));
            $studentName = $student !== null ? $student->name : trim((string) ($data['student_name'] ?? ''));

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'mutasi',
                'prefix' => 'MTS',
                'applicant_user_id' => $actor?->id,
                'subject_student_id' => $student?->id,
                'applicant_snapshot' => [
                    'mutation_code' => $mutationCode,
                    'direction' => $direction,
                    'student_name' => $studentName,
                    'contact_name' => $data['applicant_name'] ?? ($actor?->name ?? 'Pemohon Mutasi'),
                ],
                'verification_secret' => $plainSecret,
                'internal_status' => 'submitted',
                'public_summary' => sprintf(
                    'Permohonan mutasi %s atas nama %s diterima dan menunggu review TU.',
                    $direction === 'incoming' ? 'masuk' : 'keluar',
                    $studentName
                ),
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

            $mutation = StudentMutation::query()->create([
                'school_id' => $schoolId,
                'term_id' => $activeTerm->id,
                'service_ticket_id' => (int) $ticket->id,
                'direction' => $direction,
                'student_id' => $student?->id,
                'target_class_group_id' => isset($data['target_class_group_id']) ? (int) $data['target_class_group_id'] : null,
                'submitted_by_user_id' => $actor?->id,
                'mutation_code' => $mutationCode,
                'student_name' => $studentName,
                'nis' => $student?->nis ?? (isset($data['nis']) ? trim((string) $data['nis']) ?: null : null),
                'nisn' => $student?->nisn ?? (isset($data['nisn']) ? trim((string) $data['nisn']) ?: null : null),
                'gender' => $student?->gender ?? (string) ($data['gender'] ?? 'L'),
                'birth_date' => $student?->birth_date ?? ($data['birth_date'] ?? null),
                'origin_school' => $direction === 'incoming' ? trim((string) ($data['origin_school'] ?? '')) ?: null : null,
                'destination_school' => $direction === 'outgoing' ? trim((string) ($data['destination_school'] ?? '')) ?: null : null,
                'reason' => trim((string) $data['reason']),
                'applicant_name' => isset($data['applicant_name']) ? trim((string) $data['applicant_name']) ?: null : $actor?->name,
                'applicant_phone' => isset($data['applicant_phone']) ? trim((string) $data['applicant_phone']) ?: null : null,
                'attachment_document_ids' => $attachmentIds,
                'status' => 'submitted',
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->log(
                eventType: 'academic.mutation.submitted',
                targetType: 'StudentMutation',
                targetId: (string) $mutation->id,
                newValues: ['mutation_code' => $mutationCode, 'direction' => $direction, 'student_id' => $student?->id],
                actor: $actor,
                schoolId: $schoolId,
            );

            return [
                'mutation' => $mutation->refresh(),
                'ticket' => $this->ticketService->getTicketSnapshotById($schoolId, (int) $ticket->id) ?? [],
                'plain_verification_secret' => $plainSecret,
            ];
        });
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function resubmitRevision(
        int $schoolId,
        StudentMutation $mutation,
        string $reason,
        ?string $schoolName = null,
        array $attachments = [],
        ?User $actor = null,
    ): StudentMutation {
        abort_unless((int) $mutation->school_id === $schoolId, 404);

        if ($mutation->status !== 'needs_revision') {
            throw ValidationException::withMessages(['status' => 'Permohonan mutasi ini tidak sedang dalam status perbaikan.']);
        }

        return DB::transaction(function () use ($schoolId, $mutation, $reason, $schoolName, $attachments, $actor): StudentMutation {
            /** @var StudentMutation $locked */
            $locked = StudentMutation::query()->where('school_id', $schoolId)->whereKey($mutation->id)->lockForUpdate()->firstOrFail();

            $attachmentIds = is_array($locked->attachment_document_ids) ? $locked->attachment_document_ids : [];
            $ticket = $locked->service_ticket_id
                ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id)
                : null;

            foreach ($attachments as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'attachment',
                    ticket: null,
                    uploader: $actor,
                );
                if ($locked->service_ticket_id !== null) {
                    $this->documentService->attachDocumentsToTicket($schoolId, [(int) $doc->id], (int) $locked->service_ticket_id);
                }
                $attachmentIds[] = (int) $doc->id;
            }

            $updates = [
                'reason' => trim($reason),
                'attachment_document_ids' => $attachmentIds,
                'status' => 'submitted',
                'revision_note' => null,
            ];
            if ($schoolName !== null && trim($schoolName) !== '') {
                $field = $locked->direction === 'incoming' ? 'origin_school' : 'destination_school';
                $updates[$field] = trim($schoolName);
            }

            $locked->forceFill($updates)->save();

            if ($ticket !== null && ServiceWorkflowMap::canTransition((string) $ticket['public_status'], ServiceWorkflowMap::STATUS_SUBMITTED)) {
                $this->ticketService->transition(
                    ticketId: (int) $locked->service_ticket_id,
                    schoolId: $schoolId,
                    toPublicStatus: ServiceWorkflowMap::STATUS_SUBMITTED,
                    toInternalStatus: 'resubmitted',
                    reason: 'Pemohon mengirim perbaikan permohonan mutasi.',
                    actor: $actor,
                    expectedVersion: (int) $ticket['version'],
                    publicSummary: 'Perbaikan mutasi diterima dan menunggu review ulang.',
                );
            }

            return $locked->refresh();
        });
    }

    /**
     * Decide a mutation request (`approve`, `request_revision`, `reject`) safely without erasing enrollment history.
     *
     * @param  array<string, mixed>  $options
     */
    public function decide(
        User $actor,
        StudentMutation $mutation,
        string $decision,
        ?string $note = null,
        array $options = [],
    ): StudentMutation {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $mutation->school_id === $schoolId, 404);

        if (! in_array($decision, ['approve', 'request_revision', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan mutasi tidak valid.']);
        }

        $cleanNote = trim((string) $note);
        if (in_array($decision, ['request_revision', 'reject'], true) && $cleanNote === '') {
            throw ValidationException::withMessages(['decision_note' => 'Catatan atau alasan wajib diisi untuk perbaikan maupun penolakan.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $mutation, $decision, $cleanNote, $options): StudentMutation {
            /** @var StudentMutation $locked */
            $locked = StudentMutation::query()->where('school_id', $schoolId)->whereKey($mutation->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['submitted', 'in_review', 'needs_revision'], true)) {
                throw ValidationException::withMessages(['status' => 'Permohonan mutasi ini sudah memiliki keputusan akhir.']);
            }

            if ($decision !== 'approve') {
                $newStatus = $decision === 'request_revision' ? 'needs_revision' : 'rejected';
                $locked->forceFill([
                    'status' => $newStatus,
                    'decision_note' => $decision === 'reject' ? $cleanNote : $locked->decision_note,
                    'revision_note' => $decision === 'request_revision' ? $cleanNote : null,
                    'decided_by_user_id' => $actor->id,
                    'decided_at' => now(),
                ])->save();

                $this->syncTicketDecision($schoolId, $locked, $decision, $cleanNote, $actor);

                $this->audit->log(
                    eventType: "academic.mutation.{$decision}",
                    targetType: 'StudentMutation',
                    targetId: (string) $locked->id,
                    newValues: ['status' => $newStatus, 'note' => $cleanNote],
                    actor: $actor,
                    schoolId: $schoolId,
                );

                return $locked->refresh();
            }

            // Apply approved mutation effects safely while preserving all historical enrollments
            $school = $this->schoolScope->getSchoolDetails($schoolId);
            $effectiveDate = (string) ($options['effective_date'] ?? $this->schoolScope->localNow($schoolId)->toDateString());
            $targetClassGroupId = isset($options['target_class_group_id']) && (int) $options['target_class_group_id'] > 0
                ? (int) $options['target_class_group_id']
                : $locked->target_class_group_id;

            if ($locked->direction === 'outgoing') {
                /** @var Student $student */
                $student = Student::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($locked->student_id);
                $activeEnrollment = Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('status', EnrollmentStatusEnum::ACTIVE->value)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($activeEnrollment !== null) {
                    $activeEnrollment->forceFill(['status' => EnrollmentStatusEnum::TRANSFERRED->value])->save();
                }

                $student->forceFill(['status' => StudentStatusEnum::TRANSFERRED->value])->save();

                StudentLifecycleEvent::query()->create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'source_enrollment_id' => $activeEnrollment?->id,
                    'target_enrollment_id' => null,
                    'action' => 'transferred',
                    'effective_date' => $effectiveDate,
                    'notes' => sprintf('[%s] Mutasi keluar ke %s', $locked->mutation_code, $locked->destination_school ?: '-'),
                    'recorded_by' => $actor->id,
                ]);
            } else {
                $nis = trim((string) ($options['nis'] ?? $locked->nis ?? ''));
                if ($nis === '') {
                    $nis = 'M'.$this->schoolScope->localNow($schoolId)->format('ym').str_pad((string) $locked->id, 4, '0', STR_PAD_LEFT);
                }

                $student = Student::query()->where('school_id', $schoolId)->where('nis', $nis)->first();
                if ($student === null) {
                    $student = Student::query()->create([
                        'school_id' => $schoolId,
                        'nis' => $nis,
                        'nisn' => $locked->nisn,
                        'name' => $locked->student_name,
                        'gender' => in_array($locked->gender, ['L', 'P'], true) ? $locked->gender : 'L',
                        'birth_date' => $locked->birth_date,
                        'parent_name' => $locked->applicant_name,
                        'parent_phone' => $locked->applicant_phone,
                        'status' => StudentStatusEnum::ACTIVE->value,
                    ]);
                }

                $targetEnrollment = null;
                if ($targetClassGroupId !== null && $locked->term_id !== null) {
                    /** @var ClassGroup|null $classGroup */
                    $classGroup = ClassGroup::query()->where('school_id', $schoolId)->find($targetClassGroupId);
                    if ($classGroup !== null) {
                        $targetEnrollment = $this->enrollmentService->enrollStudent(
                            termId: (int) $locked->term_id,
                            classGroupId: (int) $classGroup->id,
                            studentId: (int) $student->id,
                            actorId: (int) $actor->id,
                        );
                    }
                }

                $locked->student_id = (int) $student->id;
                $locked->nis = $student->nis;
                $locked->target_class_group_id = $targetClassGroupId;

                StudentLifecycleEvent::query()->create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'source_enrollment_id' => null,
                    'target_enrollment_id' => $targetEnrollment?->id,
                    'action' => 'mutated_in',
                    'effective_date' => $effectiveDate,
                    'notes' => sprintf('[%s] Mutasi masuk dari %s', $locked->mutation_code, $locked->origin_school ?: '-'),
                    'recorded_by' => $actor->id,
                ]);
            }

            $locked->forceFill([
                'status' => 'approved',
                'decision_note' => $cleanNote !== '' ? $cleanNote : 'Permohonan mutasi disetujui.',
                'revision_note' => null,
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
            ])->save();

            $docPrefix = $locked->direction === 'outgoing' ? 'MTS-OUT' : 'MTS-IN';
            $signatoryName = (string) ($options['signatory_name'] ?? $actor->name);
            $signatoryTitle = (string) ($options['signatory_title'] ?? 'Kepala Sekolah / Tata Usaha');
            $verificationToken = Str::random(40);

            $provisionalPdf = Pdf::loadView('academic.mutation-letter', [
                'school' => $school,
                'mutation' => $locked,
                'documentNumber' => $locked->mutation_code,
                'signatoryName' => $signatoryName,
                'signatoryTitle' => $signatoryTitle,
                'verificationToken' => $verificationToken,
            ])->setPaper('a4')->output();

            $doc = $this->documentService->issueGeneratedDocument(
                schoolId: $schoolId,
                bytes: $provisionalPdf,
                payload: [
                    'document_kind' => 'letter',
                    'number_prefix' => $docPrefix,
                    'template_key' => 'student_mutation_letter',
                    'template_version' => 1,
                    'verification_token' => $verificationToken,
                    'original_name' => sprintf('surat-mutasi-%s.pdf', $locked->mutation_code),
                    'signatory_name' => $signatoryName,
                    'signatory_title' => $signatoryTitle,
                    'signature_mode' => 'image',
                    'service_ticket_id' => $locked->service_ticket_id,
                ],
                actor: $actor,
            );

            $locked->forceFill(['official_letter_document_id' => (int) $doc->id])->save();

            $this->syncTicketDecision($schoolId, $locked, 'approve', $cleanNote, $actor);

            $this->audit->log(
                eventType: 'academic.mutation.approved',
                targetType: 'StudentMutation',
                targetId: (string) $locked->id,
                newValues: ['direction' => $locked->direction, 'student_id' => $locked->student_id, 'document_id' => $doc->id],
                actor: $actor,
                schoolId: $schoolId,
            );

            if ($locked->submitted_by_user_id !== null) {
                $this->outbox->enqueue(
                    schoolId: $schoolId,
                    eventKey: 'mutasi.decided',
                    channel: 'in_app',
                    recipientType: 'user',
                    recipientId: (int) $locked->submitted_by_user_id,
                    templateKey: 'mutasi_decided',
                    payload: ['mutation_code' => $locked->mutation_code, 'status' => 'approved'],
                    idempotencyKey: sprintf('mutasi-app-%d', $locked->id),
                    dispatchJob: false,
                );
            }

            return $locked->refresh();
        });
    }

    /**
     * Revoke an approved mutation letter & safely restore prior student/enrollment status cleanly.
     */
    public function revokeApproval(User $actor, StudentMutation $mutation, string $reason): StudentMutation
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $mutation->school_id === $schoolId, 404);

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan pencabutan surat mutasi wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $mutation, $cleanReason): StudentMutation {
            /** @var StudentMutation $locked */
            $locked = StudentMutation::query()->where('school_id', $schoolId)->whereKey($mutation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Hanya mutasi yang disetujui yang dapat dicabut.']);
            }

            if ($locked->official_letter_document_id !== null) {
                $this->documentService->revokeDocument((int) $locked->official_letter_document_id, $schoolId, $cleanReason, $actor);
            }

            if ($locked->direction === 'outgoing' && $locked->student_id !== null) {
                $student = Student::query()->where('school_id', $schoolId)->find($locked->student_id);
                if ($student !== null && $student->status === StudentStatusEnum::TRANSFERRED->value) {
                    $student->forceFill(['status' => StudentStatusEnum::ACTIVE->value])->save();
                    $transferredEnrollment = Enrollment::query()
                        ->where('student_id', $student->id)
                        ->where('term_id', $locked->term_id)
                        ->where('status', EnrollmentStatusEnum::TRANSFERRED->value)
                        ->latest('id')
                        ->first();
                    $transferredEnrollment?->forceFill(['status' => EnrollmentStatusEnum::ACTIVE->value])->save();
                }
            }

            $locked->forceFill([
                'status' => 'cancelled',
                'revocation_reason' => $cleanReason,
                'revoked_at' => now(),
            ])->save();

            $this->audit->log(
                eventType: 'academic.mutation.revoked',
                targetType: 'StudentMutation',
                targetId: (string) $locked->id,
                newValues: ['revocation_reason' => $cleanReason],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    private function syncTicketDecision(
        int $schoolId,
        StudentMutation $mutation,
        string $decision,
        string $note,
        User $actor,
    ): void {
        if ($mutation->service_ticket_id === null) {
            return;
        }

        $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $mutation->service_ticket_id);
        if ($ticket === null) {
            return;
        }

        $version = (int) $ticket['version'];
        if ($decision === 'approve') {
            if ($ticket['public_status'] === ServiceWorkflowMap::STATUS_SUBMITTED) {
                $mid = $this->ticketService->transition(
                    ticketId: (int) $mutation->service_ticket_id,
                    schoolId: $schoolId,
                    toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                    toInternalStatus: 'in_review',
                    reason: null,
                    actor: $actor,
                    expectedVersion: $version,
                );
                $version = (int) $mid->version;
            }

            $this->ticketService->transition(
                ticketId: (int) $mutation->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: ServiceWorkflowMap::STATUS_COMPLETED,
                toInternalStatus: 'approved',
                reason: $note !== '' ? $note : null,
                actor: $actor,
                expectedVersion: $version,
                publicSummary: 'Surat keputusan/rekomendasi mutasi telah terbit.',
            );

            return;
        }

        $targetStatus = $decision === 'request_revision'
            ? ServiceWorkflowMap::STATUS_NEEDS_REVISION
            : ServiceWorkflowMap::STATUS_REJECTED;

        $this->ticketService->transition(
            ticketId: (int) $mutation->service_ticket_id,
            schoolId: $schoolId,
            toPublicStatus: $targetStatus,
            toInternalStatus: $decision,
            reason: $note,
            actor: $actor,
            expectedVersion: $version,
            publicSummary: $decision === 'request_revision'
                ? 'Permohonan mutasi dikembalikan untuk perbaikan data/berkas.'
                : 'Permohonan mutasi ditolak. Periksa catatan petugas.',
        );
    }
}
