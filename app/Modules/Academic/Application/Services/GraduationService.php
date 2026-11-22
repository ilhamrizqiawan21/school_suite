<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Domain\Enums\EnrollmentStatusEnum;
use App\Modules\Academic\Domain\Enums\StudentStatusEnum;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\GraduationBatch;
use App\Modules\Academic\Infrastructure\Models\GraduationRecord;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLifecycleEvent;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Shared\Audit\AuditLogger;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Manages graduation preparation, principal sign-off, release-time gates,
 * controlled SKL PDF issuance/re-issuance, and original certificate collection (M4-LIF-03).
 */
class GraduationService
{
    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBatch(User $actor, array $data): GraduationBatch
    {
        $schoolId = (int) $actor->school_id;
        /** @var AcademicYear $year */
        $year = AcademicYear::query()->where('school_id', $schoolId)->findOrFail((int) $data['academic_year_id']);

        $tz = $this->schoolScope->getTimezone($schoolId);
        $appTz = (string) config('app.timezone', 'UTC');
        $releaseAt = ! empty($data['release_at'])
            ? CarbonImmutable::parse((string) $data['release_at'], $tz)->setTimezone($appTz)
            : null;

        $batch = GraduationBatch::query()->create([
            'school_id' => $schoolId,
            'academic_year_id' => $year->id,
            'title' => trim((string) $data['title']),
            'skl_prefix' => strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) ($data['skl_prefix'] ?? 'SKL')) ?: 'SKL'),
            'release_at' => $releaseAt,
            'is_published' => false,
            'signatory_name' => isset($data['signatory_name']) ? trim((string) $data['signatory_name']) ?: null : null,
            'signatory_title' => isset($data['signatory_title']) ? trim((string) $data['signatory_title']) ?: null : 'Kepala Madrasah / Sekolah',
        ]);

        $this->audit->log(
            eventType: 'academic.graduation_batch.created',
            targetType: 'GraduationBatch',
            targetId: (string) $batch->id,
            newValues: ['title' => $batch->title, 'academic_year_id' => $year->id],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $batch;
    }

    /**
     * Prepare or update a student's graduation record & optionally generate verification PIN.
     *
     * @param  array<string, mixed>  $data
     * @return array{record: GraduationRecord, plain_pin: ?string}
     */
    public function upsertRecord(User $actor, GraduationBatch $batch, array $data): array
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $batch->school_id === $schoolId, 404);

        /** @var Student $student */
        $student = Student::query()->where('school_id', $schoolId)->findOrFail((int) $data['student_id']);
        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        $decision = (string) ($data['decision'] ?? 'graduated');
        if (! in_array($decision, ['graduated', 'not_graduated'], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan kelulusan harus graduated atau not_graduated.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $batch, $student, $enrollment, $decision, $data): array {
            $batch = GraduationBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $existing = GraduationRecord::query()
                ->where('batch_id', $batch->id)
                ->where('student_id', $student->id)
                ->first();

            $plainPin = isset($data['access_pin']) && trim((string) $data['access_pin']) !== ''
                ? trim((string) $data['access_pin'])
                : ($existing === null ? str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT) : null);

            $sklNumber = isset($data['skl_number']) && trim((string) $data['skl_number']) !== ''
                ? trim((string) $data['skl_number'])
                : ($existing?->skl_number ?? sprintf('%s/%s/%04d', $batch->skl_prefix, now()->format('Y'), GraduationRecord::query()->where('batch_id', $batch->id)->count() + 1));

            $record = GraduationRecord::query()->updateOrCreate(
                ['batch_id' => $batch->id, 'student_id' => $student->id],
                [
                    'school_id' => $schoolId,
                    'enrollment_id' => $enrollment?->id,
                    'decision' => $decision,
                    'skl_number' => $sklNumber,
                    'average_score' => isset($data['average_score']) && $data['average_score'] !== '' ? (float) $data['average_score'] : $existing?->average_score,
                    'access_pin_hash' => $plainPin !== null ? Hash::make($plainPin) : $existing?->access_pin_hash,
                    'notes' => isset($data['notes']) ? trim((string) $data['notes']) ?: null : $existing?->notes,
                ],
            );

            if ($batch->is_published && $this->isBatchReleased($batch) && $decision === 'graduated') {
                $this->applyGraduatedStudentState($actor, $schoolId, $student, $enrollment);
            }

            return ['record' => $record->refresh(), 'plain_pin' => $plainPin];
        });
    }

    /**
     * Officially sign and publish graduation batch results & generate SKL PDFs for graduated records.
     */
    public function publishBatch(User $actor, GraduationBatch $batch, string $signatoryName, string $signatoryTitle, ?string $releaseAtIso = null): GraduationBatch
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $batch->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canApproveFinalDecision($actor, $schoolId) && $this->accessScope->canSignOfficialDocument($actor, $schoolId), 403);

        $tz = $this->schoolScope->getTimezone($schoolId);
        $appTz = (string) config('app.timezone', 'UTC');

        return DB::transaction(function () use ($actor, $schoolId, $batch, $signatoryName, $signatoryTitle, $releaseAtIso, $tz, $appTz): GraduationBatch {
            /** @var GraduationBatch $locked */
            $locked = GraduationBatch::query()->where('school_id', $schoolId)->whereKey($batch->id)->lockForUpdate()->firstOrFail();

            $releaseAt = $releaseAtIso !== null && trim($releaseAtIso) !== ''
                ? CarbonImmutable::parse($releaseAtIso, $tz)->setTimezone($appTz)
                : ($locked->release_at ?? now());

            $locked->forceFill([
                'signatory_name' => trim($signatoryName),
                'signatory_title' => trim($signatoryTitle),
                'release_at' => $releaseAt,
                'is_published' => true,
                'published_by_user_id' => $actor->id,
                'published_at' => now(),
            ])->save();

            $records = $locked->records()->with(['student', 'enrollment'])->get();
            foreach ($records as $record) {
                if ($record->skl_document_id === null) {
                    $this->issueSklDocumentInternal($actor, $locked, $record);
                }
                if ($this->isBatchReleased($locked) && $record->decision === 'graduated' && $record->student !== null) {
                    $this->applyGraduatedStudentState($actor, $schoolId, $record->student, $record->enrollment);
                }
            }

            $this->audit->log(
                eventType: 'academic.graduation_batch.published',
                targetType: 'GraduationBatch',
                targetId: (string) $locked->id,
                newValues: ['release_at' => $releaseAt->toIso8601String(), 'record_count' => $records->count()],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Controlled re-issuance of an SKL PDF: revokes previous SKL document & issues a new versioned PDF.
     */
    public function reissueSkl(User $actor, GraduationRecord $record, string $reason): GraduationRecord
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $record->school_id === $schoolId, 404);
        abort_unless($this->accessScope->canApproveFinalDecision($actor, $schoolId) && $this->accessScope->canSignOfficialDocument($actor, $schoolId), 403);

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penerbitan ulang SKL wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $record, $cleanReason): GraduationRecord {
            /** @var GraduationRecord $locked */
            $locked = GraduationRecord::query()->with('batch')->where('school_id', $schoolId)->whereKey($record->id)->lockForUpdate()->firstOrFail();

            if ($locked->skl_document_id !== null) {
                $this->documentService->revokeDocument((int) $locked->skl_document_id, $schoolId, "Penerbitan ulang SKL: {$cleanReason}", $actor);
            }

            $locked->forceFill([
                'reissue_count' => (int) $locked->reissue_count + 1,
                'reissue_reason' => $cleanReason,
            ])->save();

            $this->issueSklDocumentInternal($actor, $locked->batch, $locked);

            $this->audit->log(
                eventType: 'academic.graduation_skl.reissued',
                targetType: 'GraduationRecord',
                targetId: (string) $locked->id,
                newValues: ['reissue_count' => $locked->reissue_count, 'reason' => $cleanReason],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    public function markOriginalCollected(User $actor, GraduationRecord $record, string $collectorName, string $relation = 'Siswa Bersangkutan'): GraduationRecord
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $record->school_id === $schoolId, 404);

        if ($record->original_collected_at !== null) {
            throw ValidationException::withMessages(['collected_by' => 'Dokumen kelulusan asli untuk siswa ini sudah pernah tercatat diambil.']);
        }

        $cleanName = trim($collectorName);
        if ($cleanName === '') {
            throw ValidationException::withMessages(['collected_by' => 'Nama pengambil dokumen asli wajib diisi.']);
        }

        $record->forceFill([
            'original_collected_at' => now(),
            'original_collected_by' => $cleanName,
            'collector_relation' => trim($relation) ?: 'Siswa Bersangkutan',
        ])->save();

        $this->audit->log(
            eventType: 'academic.graduation.original_collected',
            targetType: 'GraduationRecord',
            targetId: (string) $record->id,
            newValues: ['collected_by' => $cleanName, 'relation' => $record->collector_relation],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $record->refresh();
    }

    /**
     * Check graduation result enforcing schedule release gate + owner/PIN verification (M4-LIF-03).
     *
     * @return array<string, mixed>
     */
    public function checkPortalResult(int $schoolId, string $nisn, string $pin, ?User $actor = null): array
    {
        $cleanNisn = preg_replace('/\D+/', '', trim($nisn)) ?? '';
        $cleanPin = trim($pin);

        /** @var Student|null $student */
        $student = Student::query()->where('school_id', $schoolId)->where('nisn', $cleanNisn)->first();
        if ($student === null) {
            return ['found' => false, 'released' => false, 'message' => 'Data kelulusan tidak ditemukan untuk NISN tersebut.'];
        }

        /** @var GraduationRecord|null $record */
        $record = GraduationRecord::query()
            ->with('batch')
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if ($record === null || $record->batch === null) {
            return ['found' => false, 'released' => false, 'message' => 'Belum ada penetapan kelulusan untuk siswa ini.'];
        }

        if (! $this->isBatchReleased($record->batch)) {
            return [
                'found' => true,
                'released' => false,
                'release_at' => $record->batch->release_at?->toIso8601String(),
                'message' => 'Pengumuman kelulusan belum dibuka sesuai jadwal rilis resmi sekolah.',
            ];
        }

        $isOwnerUser = $actor !== null && (int) $actor->school_id === $schoolId && $this->accessScope->canAccessStudentScope($actor, $schoolId, (int) $student->id);
        $isPinValid = $cleanPin !== '' && Hash::check($cleanPin, (string) $record->access_pin_hash);

        if (! $isOwnerUser && ! $isPinValid) {
            return [
                'found' => false,
                'released' => true,
                'message' => 'PIN kelulusan tidak valid.',
            ];
        }

        return [
            'found' => true,
            'released' => true,
            'record' => [
                'id' => (int) $record->id,
                'batch_title' => $record->batch->title,
                'student_name' => $student->name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'decision' => $record->decision,
                'skl_number' => $record->skl_number,
                'average_score' => $record->average_score !== null ? (float) $record->average_score : null,
                'has_skl_pdf' => $record->skl_document_id !== null,
                'original_collected_at' => $record->original_collected_at?->toIso8601String(),
            ],
            'message' => 'Hasil kelulusan tersedia.',
        ];
    }

    public function canUserDownloadSkl(?User $user, int $schoolId, GraduationRecord $record, bool $hasVerifiedSession): bool
    {
        if ((int) $record->school_id !== $schoolId || $record->batch === null) {
            return false;
        }

        if ($user !== null && (int) $user->school_id === $schoolId && $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PRINCIPAL->value, RoleEnum::SIGNATORY->value])) {
            return true;
        }

        if (! $this->isBatchReleased($record->batch)) {
            return false;
        }

        if ($user !== null && (int) $user->school_id === $schoolId && $this->accessScope->canAccessStudentScope($user, $schoolId, (int) $record->student_id)) {
            return true;
        }

        return $hasVerifiedSession;
    }

    public function isBatchReleased(GraduationBatch $batch): bool
    {
        return $batch->isReleased();
    }

    public function releaseDueGraduations(): void
    {
        GraduationRecord::query()
            ->where('decision', 'graduated')
            ->whereHas('batch', fn ($query) => $query->where('is_published', true)
                ->where(fn ($release) => $release->whereNull('release_at')->orWhere('release_at', '<=', now())))
            ->where(fn ($query) => $query
                ->whereHas('student', fn ($student) => $student->where('status', StudentStatusEnum::ACTIVE->value))
                ->orWhereHas('enrollment', fn ($enrollment) => $enrollment->where('status', EnrollmentStatusEnum::ACTIVE->value)))
            ->eachById(function (GraduationRecord $candidate): void {
                DB::transaction(function () use ($candidate): void {
                    $batch = GraduationBatch::query()->whereKey($candidate->batch_id)->lockForUpdate()->first();
                    if ($batch === null || ! $batch->isReleased()) {
                        return;
                    }

                    $record = $batch->records()->with(['student', 'enrollment'])->find($candidate->id);
                    if ($record?->decision !== 'graduated' || $record->student === null) {
                        return;
                    }

                    $actor = User::query()->find($batch->published_by_user_id);
                    if ($actor === null) {
                        report(new \RuntimeException("Graduation batch {$batch->id} has no publishing user for its lifecycle audit."));

                        return;
                    }

                    $this->applyGraduatedStudentState($actor, (int) $batch->school_id, $record->student, $record->enrollment);
                });
            });
    }

    private function applyGraduatedStudentState(User $actor, int $schoolId, Student $student, ?Enrollment $enrollment): void
    {
        $student = Student::query()->where('school_id', $schoolId)->whereKey($student->id)->lockForUpdate()->firstOrFail();
        if ($enrollment !== null) {
            $enrollment = Enrollment::query()->whereKey($enrollment->id)->lockForUpdate()->first();
        }

        if ($student->status === StudentStatusEnum::ACTIVE->value) {
            $student->forceFill(['status' => StudentStatusEnum::GRADUATED->value])->save();
        }
        if ($enrollment !== null && $enrollment->status === EnrollmentStatusEnum::ACTIVE->value) {
            $enrollment->forceFill(['status' => EnrollmentStatusEnum::GRADUATED->value])->save();
        }
        $alreadyLogged = StudentLifecycleEvent::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('action', 'graduated')
            ->lockForUpdate()
            ->first(['id']);
        if ($alreadyLogged === null) {
            StudentLifecycleEvent::query()->create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'source_enrollment_id' => $enrollment?->id,
                'target_enrollment_id' => null,
                'action' => 'graduated',
                'effective_date' => $this->schoolScope->localNow($schoolId)->toDateString(),
                'notes' => 'Kelulusan resmi batch kelulusan.',
                'recorded_by' => $actor->id,
            ]);
        }
    }

    private function issueSklDocumentInternal(User $actor, GraduationBatch $batch, GraduationRecord $record): void
    {
        $schoolId = (int) $batch->school_id;
        $record->loadMissing('student');
        $student = $record->student;
        $school = $this->schoolScope->getSchoolDetails($schoolId);

        $ticket = $this->ticketService->issueTicket($schoolId, [
            'service_type' => 'skl',
            'prefix' => 'SKL',
            'subject_student_id' => $record->student_id,
            'applicant_snapshot' => [
                'student_name' => $student?->name ?? 'Lulusan',
                'skl_number' => $record->skl_number,
                'graduation_record_id' => $record->id,
                'batch_title' => $batch->title,
            ],
            'internal_status' => 'issued',
            'public_summary' => sprintf('SKL %s diterbitkan resmi.', $record->skl_number),
        ], $actor);

        $verificationToken = Str::random(40);
        $verifyUrl = rtrim((string) config('app.url'), '/').'/verifikasi?code='.urlencode($verificationToken);
        $writer = new Writer(new ImageRenderer(new RendererStyle(160, 2), new SvgImageBackEnd));
        $qrVerify = base64_encode($writer->writeString($verifyUrl));

        $pdfBytes = Pdf::loadView('academic.graduation-skl', [
            'school' => $school,
            'batch' => $batch,
            'record' => $record,
            'student' => $student,
            'documentNumber' => $record->skl_number,
            'verificationToken' => $verificationToken,
            'verifyUrl' => $verifyUrl,
            'qrVerify' => $qrVerify,
        ])->setPaper('a4')->output();

        $doc = $this->documentService->issueGeneratedDocument(
            schoolId: $schoolId,
            bytes: $pdfBytes,
            payload: [
                'document_kind' => 'skl',
                'number_prefix' => $batch->skl_prefix ?: 'SKL',
                'template_key' => 'graduation_skl',
                'template_version' => (int) $record->reissue_count + 1,
                'verification_token' => $verificationToken,
                'original_name' => sprintf('skl-%s.pdf', preg_replace('/[^A-Za-z0-9_-]/', '', (string) $record->skl_number)),
                'signatory_name' => $batch->signatory_name ?: $actor->name,
                'signatory_title' => $batch->signatory_title ?: 'Kepala Sekolah',
                'signature_mode' => 'image',
                'service_ticket_id' => (int) $ticket->id,
            ],
            actor: $actor,
        );

        $record->forceFill(['skl_document_id' => (int) $doc->id])->save();
    }
}
