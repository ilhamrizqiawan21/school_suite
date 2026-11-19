<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Application\Services;

use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicantDocument;
use App\Modules\Admissions\Infrastructure\Models\AdmissionDocumentRequirement;
use App\Modules\Admissions\Infrastructure\Models\AdmissionPeriod;
use App\Modules\Admissions\Infrastructure\Models\AdmissionReRegistration;
use App\Modules\Admissions\Infrastructure\Models\AdmissionTrack;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\NotificationOutboxService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Shared\Audit\AuditLogger;
use App\Shared\Files\PrivateStorageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Public candidate self-registration, document revision, announcement gate,
 * and re-registration portal workflows for PPDB / SPMB (M4-LIF-01).
 */
class CandidatePortalService
{
    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly ExternalApplicantAuthService $externalAuth,
        private readonly NotificationOutboxService $outbox,
        private readonly PrivateStorageService $storage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int|string, UploadedFile>  $uploadedFilesByRequirement
     * @return array{applicant: AdmissionApplicant, ticket: array<string, mixed>, plain_verification_secret: string}
     */
    public function submitCandidateRegistration(
        int $schoolId,
        array $data,
        array $uploadedFilesByRequirement = [],
        ?int $externalApplicantId = null,
    ): array {
        $idempotencyKey = isset($data['idempotency_key']) && trim((string) $data['idempotency_key']) !== ''
            ? trim((string) $data['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            /** @var AdmissionApplicant|null $existing */
            $existing = AdmissionApplicant::query()
                ->where('school_id', $schoolId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                $ticket = $existing->service_ticket_id
                    ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                    : [];

                return [
                    'applicant' => $existing,
                    'ticket' => $ticket ?? [],
                    'plain_verification_secret' => '',
                ];
            }
        }

        /** @var AdmissionPeriod|null $period */
        $period = AdmissionPeriod::query()
            ->where('school_id', $schoolId)
            ->find((int) ($data['period_id'] ?? 0));

        if ($period === null || $period->status !== 'registration_open') {
            throw ValidationException::withMessages([
                'period_id' => 'Periode pendaftaran belum dibuka atau telah ditutup.',
            ]);
        }

        $todayLocal = $this->schoolScope->localNow($schoolId)->startOfDay();
        if ($period->registration_start !== null && $todayLocal->lt(CarbonImmutable::parse($period->registration_start->toDateString())->startOfDay())) {
            throw ValidationException::withMessages([
                'period_id' => 'Jadwal pendaftaran periode ini belum dimulai.',
            ]);
        }
        if ($period->registration_end !== null && $todayLocal->gt(CarbonImmutable::parse($period->registration_end->toDateString())->startOfDay())) {
            throw ValidationException::withMessages([
                'period_id' => 'Jadwal pendaftaran periode ini sudah berakhir.',
            ]);
        }

        /** @var AdmissionTrack|null $track */
        $track = AdmissionTrack::query()
            ->where('period_id', $period->id)
            ->where('is_active', true)
            ->find((int) ($data['track_id'] ?? 0));

        if ($track === null) {
            throw ValidationException::withMessages([
                'track_id' => 'Jalur pendaftaran tidak ditemukan atau tidak aktif pada periode ini.',
            ]);
        }

        $nisn = isset($data['nisn']) && trim((string) $data['nisn']) !== '' ? trim((string) $data['nisn']) : null;
        if ($nisn !== null && AdmissionApplicant::query()->where('school_id', $schoolId)->where('period_id', $period->id)->where('nisn', $nisn)->exists()) {
            throw ValidationException::withMessages([
                'nisn' => 'NISN ini sudah terdaftar pada periode SPMB yang dipilih.',
            ]);
        }

        $requirements = AdmissionDocumentRequirement::query()
            ->where('track_id', $track->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($requirements as $req) {
            if ($req->is_required && ! ($uploadedFilesByRequirement[$req->id] ?? null) instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    "documents.{$req->id}" => sprintf('Berkas persyaratan [%s] wajib diunggah.', $req->label),
                ]);
            }
        }

        return DB::transaction(function () use ($schoolId, $period, $track, $data, $nisn, $requirements, $uploadedFilesByRequirement, $externalApplicantId, $idempotencyKey): array {
            $this->schoolScope->lockSchool($schoolId);

            if ($idempotencyKey !== null) {
                /** @var AdmissionApplicant|null $existing */
                $existing = AdmissionApplicant::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing !== null) {
                    $ticket = $existing->service_ticket_id
                        ? $this->ticketService->getTicketSnapshotById($schoolId, (int) $existing->service_ticket_id)
                        : [];

                    return [
                        'applicant' => $existing,
                        'ticket' => $ticket ?? [],
                        'plain_verification_secret' => '',
                    ];
                }
            }

            $registrationNo = 'SPMB-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(7));
            $plainSecret = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $applicant = AdmissionApplicant::query()->create([
                'school_id' => $schoolId,
                'period_id' => $period->id,
                'track_id' => $track->id,
                'external_applicant_id' => $externalApplicantId,
                'verification_secret_hash' => Hash::make($plainSecret),
                'registration_no' => $registrationNo,
                'name' => trim((string) $data['name']),
                'nisn' => $nisn,
                'gender' => (string) $data['gender'],
                'birth_date' => $data['birth_date'] ?? null,
                'birth_place' => isset($data['birth_place']) ? trim((string) $data['birth_place']) ?: null : null,
                'parent_name' => isset($data['parent_name']) ? trim((string) $data['parent_name']) ?: null : null,
                'parent_phone' => isset($data['parent_phone']) ? trim((string) $data['parent_phone']) ?: null : null,
                'address' => isset($data['address']) ? trim((string) $data['address']) ?: null : null,
                'previous_school' => isset($data['previous_school']) ? trim((string) $data['previous_school']) ?: null : null,
                'status' => 'registered',
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) ?: null : null,
                'idempotency_key' => $idempotencyKey,
                'created_by' => null,
            ]);

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'ppdb',
                'prefix' => 'PPDB',
                'external_applicant_id' => $externalApplicantId,
                'applicant_snapshot' => [
                    'registration_no' => $registrationNo,
                    'student_name' => $applicant->name,
                    'period_name' => $period->name,
                    'track_name' => $track->name,
                ],
                'verification_secret' => $plainSecret,
                'internal_status' => 'registered',
                'public_summary' => sprintf('Pendaftaran PPDB [%s] diterima dan menunggu verifikasi berkas.', $registrationNo),
                'idempotency_key' => $idempotencyKey,
            ]);

            $applicant->forceFill(['service_ticket_id' => (int) $ticket->id])->save();

            foreach ($requirements as $req) {
                $file = $uploadedFilesByRequirement[$req->id] ?? null;
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = sprintf('schools/%d/admissions/%d', $schoolId, $applicant->id);
                $stored = $this->storage->storeValidatedUpload($file, $path);
                AdmissionApplicantDocument::query()->create([
                    'applicant_id' => $applicant->id,
                    'requirement_id' => $req->id,
                    'path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['size_bytes'],
                    'status' => 'pending',
                ]);
            }

            $this->audit->log(
                eventType: 'admissions.candidate.self_registered',
                targetType: 'admission_applicant',
                targetId: (string) $applicant->id,
                newValues: ['registration_no' => $registrationNo, 'track_id' => $track->id],
                schoolId: $schoolId,
            );

            $this->outbox->enqueue(
                schoolId: $schoolId,
                eventKey: 'ppdb.registered',
                channel: 'in_app',
                recipientType: $externalApplicantId ? 'external_applicant' : 'applicant',
                recipientId: $externalApplicantId,
                templateKey: 'ppdb_registered',
                payload: ['registration_no' => $registrationNo, 'ticket_code' => $ticket->ticket_code],
                idempotencyKey: sprintf('ppdb-reg-%d', $applicant->id),
                dispatchJob: false,
            );

            return [
                'applicant' => $applicant->refresh(),
                'ticket' => $this->ticketService->getTicketSnapshotById($schoolId, (int) $ticket->id) ?? [],
                'plain_verification_secret' => $plainSecret,
            ];
        });
    }

    /**
     * Allow a verified candidate (via session or reg_no + PIN) to upload revised documents when status = needs_revision.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string, UploadedFile>  $uploadedFilesByRequirement
     */
    public function resubmitRevision(
        int $schoolId,
        AdmissionApplicant $applicant,
        array $data,
        array $uploadedFilesByRequirement = [],
    ): AdmissionApplicant {
        abort_unless((int) $applicant->school_id === $schoolId, 404);

        if ($applicant->status !== 'needs_revision') {
            throw ValidationException::withMessages([
                'status' => 'Perbaikan data/berkas hanya tersedia saat pendaftar berstatus perlu perbaikan.',
            ]);
        }

        return DB::transaction(function () use ($schoolId, $applicant, $data, $uploadedFilesByRequirement): AdmissionApplicant {
            /** @var AdmissionApplicant $locked */
            $locked = AdmissionApplicant::query()->where('school_id', $schoolId)->whereKey($applicant->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'name' => trim((string) ($data['name'] ?? $locked->name)),
                'previous_school' => isset($data['previous_school']) ? trim((string) $data['previous_school']) ?: null : $locked->previous_school,
                'parent_name' => isset($data['parent_name']) ? trim((string) $data['parent_name']) ?: null : $locked->parent_name,
                'parent_phone' => isset($data['parent_phone']) ? trim((string) $data['parent_phone']) ?: null : $locked->parent_phone,
                'address' => isset($data['address']) ? trim((string) $data['address']) ?: null : $locked->address,
                'status' => 'registered',
                'revision_notes' => null,
            ])->save();

            foreach ($uploadedFilesByRequirement as $reqId => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = sprintf('schools/%d/admissions/%d', $schoolId, $locked->id);
                $stored = $this->storage->storeValidatedUpload($file, $path);
                $existingDoc = AdmissionApplicantDocument::query()
                    ->where('applicant_id', $locked->id)
                    ->where('requirement_id', (int) $reqId)
                    ->first();

                if ($existingDoc !== null) {
                    $existingDoc->forceFill([
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size' => $stored['size_bytes'],
                        'status' => 'pending',
                        'notes' => null,
                    ])->save();
                } else {
                    AdmissionApplicantDocument::query()->create([
                        'applicant_id' => $locked->id,
                        'requirement_id' => (int) $reqId,
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size' => $stored['size_bytes'],
                        'status' => 'pending',
                    ]);
                }
            }

            if ($locked->service_ticket_id !== null) {
                $ticket = $this->ticketService->getTicketSnapshotById($schoolId, (int) $locked->service_ticket_id);
                if ($ticket !== null && ServiceWorkflowMap::canTransition((string) $ticket['public_status'], ServiceWorkflowMap::STATUS_SUBMITTED)) {
                    $this->ticketService->transition(
                        ticketId: (int) $locked->service_ticket_id,
                        schoolId: $schoolId,
                        toPublicStatus: ServiceWorkflowMap::STATUS_SUBMITTED,
                        toInternalStatus: 'resubmitted',
                        reason: 'Kandidat mengirim perbaikan data dan berkas.',
                        actor: null,
                        expectedVersion: (int) $ticket['version'],
                        publicSummary: 'Perbaikan berkas PPDB diterima dan siap diverifikasi ulang.',
                    );
                }
            }

            $this->audit->log(
                eventType: 'admissions.candidate.resubmitted',
                targetType: 'admission_applicant',
                targetId: (string) $locked->id,
                newValues: ['status' => 'registered'],
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Authorize and inspect candidate status/selection result strictly respecting announcement release gate (M4-LIF-01).
     *
     * @return array<string, mixed>
     */
    public function lookupStatus(int $schoolId, string $registrationNo, string $pin): array
    {
        $cleanReg = strtoupper(trim($registrationNo));
        $cleanPin = trim($pin);

        /** @var AdmissionApplicant|null $applicant */
        $applicant = AdmissionApplicant::query()
            ->with(['period', 'track.requirements', 'documents', 'selectionResult', 'reRegistration'])
            ->where('school_id', $schoolId)
            ->where('registration_no', $cleanReg)
            ->first();

        if ($applicant === null || ! Hash::check($cleanPin, (string) $applicant->verification_secret_hash)) {
            return [
                'authorized' => false,
                'message' => 'Nomor pendaftaran atau PIN verifikasi tidak cocok.',
            ];
        }

        return [
            'authorized' => true,
            'applicant' => $this->serializeCandidateView($schoolId, $applicant),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeCandidateView(int $schoolId, AdmissionApplicant $applicant): array
    {
        $applicant->loadMissing(['period', 'track.requirements', 'documents', 'selectionResult', 'reRegistration']);
        $released = $this->isAnnouncementReleased($schoolId, $applicant->period);
        $decision = $released ? $applicant->selectionResult?->decision : null;
        $reRegistrationOpen = $released
            && $decision === 'accepted'
            && $this->isReRegistrationWindowOpen($schoolId, $applicant->period);

        return [
            'id' => (int) $applicant->id,
            'registration_no' => (string) $applicant->registration_no,
            'name' => (string) $applicant->name,
            'nisn' => $applicant->nisn,
            'period_name' => $applicant->period?->name,
            'period_status' => $applicant->period?->status,
            'track_name' => $applicant->track?->name,
            'status' => $released ? (string) $applicant->status : (in_array($applicant->status, ['accepted', 'rejected', 'waitlisted'], true) ? 'selection_review' : (string) $applicant->status),
            'revision_notes' => $applicant->revision_notes,
            'is_result_released' => $released,
            'announcement_at' => $applicant->period?->announcement_at?->toDateString(),
            'selection_decision' => $decision,
            'selection_rank' => $released ? $applicant->selectionResult?->rank : null,
            'can_re_register' => $reRegistrationOpen && $applicant->reRegistration?->status !== 'confirmed',
            're_registration_status' => $applicant->reRegistration?->status,
            're_registration_submitted' => $applicant->reRegistration?->submitted_by_candidate_at !== null,
            'requirements' => $applicant->track?->requirements->map(fn ($r): array => [
                'id' => (int) $r->id,
                'label' => (string) $r->label,
                'is_required' => (bool) $r->is_required,
            ])->values()->all() ?? [],
            'documents' => $applicant->documents->map(fn ($d): array => [
                'id' => (int) $d->id,
                'requirement_id' => $d->requirement_id,
                'original_name' => $d->original_name,
                'status' => $d->status,
                'notes' => $d->notes,
            ])->values()->all(),
        ];
    }

    public function submitCandidateReRegistrationIntent(int $schoolId, AdmissionApplicant $applicant, ?string $notes = null): AdmissionReRegistration
    {
        abort_unless((int) $applicant->school_id === $schoolId, 404);
        $applicant->loadMissing(['period', 'selectionResult', 'reRegistration']);

        if (! $this->isAnnouncementReleased($schoolId, $applicant->period) || $applicant->selectionResult?->decision !== 'accepted') {
            throw ValidationException::withMessages([
                'status' => 'Daftar ulang hanya tersedia setelah pengumuman resmi untuk kandidat yang diterima.',
            ]);
        }

        if (! $this->isReRegistrationWindowOpen($schoolId, $applicant->period)) {
            throw ValidationException::withMessages([
                'status' => 'Periode daftar ulang belum dibuka atau telah berakhir.',
            ]);
        }

        $record = AdmissionReRegistration::query()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'status' => $applicant->reRegistration?->status === 'confirmed' ? 'confirmed' : 'pending',
                'notes' => isset($notes) ? trim($notes) ?: null : null,
                'submitted_by_candidate_at' => now(),
            ],
        );

        $this->audit->log(
            eventType: 'admissions.candidate.re_registration_submitted',
            targetType: 'admission_applicant',
            targetId: (string) $applicant->id,
            newValues: ['status' => $record->status],
            schoolId: $schoolId,
        );

        return $record->refresh();
    }

    public function canSessionViewCandidate(Request $request, int $schoolId, AdmissionApplicant $applicant): bool
    {
        if ((int) $applicant->school_id !== $schoolId) {
            return false;
        }

        if ($request->session()->get("ppdb_candidate_access.{$applicant->id}") === true) {
            return true;
        }

        $ext = $this->externalAuth->currentFromSession($request, $schoolId);

        return $ext !== null && $applicant->external_applicant_id !== null && (int) $ext['id'] === (int) $applicant->external_applicant_id;
    }

    public function renderProofPdfBytes(AdmissionApplicant $applicant): string
    {
        $applicant->loadMissing(['period', 'track']);
        $school = $this->schoolScope->getSchoolDetails((int) $applicant->school_id);
        $ticket = $applicant->service_ticket_id !== null
            ? $this->ticketService->getTicketSnapshotById((int) $applicant->school_id, (int) $applicant->service_ticket_id)
            : null;

        return Pdf::loadView('admissions.registration-proof', [
            'school' => $school,
            'applicant' => $applicant,
            'ticketCode' => $ticket['ticket_code'] ?? null,
        ])->setPaper('a4')->output();
    }

    public function isAnnouncementReleased(int $schoolId, ?AdmissionPeriod $period): bool
    {
        if ($period === null) {
            return false;
        }

        if (! in_array($period->status, ['announcement', 're_registration', 'closed'], true)) {
            return false;
        }

        if ($period->announcement_at !== null) {
            $todayLocal = $this->schoolScope->localNow($schoolId)->startOfDay();
            if ($todayLocal->lt(CarbonImmutable::parse($period->announcement_at->toDateString())->startOfDay())) {
                return false;
            }
        }

        return true;
    }

    public function isReRegistrationWindowOpen(int $schoolId, ?AdmissionPeriod $period): bool
    {
        if ($period === null) {
            return false;
        }

        if (! in_array($period->status, ['announcement', 're_registration'], true)) {
            return false;
        }

        $todayLocal = $this->schoolScope->localNow($schoolId)->startOfDay();
        if ($period->re_registration_start !== null && $todayLocal->lt(CarbonImmutable::parse($period->re_registration_start->toDateString())->startOfDay())) {
            return false;
        }
        if ($period->re_registration_end !== null && $todayLocal->gt(CarbonImmutable::parse($period->re_registration_end->toDateString())->startOfDay())) {
            return false;
        }

        return true;
    }
}
