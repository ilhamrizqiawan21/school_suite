<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AlumniProfile;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Shared\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alumni portal, tracer study updates, publication consent controls,
 * admin moderation (`lif.alumni.moderate`), filtered directory, and E-KTA issuance (M4-LIF-07).
 */
class AlumniService
{
    public const STATUS_LABELS = [
        'studying' => 'Kuliah / Studi Lanjut',
        'working' => 'Bekerja',
        'entrepreneur' => 'Wirausaha',
        'job_seeking' => 'Mencari Pekerjaan',
        'other' => 'Aktivitas Lainnya',
    ];

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create or update an alumni's own profile with granular directory publication opt-in flags.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertSelfProfile(
        int $schoolId,
        array $data,
        ?User $user = null,
        ?int $externalApplicantId = null,
        ?UploadedFile $proofFile = null,
    ): AlumniProfile {
        if ($user === null && $externalApplicantId === null) {
            abort(403, 'Silakan masuk sebagai akun alumni atau pengguna alumni terlebih dahulu.');
        }

        return DB::transaction(function () use ($schoolId, $data, $user, $externalApplicantId, $proofFile): AlumniProfile {
            $existing = $this->findOwnedProfile($schoolId, $user, $externalApplicantId);

            $nisn = isset($data['nisn']) && trim((string) $data['nisn']) !== '' ? trim((string) $data['nisn']) : null;
            $nis = isset($data['nis']) && trim((string) $data['nis']) !== '' ? trim((string) $data['nis']) : null;
            $studentId = $existing?->student_id;

            if ($studentId === null && ($nisn !== null || $nis !== null)) {
                $matchedStudent = Student::query()
                    ->where('school_id', $schoolId)
                    ->where(function ($q) use ($nisn, $nis): void {
                        if ($nisn !== null) {
                            $q->where('nisn', $nisn);
                        }
                        if ($nis !== null) {
                            $q->orWhere('nis', $nis);
                        }
                    })
                    ->first();
                $studentId = $matchedStudent?->id;
            }

            $proofDocId = $existing?->proof_document_id;
            if ($proofFile instanceof UploadedFile) {
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $proofFile,
                    documentKind: 'attachment',
                    ticket: null,
                    uploader: $user,
                );
                $proofDocId = (int) $doc->id;
            }

            $payload = [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'external_applicant_id' => $externalApplicantId ?? $existing?->external_applicant_id,
                'user_id' => $user?->id ?? $existing?->user_id,
                'nis' => $nis,
                'nisn' => $nisn,
                'full_name' => trim((string) $data['full_name']),
                'graduation_year' => (int) $data['graduation_year'],
                'major_or_class' => isset($data['major_or_class']) ? trim((string) $data['major_or_class']) ?: null : null,
                'current_status' => (string) ($data['current_status'] ?? 'studying'),
                'institution_or_company' => isset($data['institution_or_company']) ? trim((string) $data['institution_or_company']) ?: null : null,
                'position_or_program' => isset($data['position_or_program']) ? trim((string) $data['position_or_program']) ?: null : null,
                'career_notes' => isset($data['career_notes']) ? trim((string) $data['career_notes']) ?: null : null,
                'scholarship_info' => isset($data['scholarship_info']) ? trim((string) $data['scholarship_info']) ?: null : null,
                'testimonial' => isset($data['testimonial']) ? trim((string) $data['testimonial']) ?: null : null,
                'phone' => isset($data['phone']) ? trim((string) $data['phone']) ?: null : null,
                'email' => isset($data['email']) ? trim((string) $data['email']) ?: null : null,
                'proof_document_id' => $proofDocId,
                'opt_in_directory' => (bool) ($data['opt_in_directory'] ?? false),
                'opt_in_contact' => (bool) ($data['opt_in_contact'] ?? false),
                'opt_in_career' => (bool) ($data['opt_in_career'] ?? false),
                'opt_in_testimonial' => (bool) ($data['opt_in_testimonial'] ?? false),
                // Profile edits requiring public display go back to pending moderation cleanly
                'moderation_status' => $existing?->moderation_status === 'approved' && ! empty($data['opt_in_directory'])
                    ? 'pending'
                    : ($existing?->moderation_status ?? 'pending'),
            ];

            if ($existing !== null) {
                $existing->forceFill($payload)->save();
                $profile = $existing->refresh();
            } else {
                $profile = AlumniProfile::query()->create($payload);
            }

            $this->audit->log(
                eventType: 'academic.alumni.profile_updated',
                targetType: 'AlumniProfile',
                targetId: (string) $profile->id,
                newValues: ['graduation_year' => $profile->graduation_year, 'current_status' => $profile->current_status],
                actor: $user,
                schoolId: $schoolId,
            );

            return $profile;
        });
    }

    public function moderate(User $actor, AlumniProfile $profile, string $status, ?string $note = null, bool $issueEkta = true): AlumniProfile
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $profile->school_id === $schoolId, 404);

        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['moderation_status' => 'Keputusan moderasi harus disetujui atau ditolak.']);
        }

        if ($status === 'rejected' && trim((string) $note) === '') {
            throw ValidationException::withMessages(['moderation_note' => 'Alasan penolakan moderasi wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $profile, $status, $note, $issueEkta): AlumniProfile {
            /** @var AlumniProfile $locked */
            $locked = AlumniProfile::query()->where('school_id', $schoolId)->whereKey($profile->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'moderation_status' => $status,
                'moderation_note' => $note !== null ? trim($note) ?: null : null,
                'moderated_by_user_id' => $actor->id,
                'moderated_at' => now(),
            ])->save();

            if ($status === 'approved' && $issueEkta && $locked->ekta_document_id === null) {
                $this->issueEktaInternal($actor, $locked);
            }

            $this->audit->log(
                eventType: "academic.alumni.moderated_{$status}",
                targetType: 'AlumniProfile',
                targetId: (string) $locked->id,
                newValues: ['moderation_status' => $status, 'note' => $locked->moderation_note],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Public alumni directory: strictly excludes non-approved or non-opt-in records and strips unconsented fields.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function publicDirectory(int $schoolId, array $filters = []): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $year = isset($filters['graduation_year']) && (int) $filters['graduation_year'] > 0 ? (int) $filters['graduation_year'] : null;
        $status = trim((string) ($filters['current_status'] ?? ''));

        $rows = AlumniProfile::query()
            ->where('school_id', $schoolId)
            ->where('moderation_status', 'approved')
            ->where('opt_in_directory', true)
            ->when($year !== null, fn ($q) => $q->where('graduation_year', $year))
            ->when($status !== '' && array_key_exists($status, self::STATUS_LABELS), fn ($q) => $q->where('current_status', $status))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(fn ($sub) => $sub
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('institution_or_company', 'like', "%{$search}%")
                    ->orWhere('scholarship_info', 'like', "%{$search}%"));
            })
            ->orderByDesc('graduation_year')
            ->orderBy('full_name')
            ->limit(80)
            ->get();

        return $rows->map(function (AlumniProfile $p): array {
            return [
                'id' => (int) $p->id,
                'full_name' => $p->full_name,
                'graduation_year' => (int) $p->graduation_year,
                'major_or_class' => $p->major_or_class,
                'current_status' => $p->current_status,
                'status_label' => self::STATUS_LABELS[$p->current_status] ?? $p->current_status,
                'institution_or_company' => $p->opt_in_career ? $p->institution_or_company : null,
                'position_or_program' => $p->opt_in_career ? $p->position_or_program : null,
                'scholarship_info' => $p->opt_in_career ? $p->scholarship_info : null,
                'testimonial' => $p->opt_in_testimonial ? $p->testimonial : null,
                'contact_email' => $p->opt_in_contact ? $p->email : null,
                'contact_phone' => $p->opt_in_contact ? $p->phone : null,
            ];
        })->values()->all();
    }

    public function findOwnedProfile(int $schoolId, ?User $user, ?int $externalApplicantId): ?AlumniProfile
    {
        if ($user !== null) {
            return AlumniProfile::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->first();
        }

        if ($externalApplicantId !== null) {
            return AlumniProfile::query()
                ->where('school_id', $schoolId)
                ->where('external_applicant_id', $externalApplicantId)
                ->first();
        }

        return null;
    }

    private function issueEktaInternal(User $actor, AlumniProfile $profile): void
    {
        $schoolId = (int) $profile->school_id;
        $school = $this->schoolScope->getSchoolDetails($schoolId);
        $ektaNumber = $profile->ekta_number ?: sprintf('EKTA-%d-%s', $profile->graduation_year, Str::upper(Str::random(6)));

        $ticket = $this->ticketService->issueTicket($schoolId, [
            'service_type' => 'alumni',
            'prefix' => 'EKT',
            'subject_student_id' => $profile->student_id,
            'applicant_snapshot' => [
                'full_name' => $profile->full_name,
                'ekta_number' => $ektaNumber,
                'graduation_year' => $profile->graduation_year,
            ],
            'internal_status' => 'issued',
            'public_summary' => sprintf('Kartu Alumni Elektronik (%s) resmi aktif.', $ektaNumber),
        ], $actor);

        $verificationToken = Str::random(40);

        $pdfBytes = Pdf::loadView('academic.alumni-ekta', [
            'school' => $school,
            'profile' => $profile->fill(['ekta_number' => $ektaNumber]),
            'documentNumber' => $ektaNumber,
            'verificationToken' => $verificationToken,
        ])->setPaper('a4')->output();

        $doc = $this->documentService->issueGeneratedDocument(
            schoolId: $schoolId,
            bytes: $pdfBytes,
            payload: [
                'document_kind' => 'ekta',
                'number_prefix' => 'EKTA',
                'template_key' => 'alumni_ekta',
                'template_version' => 1,
                'verification_token' => $verificationToken,
                'original_name' => sprintf('ekta-%s.pdf', preg_replace('/[^A-Za-z0-9_-]/', '', $ektaNumber)),
                'signatory_name' => $actor->name,
                'signatory_title' => 'Pengelola Alumni / Operator',
                'signature_mode' => 'image',
                'service_ticket_id' => (int) $ticket->id,
            ],
            actor: $actor,
        );

        $profile->forceFill([
            'ekta_number' => $ektaNumber,
            'ekta_document_id' => (int) $doc->id,
        ])->save();
    }
}
