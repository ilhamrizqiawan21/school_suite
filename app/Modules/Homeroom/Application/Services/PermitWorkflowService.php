<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Academic\Application\Services\AttendanceService;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\NotificationOutboxService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Modules\Homeroom\Domain\Enums\DispensationStatusEnum;
use App\Modules\Homeroom\Domain\Enums\DispensationTypeEnum;
use App\Modules\Homeroom\Infrastructure\Models\AttendanceDispensation;
use App\Modules\Homeroom\Infrastructure\Models\StudentPermitToken;
use App\Shared\Audit\AuditLogger;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Guardian/student leave & dispensation requests, one-time tokens, local time cutoff,
 * conflict rejection, homeroom review, and attendance impact/correction (M4-OPS-01, M4-OPS-02).
 */
class PermitWorkflowService
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
        private readonly AttendanceService $attendanceService,
        private readonly AccessScopeService $accessScope,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly NotificationOutboxService $outbox,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Issue a single-use token bound to a specific student and school (M4-OPS-01).
     *
     * @return array{token: string, token_prefix: string, expires_at: string, student_id: int}
     */
    public function issuePermitToken(User $actor, int $studentId, int $expiresInHours = 48): array
    {
        $schoolId = (int) $actor->school_id;
        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId);

        if ($student === null || $student['status'] !== 'active') {
            throw ValidationException::withMessages(['student_id' => 'Siswa aktif tidak ditemukan.']);
        }

        if ($actor->hasRole(RoleEnum::HOMEROOM->value) && ! $this->accessScope->canAccessStudentScope($actor, $schoolId, $studentId)) {
            abort(403, 'Wali kelas hanya dapat menerbitkan token untuk siswa kelas perwaliannya.');
        }

        $plainToken = 'IZT-'.strtoupper(Str::random(10));
        $prefix = substr($plainToken, 0, 8);
        $expiresAt = now()->addHours(max(1, min(168, $expiresInHours)));

        $record = StudentPermitToken::query()->create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'purpose' => 'permit_submission',
            'token_hash' => Hash::make($plainToken),
            'token_prefix' => $prefix,
            'expires_at' => $expiresAt,
            'issued_by_user_id' => $actor->id,
        ]);

        $this->audit->log(
            eventType: 'homeroom.permit_token.issued',
            targetType: 'StudentPermitToken',
            targetId: (string) $record->id,
            newValues: ['student_id' => $studentId, 'expires_at' => $expiresAt->toIso8601String()],
            actor: $actor,
            schoolId: $schoolId,
        );

        return [
            'token' => $plainToken,
            'token_prefix' => $prefix,
            'expires_at' => $expiresAt->toIso8601String(),
            'student_id' => $studentId,
        ];
    }

    /**
     * Submit a leave/dispensation request either by verified guardian/student or via single-use token (M4-OPS-01).
     *
     * @param  array<string, mixed>  $data
     * @return array{dispensation: AttendanceDispensation, ticket: array<string, mixed>, plain_verification_secret: string}
     */
    public function submitPermit(
        int $schoolId,
        array $data,
        ?UploadedFile $attachment = null,
        ?User $actor = null,
        bool $publicSubmission = false,
    ): array {
        $studentId = (int) $data['student_id'];
        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId);

        if ($student === null || $student['status'] !== 'active') {
            throw ValidationException::withMessages(['student_id' => 'Siswa aktif tidak ditemukan pada sekolah ini.']);
        }

        if (empty($student['term_id'])) {
            throw ValidationException::withMessages(['student_id' => 'Belum ada semester aktif pada sekolah ini.']);
        }

        $oneTimeToken = isset($data['one_time_token']) ? strtoupper(trim((string) $data['one_time_token'])) : '';

        if ($oneTimeToken === '' && ! $publicSubmission) {
            if ($actor === null || ! $this->accessScope->canAccessStudentScope($actor, $schoolId, $studentId)) {
                throw ValidationException::withMessages([
                    $actor !== null ? 'student_id' : 'one_time_token' => $actor !== null
                        ? 'Anda tidak memiliki akses pengajuan izin untuk siswa tersebut.'
                        : 'Anda harus masuk sebagai wali/siswa terverifikasi atau memasukkan token izin sekali pakai yang sah.',
                ]);
            }
        }

        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            $existing = AttendanceDispensation::query()
                ->where('school_id', $schoolId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing !== null) {
                $ticket = $existing->service_ticket_id
                    ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                    : [];

                return [
                    'dispensation' => $existing,
                    'ticket' => $ticket ?? [],
                    'plain_verification_secret' => '',
                ];
            }
        }

        $this->validateDatesAndCutoff($schoolId, $studentId, (string) $data['start_date'], (string) $data['end_date'], publicSubmission: $publicSubmission, hasToken: $oneTimeToken !== '');

        return DB::transaction(function () use ($schoolId, $studentId, $student, $data, $attachment, $actor, $oneTimeToken, $idempotencyKey, $publicSubmission): array {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                $existing = AttendanceDispensation::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing !== null) {
                    $ticket = $existing->service_ticket_id
                        ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                        : [];

                    return [
                        'dispensation' => $existing,
                        'ticket' => $ticket ?? [],
                        'plain_verification_secret' => '',
                    ];
                }
            }

            $tokenRecord = null;
            if ($publicSubmission) {
                $this->validateDatesAndCutoff($schoolId, $studentId, (string) $data['start_date'], (string) $data['end_date'], publicSubmission: true, hasToken: $oneTimeToken !== '');
            }
            if ($oneTimeToken !== '') {
                $tokenRecord = $this->lockAndValidateToken($schoolId, $studentId, $oneTimeToken);
            }

            $plainSecret = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'izin',
                'prefix' => 'IZIN',
                'applicant_user_id' => $actor?->id,
                'subject_student_id' => $studentId,
                'applicant_snapshot' => [
                    'student_id' => $studentId,
                    'student_name' => $student['name'],
                    'class_group_name' => $student['class_group_name'],
                    'contact_name' => $data['contact_name'] ?? ($actor?->name ?? 'Wali Siswa'),
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ],
                'verification_secret' => $plainSecret,
                'internal_status' => 'pending',
                'public_summary' => 'Permohonan izin siswa diterima dan menunggu verifikasi wali kelas.',
                'idempotency_key' => $idempotencyKey,
            ], $actor);

            $attachmentDocId = null;
            if ($attachment !== null) {
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $attachment,
                    documentKind: 'attachment',
                    ticket: $ticket,
                    uploader: $actor,
                );
                $attachmentDocId = (int) $doc->id;
            }

            $dispensation = AttendanceDispensation::query()->create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'term_id' => (int) $student['term_id'],
                'service_ticket_id' => (int) $ticket->id,
                'submitted_by_user_id' => $actor?->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'type' => DispensationTypeEnum::from((string) $data['type']),
                'title' => trim((string) $data['title']),
                'reason' => trim((string) $data['reason']),
                'contact_name' => isset($data['contact_name']) ? trim((string) $data['contact_name']) ?: null : ($actor?->name),
                'contact_phone' => isset($data['contact_phone']) ? trim((string) $data['contact_phone']) ?: null : ($actor?->phone),
                'signature_data' => trim((string) $data['signature_data']),
                'contact_relationship' => $data['contact_relationship'] ?? null,
                'attachment_document_id' => $attachmentDocId,
                'status' => DispensationStatusEnum::PENDING,
                'idempotency_key' => $idempotencyKey,
            ]);

            if ($tokenRecord !== null) {
                $tokenRecord->forceFill([
                    'used_at' => now(),
                    'used_by_dispensation_id' => $dispensation->id,
                ])->save();
            }

            $this->audit->log(
                eventType: 'homeroom.dispensation.submitted',
                targetType: 'attendance_dispensation',
                targetId: (string) $dispensation->id,
                newValues: [
                    'student_id' => $studentId,
                    'ticket_code' => $ticket->ticket_code,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'via_token' => $tokenRecord !== null,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            if ($actor !== null) {
                $this->outbox->enqueue(
                    schoolId: $schoolId,
                    eventKey: 'izin.submitted',
                    channel: 'in_app',
                    recipientType: 'user',
                    recipientId: (int) $actor->id,
                    templateKey: 'izin_submitted',
                    payload: ['ticket_code' => $ticket->ticket_code, 'status' => 'submitted'],
                    idempotencyKey: sprintf('izin-sub-%d', $dispensation->id),
                    dispatchJob: false,
                );
            }

            $ticketSnap = $this->ticketService->getTicketSnapshotById($schoolId, (int) $ticket->id);

            return [
                'dispensation' => $dispensation->refresh(),
                'ticket' => $ticketSnap ?? [],
                'plain_verification_secret' => $plainSecret,
            ];
        });
    }

    /**
     * Applicant resubmits a permit returned for revision (M4-OPS-02).
     *
     * @param  array<string, mixed>  $data
     */
    public function resubmitPermit(
        int $schoolId,
        AttendanceDispensation $dispensation,
        array $data,
        ?UploadedFile $attachment = null,
        ?User $actor = null,
    ): AttendanceDispensation {
        abort_unless((int) $dispensation->school_id === $schoolId, 404);

        if ($dispensation->status !== DispensationStatusEnum::NEEDS_REVISION) {
            throw ValidationException::withMessages([
                'status' => 'Permohonan izin ini tidak berstatus perlu perbaikan.',
            ]);
        }

        $startDate = (string) ($data['start_date'] ?? $dispensation->start_date->toDateString());
        $endDate = (string) ($data['end_date'] ?? $dispensation->end_date->toDateString());
        $this->validateDatesAndCutoff($schoolId, (int) $dispensation->student_id, $startDate, $endDate, (int) $dispensation->id);

        return DB::transaction(function () use ($schoolId, $dispensation, $data, $startDate, $endDate, $attachment, $actor): AttendanceDispensation {
            /** @var AttendanceDispensation $locked */
            $locked = AttendanceDispensation::query()->where('school_id', $schoolId)->whereKey($dispensation->id)->lockForUpdate()->firstOrFail();

            $attachmentDocId = $locked->attachment_document_id;
            if ($attachment !== null) {
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $attachment,
                    documentKind: 'attachment',
                    ticket: null,
                    uploader: $actor,
                );
                if ($locked->service_ticket_id !== null) {
                    $this->documentService->attachDocumentsToTicket($schoolId, [(int) $doc->id], (int) $locked->service_ticket_id);
                }
                $attachmentDocId = (int) $doc->id;
            }

            $locked->forceFill([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'title' => trim((string) ($data['title'] ?? $locked->title)),
                'reason' => trim((string) ($data['reason'] ?? $locked->reason)),
                'attachment_document_id' => $attachmentDocId,
                'status' => DispensationStatusEnum::PENDING,
            ])->save();

            if ($locked->service_ticket_id !== null) {
                $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
                if ($ticket !== null) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_SUBMITTED,
                        toInternalStatus: 'resubmitted',
                        reason: 'Pemohon mengirimkan perbaikan izin.',
                        actor: $actor,
                        expectedVersion: (int) $ticket['version'],
                        publicSummary: 'Perbaikan izin diterima dan menunggu peninjauan ulang.',
                    );
                }
            }

            $this->audit->log(
                eventType: 'homeroom.dispensation.resubmitted',
                targetType: 'attendance_dispensation',
                targetId: (string) $locked->id,
                newValues: ['start_date' => $startDate, 'end_date' => $endDate],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Homeroom teacher / officer verifies, returns for revision, or decides a permit (M4-OPS-02).
     */
    public function decidePermit(
        User $actor,
        AttendanceDispensation $dispensation,
        string $decision,
        ?string $note = null,
    ): AttendanceDispensation {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $dispensation->school_id === $schoolId, 404);

        if ($actor->hasRole(RoleEnum::HOMEROOM->value) && ! $this->accessScope->canAccessStudentScope($actor, $schoolId, (int) $dispensation->student_id)) {
            abort(403, 'Wali kelas hanya berhak memutuskan izin siswa kelas perwaliannya.');
        }

        if (! in_array($decision, ['approve', 'request_revision', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan verifikasi izin tidak valid.']);
        }

        if (in_array($decision, ['request_revision', 'reject'], true) && trim((string) $note) === '') {
            throw ValidationException::withMessages(['decision_note' => 'Alasan atau catatan wajib diisi untuk permintaan perbaikan atau penolakan.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $dispensation, $decision, $note): AttendanceDispensation {
            /** @var AttendanceDispensation $locked */
            $locked = AttendanceDispensation::query()->where('school_id', $schoolId)->whereKey($dispensation->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [DispensationStatusEnum::PENDING, DispensationStatusEnum::NEEDS_REVISION], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Izin ini sudah memiliki keputusan akhir. Gunakan jalur koreksi jika ingin mengubahnya.',
                ]);
            }

            $oldStatus = $locked->status->value;
            $newEnum = match ($decision) {
                'approve' => DispensationStatusEnum::APPROVED,
                'request_revision' => DispensationStatusEnum::NEEDS_REVISION,
                'reject' => DispensationStatusEnum::REJECTED,
            };

            $locked->forceFill([
                'status' => $newEnum,
                'approved_by_user_id' => $actor->id,
                'decision_note' => $note !== null ? trim($note) ?: null : null,
                'decided_at' => now(),
            ])->save();

            if ($decision === 'approve') {
                $attendanceStatus = $locked->type === DispensationTypeEnum::SICK ? 'sick' : 'permitted';
                $this->attendanceService->applyApprovedDispensationToAttendance(
                    schoolId: $schoolId,
                    studentId: (int) $locked->student_id,
                    termId: (int) $locked->term_id,
                    startDate: $locked->start_date->toDateString(),
                    endDate: $locked->end_date->toDateString(),
                    attendanceStatus: $attendanceStatus,
                    title: $locked->title,
                    actor: $actor,
                );
            }

            $this->syncTicketDecision($schoolId, $locked, $decision, $note, $actor);

            $this->audit->log(
                eventType: 'homeroom.dispensation.decided',
                targetType: 'attendance_dispensation',
                targetId: (string) $locked->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newEnum->value, 'decision_note' => $locked->decision_note],
                actor: $actor,
                schoolId: $schoolId,
            );

            if ($locked->submitted_by_user_id !== null) {
                $this->outbox->enqueue(
                    schoolId: $schoolId,
                    eventKey: 'izin.decided',
                    channel: 'in_app',
                    recipientType: 'user',
                    recipientId: (int) $locked->submitted_by_user_id,
                    templateKey: 'izin_decided',
                    payload: ['dispensation_id' => $locked->id, 'status' => $newEnum->value],
                    idempotencyKey: sprintf('izin-dec-%d-%s', $locked->id, $newEnum->value),
                    dispatchJob: false,
                );
            }

            return $locked->refresh();
        });
    }

    /**
     * Correct a previously decided dispensation with audit trail and attendance rollback (M4-OPS-02).
     */
    public function correctPermitDecision(
        User $actor,
        AttendanceDispensation $dispensation,
        string $newStatus,
        string $correctionReason,
    ): AttendanceDispensation {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $dispensation->school_id === $schoolId, 404);

        if ($actor->hasRole(RoleEnum::HOMEROOM->value) && ! $this->accessScope->canAccessStudentScope($actor, $schoolId, (int) $dispensation->student_id)) {
            abort(403, 'Wali kelas hanya berhak mengoreksi izin siswa kelas perwaliannya.');
        }

        $cleanReason = trim($correctionReason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['correction_reason' => 'Alasan koreksi keputusan izin wajib diisi.']);
        }

        if (! in_array($newStatus, ['approved', 'rejected', 'cancelled'], true)) {
            throw ValidationException::withMessages(['new_status' => 'Status koreksi tidak valid.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $dispensation, $newStatus, $cleanReason): AttendanceDispensation {
            /** @var AttendanceDispensation $locked */
            $locked = AttendanceDispensation::query()->where('school_id', $schoolId)->whereKey($dispensation->id)->lockForUpdate()->firstOrFail();

            $oldStatus = $locked->status;
            $targetEnum = DispensationStatusEnum::from($newStatus);

            if ($oldStatus === DispensationStatusEnum::APPROVED && in_array($targetEnum, [DispensationStatusEnum::REJECTED, DispensationStatusEnum::CANCELLED], true)) {
                $this->attendanceService->revertDispensationAttendance(
                    schoolId: $schoolId,
                    studentId: (int) $locked->student_id,
                    termId: (int) $locked->term_id,
                    startDate: $locked->start_date->toDateString(),
                    endDate: $locked->end_date->toDateString(),
                    reason: $cleanReason,
                    actor: $actor,
                );
            } elseif ($oldStatus !== DispensationStatusEnum::APPROVED && $targetEnum === DispensationStatusEnum::APPROVED) {
                $attendanceStatus = $locked->type === DispensationTypeEnum::SICK ? 'sick' : 'permitted';
                $this->attendanceService->applyApprovedDispensationToAttendance(
                    schoolId: $schoolId,
                    studentId: (int) $locked->student_id,
                    termId: (int) $locked->term_id,
                    startDate: $locked->start_date->toDateString(),
                    endDate: $locked->end_date->toDateString(),
                    attendanceStatus: $attendanceStatus,
                    title: $locked->title,
                    actor: $actor,
                );
            }

            $locked->forceFill([
                'status' => $targetEnum,
                'approved_by_user_id' => $actor->id,
                'correction_reason' => $cleanReason,
                'decided_at' => now(),
            ])->save();

            $this->syncTicketCorrection($schoolId, $locked, $targetEnum, $cleanReason, $actor);

            $this->audit->log(
                eventType: 'homeroom.dispensation.corrected',
                targetType: 'attendance_dispensation',
                targetId: (string) $locked->id,
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => $targetEnum->value, 'correction_reason' => $cleanReason],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPermitsForApplicant(int $schoolId, User $user): array
    {
        $students = $this->academicQuery->getStudentsForUserScope($schoolId, $user);
        $studentIds = array_column($students, 'student_id');

        if ($studentIds === []) {
            return [];
        }

        $items = AttendanceDispensation::query()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->latest('id')
            ->limit(30)
            ->get();

        return $this->hydrateDispensations($schoolId, $items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPermitsForStaffQueue(int $schoolId, User $actor, ?string $statusFilter = null): array
    {
        $scopedStudentIds = $actor->hasRole(RoleEnum::HOMEROOM->value)
            ? $this->academicQuery->getHomeroomStudentIdsForUser($schoolId, (int) $actor->id)
            : null;

        if ($scopedStudentIds === []) {
            return [];
        }

        $items = AttendanceDispensation::query()
            ->where('school_id', $schoolId)
            ->when($scopedStudentIds !== null, fn ($q) => $q->whereIn('student_id', $scopedStudentIds))
            ->when($statusFilter !== null && $statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->latest('id')
            ->limit(60)
            ->get();

        return $this->hydrateDispensations($schoolId, $items);
    }

    public function renderProofPdfBytes(int $schoolId, AttendanceDispensation $dispensation): string
    {
        $schoolSnapshot = $this->schoolScope->getSchoolLegalSnapshot($schoolId);
        $logo = $this->schoolScope->getSchoolLogoDataUri($schoolId);
        if ($logo !== null) {
            $schoolSnapshot['logo_data_uri'] = $logo;
        }

        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, (int) $dispensation->student_id);
        $ticket = $dispensation->service_ticket_id
            ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $dispensation->service_ticket_id)
            : null;

        $ticketCode = (string) ($ticket['ticket_code'] ?? ('IZN-'.$dispensation->id));
        $trackUrl = rtrim((string) config('app.url'), '/').'/lacak?code='.urlencode($ticketCode).'&category=izin';
        $writer = new Writer(new ImageRenderer(new RendererStyle(160, 2), new SvgImageBackEnd));
        $qr = base64_encode($writer->writeString($trackUrl));

        $pdf = Pdf::loadView('homeroom.permit-proof', [
            'school' => $schoolSnapshot,
            'dispensation' => $dispensation,
            'student' => $student,
            'ticket' => $ticket,
            'qr' => $qr,
            'track_url' => $trackUrl,
        ])->setPaper('a4');

        return $pdf->output();
    }

    /**
     * @param  Collection<int, AttendanceDispensation>  $items
     * @return list<array<string, mixed>>
     */
    private function hydrateDispensations(int $schoolId, $items): array
    {
        $studentIds = $items->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all();
        $ticketIds = $items->pluck('service_ticket_id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        $studentMap = $this->academicQuery->getStudentsSnapshotsByIds($schoolId, $studentIds);
        $ticketMap = $this->ticketService->getTicketsMapByIds($schoolId, $ticketIds);

        return $items->map(function (AttendanceDispensation $item) use ($studentMap, $ticketMap): array {
            $student = $studentMap[(int) $item->student_id] ?? null;
            $ticket = $item->service_ticket_id ? ($ticketMap[(int) $item->service_ticket_id] ?? null) : null;

            return [
                'id' => (int) $item->id,
                'student_id' => (int) $item->student_id,
                'student_name' => $student['name'] ?? '-',
                'student_nis' => $student['nis'] ?? '-',
                'class_name' => $student['class_group_name'] ?? '-',
                'ticket_code' => $ticket['ticket_code'] ?? null,
                'type' => $item->type->value,
                'type_label' => $item->type->label(),
                'title' => (string) $item->title,
                'reason' => (string) $item->reason,
                'start_date' => $item->start_date->toDateString(),
                'end_date' => $item->end_date->toDateString(),
                'contact_name' => $item->contact_name,
                'contact_phone' => $item->contact_phone,
                'has_signature' => filled($item->signature_data),
                'attachment_document_id' => $item->attachment_document_id,
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'decision_note' => $item->decision_note,
                'correction_reason' => $item->correction_reason,
                'decided_at' => $item->decided_at?->toIso8601String(),
            ];
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function publicPermitStatuses(int $schoolId, ?int $studentId, ?string $ticketCode = null): array
    {
        if ($studentId === null && trim((string) $ticketCode) === '') {
            return [];
        }

        return AttendanceDispensation::query()
            ->leftJoin('service_tickets', 'service_tickets.id', '=', 'attendance_dispensations.service_ticket_id')
            ->where('attendance_dispensations.school_id', $schoolId)
            ->when($studentId !== null, fn ($query) => $query->where('attendance_dispensations.student_id', $studentId),
                fn ($query) => $query->where('service_tickets.ticket_code', trim((string) $ticketCode)))
            ->select('attendance_dispensations.*', 'service_tickets.ticket_code')
            ->orderByDesc('attendance_dispensations.id')
            ->limit(20)
            ->get()
            ->map(static fn (AttendanceDispensation $permit): array => [
                'ticket_code' => $permit->ticket_code,
                'type_label' => $permit->type->label(),
                'start_date' => $permit->start_date->toDateString(),
                'end_date' => $permit->end_date->toDateString(),
                'status' => $permit->status->value,
                'status_label' => $permit->status->label(),
            ])->all();
    }

    public function checkPublicPermitToken(int $schoolId, int $studentId, string $token): void
    {
        DB::transaction(fn () => $this->lockAndValidateToken($schoolId, $studentId, strtoupper(trim($token))));
    }

    private function validateDatesAndCutoff(
        int $schoolId,
        int $studentId,
        string $startDateStr,
        string $endDateStr,
        ?int $excludeDispensationId = null,
        bool $publicSubmission = false,
        bool $hasToken = false,
    ): void {
        $tz = $this->schoolScope->getTimezone($schoolId);
        $localNow = $this->schoolScope->localNow($schoolId);
        $todayLocal = $localNow->startOfDay();
        $ops = $this->schoolScope->getOperationsSettings($schoolId);

        $start = CarbonImmutable::parse($startDateStr, $tz)->startOfDay();
        $end = CarbonImmutable::parse($endDateStr, $tz)->startOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir izin tidak boleh lebih awal dari tanggal mulai.',
            ]);
        }

        if ($start->lt($todayLocal)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal mulai izin tidak boleh di masa lalu.',
            ]);
        }

        $maxAdvance = $todayLocal->addDays($ops['permit_max_advance_days']);
        if ($start->gt($maxAdvance)) {
            throw ValidationException::withMessages([
                'start_date' => sprintf('Pengajuan izin terencana maksimal %d hari ke depan.', $ops['permit_max_advance_days']),
            ]);
        }

        if ($start->equalTo($todayLocal)) {
            $cutoff = $ops['permit_same_day_cutoff'];
            if ($publicSubmission && $localNow->format('H:i') >= '08:00' && $localNow->format('H:i') < '22:00' && ! $hasToken) {
                throw ValidationException::withMessages(['one_time_token' => 'Pengajuan pukul 08:00 s/d 22:00 memerlukan token dispensasi wali kelas.']);
            }
            if (! $publicSubmission && $localNow->format('H:i') > $cutoff) {
                throw ValidationException::withMessages([
                    'start_date' => sprintf('Batas jam pengajuan izin hari yang sama (%s %s) telah terlewati.', $cutoff, $tz),
                ]);
            }
        }

        $conflict = AttendanceDispensation::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->when($excludeDispensationId !== null, fn ($q) => $q->where('id', '!=', $excludeDispensationId))
            ->whereIn('status', [
                DispensationStatusEnum::PENDING->value,
                DispensationStatusEnum::NEEDS_REVISION->value,
                DispensationStatusEnum::APPROVED->value,
            ])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_date' => 'Rentang tanggal izin bentrok dengan permohonan izin lain untuk siswa ini.',
            ]);
        }
    }

    private function lockAndValidateToken(int $schoolId, int $studentId, string $plainToken): StudentPermitToken
    {
        $prefix = substr($plainToken, 0, 8);
        $candidates = StudentPermitToken::query()
            ->where('school_id', $schoolId)
            ->where('token_prefix', $prefix)
            ->lockForUpdate()
            ->get();

        /** @var StudentPermitToken|null $matched */
        $matched = null;
        foreach ($candidates as $candidate) {
            if (Hash::check($plainToken, (string) $candidate->token_hash)) {
                $matched = $candidate;
                break;
            }
        }

        if ($matched === null) {
            throw ValidationException::withMessages([
                'one_time_token' => 'Token izin sekali pakai tidak valid.',
            ]);
        }

        if ((int) $matched->student_id !== $studentId || $matched->purpose !== 'permit_submission') {
            throw ValidationException::withMessages([
                'one_time_token' => 'Token izin tidak cocok dengan siswa atau tujuan pengajuan ini.',
            ]);
        }

        if ($matched->used_at !== null) {
            throw ValidationException::withMessages([
                'one_time_token' => 'Token izin sekali pakai sudah pernah digunakan.',
            ]);
        }

        if ($matched->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'one_time_token' => 'Token izin sekali pakai sudah kedaluwarsa.',
            ]);
        }

        return $matched;
    }

    private function syncTicketDecision(
        int $schoolId,
        AttendanceDispensation $dispensation,
        string $decision,
        ?string $note,
        User $actor,
    ): void {
        if ($dispensation->service_ticket_id === null) {
            return;
        }

        $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $dispensation->service_ticket_id);
        if ($ticket === null) {
            return;
        }

        $version = (int) $ticket['version'];
        if ($decision === 'approve') {
            if ($ticket['public_status'] === ServiceWorkflowMap::STATUS_SUBMITTED) {
                $mid = $this->ticketService->transition(
                    ticketId: (int) $dispensation->service_ticket_id,
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
                ticketId: (int) $dispensation->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: ServiceWorkflowMap::STATUS_COMPLETED,
                toInternalStatus: 'approved',
                reason: $note,
                actor: $actor,
                expectedVersion: $version,
                publicSummary: 'Permohonan izin disetujui dan telah diperhitungkan pada presensi.',
            );

            return;
        }

        $targetStatus = $decision === 'request_revision'
            ? ServiceWorkflowMap::STATUS_NEEDS_REVISION
            : ServiceWorkflowMap::STATUS_REJECTED;

        $this->ticketService->transition(
            ticketId: (int) $dispensation->service_ticket_id,
            schoolId: $schoolId,
            toPublicStatus: $targetStatus,
            toInternalStatus: $decision,
            reason: $note,
            actor: $actor,
            expectedVersion: $version,
            publicSummary: $decision === 'request_revision'
                ? 'Permohonan izin dikembalikan untuk perbaikan data/lampiran.'
                : 'Permohonan izin ditolak. Periksa catatan petugas.',
        );
    }

    private function syncTicketCorrection(
        int $schoolId,
        AttendanceDispensation $dispensation,
        DispensationStatusEnum $targetEnum,
        string $reason,
        User $actor,
    ): void {
        if ($dispensation->service_ticket_id === null) {
            return;
        }

        $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $dispensation->service_ticket_id);
        if ($ticket === null) {
            return;
        }

        $targetPublicStatus = match ($targetEnum) {
            DispensationStatusEnum::APPROVED => ServiceWorkflowMap::STATUS_COMPLETED,
            DispensationStatusEnum::REJECTED => ServiceWorkflowMap::STATUS_REJECTED,
            DispensationStatusEnum::CANCELLED => ServiceWorkflowMap::STATUS_CANCELLED,
            default => $ticket['public_status'],
        };

        if ($ticket['public_status'] === $targetPublicStatus) {
            return;
        }

        $version = (int) $ticket['version'];
        if (
            in_array($ticket['public_status'], [ServiceWorkflowMap::STATUS_REJECTED, ServiceWorkflowMap::STATUS_SUBMITTED], true)
            && $targetPublicStatus === ServiceWorkflowMap::STATUS_COMPLETED
        ) {
            $mid = $this->ticketService->transition(
                ticketId: (int) $dispensation->service_ticket_id,
                schoolId: $schoolId,
                toPublicStatus: ServiceWorkflowMap::STATUS_IN_REVIEW,
                toInternalStatus: 'correction_review',
                reason: $reason,
                actor: $actor,
                expectedVersion: $version,
            );
            $version = (int) $mid->version;
        }

        $this->ticketService->transition(
            ticketId: (int) $dispensation->service_ticket_id,
            schoolId: $schoolId,
            toPublicStatus: $targetPublicStatus,
            toInternalStatus: 'corrected_'.$targetEnum->value,
            reason: $reason,
            actor: $actor,
            expectedVersion: $version,
            publicSummary: sprintf('Keputusan izin dikoreksi oleh petugas menjadi %s.', $targetEnum->label()),
        );
    }
}
