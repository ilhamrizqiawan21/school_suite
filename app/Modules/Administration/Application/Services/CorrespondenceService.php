<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CorrespondenceService
{
    public function __construct(private readonly AuditLogger $audit, private readonly SchoolScopeService $schoolScope) {}

    /** @return array<string, mixed> */
    public function getSchoolLegalSnapshot(int $schoolId): array
    {
        $snapshot = $this->schoolScope->getSchoolLegalSnapshot($schoolId);
        $logo = $this->schoolScope->getSchoolLogoDataUri($schoolId);
        if ($logo !== null) {
            $snapshot['logo_data_uri'] = $logo;
        }

        return $snapshot;
    }

    public function documentHash(CorrespondenceRecord $record, array $schoolSnapshot): string
    {
        return hash('sha256', json_encode([
            'letter_template' => $record->letter_template,
            'letter_number' => $record->letter_number,
            'letter_date' => $record->letter_date?->toDateString(),
            'recipient' => $record->recipient,
            'subject' => $record->subject,
            'summary' => $record->summary,
            'letter_body' => $record->letter_body,
            'attachment_note' => $record->attachment_note,
            'signer_name' => $record->signer_name,
            'signer_title' => $record->signer_title,
            'school' => collect($schoolSnapshot)->except('logo_data_uri')->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data, ?UploadedFile $attachment = null): CorrespondenceRecord
    {
        $schoolId = (int) $actor->school_id;
        $storedPath = null;

        try {
            return DB::transaction(function () use ($actor, $data, $attachment, $schoolId, &$storedPath): CorrespondenceRecord {
                $this->schoolScope->lockSchool($schoolId);
                $record = new CorrespondenceRecord([
                    ...$data,
                    'school_id' => $schoolId,
                    'created_by' => $actor->id,
                    'status' => $data['letter_type'] === 'incoming' ? 'received' : 'draft',
                    'approval_status' => ($data['approval_required'] ?? false) ? 'pending' : 'not_required',
                ]);

                if ($attachment) {
                    $storedPath = $this->storeAttachment($attachment, $schoolId);
                    $record->attachment_path = $storedPath;
                    $record->attachment_name = $this->safeOriginalName($attachment);
                }

                $record->save();

                if ($record->letter_type === 'incoming') {
                    $year = $record->received_at?->format('Y') ?? now()->format('Y');
                    $nextNumber = CorrespondenceRecord::query()
                        ->where('school_id', $schoolId)
                        ->where('letter_type', 'incoming')
                        ->where('agenda_number', 'like', "IN/{$year}/%")
                        ->count() + 1;
                    $record->agenda_number = sprintf('IN/%s/%06d', $year, $nextNumber);
                    $record->save();
                }

                $this->audit->log(
                    'administration.correspondence.created',
                    'CorrespondenceRecord',
                    (string) $record->id,
                    [
                        'letter_type' => $record->letter_type,
                        'agenda_number' => $record->agenda_number,
                        'letter_number' => $record->letter_number,
                        'status' => $record->status,
                        'has_attachment' => (bool) $record->attachment_path,
                    ],
                    actor: $actor,
                    schoolId: $schoolId,
                );

                return $record;
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function updateStatus(User $actor, CorrespondenceRecord $record, string $status, ?string $disposition): void
    {
        DB::transaction(function () use ($actor, $record, $status, $disposition): void {
            $record = CorrespondenceRecord::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($record->id);
            $this->assertOwnedByActor($actor, $record);
            $allowedTransitions = $record->letter_type === 'incoming'
                ? ['received' => ['disposition'], 'disposition' => ['done'], 'done' => ['archived']]
                : (filled($record->letter_body)
                    ? ['draft' => ['issued'], 'issued' => ['sent'], 'sent' => ['archived']]
                    : ['draft' => ['sent'], 'sent' => ['archived']]);

            if (! in_array($status, $allowedTransitions[$record->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Perubahan status surat tidak sesuai urutan alur.',
                ]);
            }

            if ($record->letter_type === 'incoming' && $status === 'disposition' && blank($disposition)) {
                throw ValidationException::withMessages([
                    'disposition' => 'Isi disposisi sebelum surat diteruskan.',
                ]);
            }

            if ($status === 'issued' && filled($record->letter_body)) {
                if ($record->approval_required && $record->approval_status !== 'approved') {
                    throw ValidationException::withMessages([
                        'status' => 'Surat harus disetujui kepala sekolah sebelum diterbitkan.',
                    ]);
                }
                $record->school_snapshot = $this->getSchoolLegalSnapshot((int) $record->school_id);
                $record->verification_token = bin2hex(random_bytes(32));
                $record->document_hash = $this->documentHash($record, $record->school_snapshot);
            }

            $oldValues = ['status' => $record->status, 'disposition' => $record->disposition];
            $record->status = $status;
            if ($disposition !== null) {
                $record->disposition = $disposition;
            }
            if ($status === 'archived') {
                $record->archived_at = now();
            }
            $record->save();

            $this->audit->log(
                'administration.correspondence.status_changed',
                'CorrespondenceRecord',
                (string) $record->id,
                ['status' => $record->status, 'disposition' => $record->disposition],
                $oldValues,
                $actor,
                (int) $record->school_id,
            );
        });
    }

    public function approve(User $actor, CorrespondenceRecord $record, bool $approved, ?string $note): void
    {
        DB::transaction(function () use ($actor, $record, $approved, $note): void {
            $record = CorrespondenceRecord::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($record->id);
            abort_unless($actor->hasRole('principal'), 403);
            if (! $record->approval_required || $record->letter_type !== 'outgoing' || $record->status !== 'draft') {
                throw ValidationException::withMessages(['approval' => 'Surat ini tidak menunggu persetujuan.']);
            }

            $record->approval_status = $approved ? 'approved' : 'rejected';
            $record->approved_by = $actor->id;
            $record->approved_at = now();
            $record->approval_note = $note;
            $record->save();

            $this->audit->log(
                $approved ? 'administration.correspondence.approved' : 'administration.correspondence.rejected',
                'CorrespondenceRecord',
                (string) $record->id,
                ['approval_status' => $record->approval_status, 'approval_note' => $record->approval_note],
                actor: $actor,
                schoolId: (int) $record->school_id,
            );
        });
    }

    /** @param array<string, mixed> $data */
    public function updateDraft(User $actor, CorrespondenceRecord $record, array $data): void
    {
        DB::transaction(function () use ($actor, $record, $data): void {
            $record = CorrespondenceRecord::query()->where('school_id', $actor->school_id)->lockForUpdate()->findOrFail($record->id);
            if ($record->letter_type !== 'outgoing' || $record->status !== 'draft' || blank($record->letter_body)) {
                throw ValidationException::withMessages(['status' => 'Hanya draf surat keluar yang dapat diubah.']);
            }
            $old = $record->only(array_keys($data));
            $wasApprovalRequired = (bool) $record->approval_required;
            $materialFields = array_diff(array_keys($data), ['approval_required']);
            $record->fill($data);
            $materialChanged = collect($materialFields)->contains(fn (string $field): bool => $record->isDirty($field));
            $approvalRequired = (bool) $record->approval_required;

            if (! $approvalRequired) {
                $record->approval_status = 'not_required';
                $record->approved_by = null;
                $record->approved_at = null;
                $record->approval_note = null;
            } elseif (! $wasApprovalRequired || $materialChanged) {
                $record->approval_status = 'pending';
                $record->approved_by = null;
                $record->approved_at = null;
                $record->approval_note = null;
            }

            $record->save();
            $this->audit->log('administration.correspondence.draft_updated', 'CorrespondenceRecord', (string) $record->id,
                ['fields' => array_keys($data)], ['fields' => array_keys($old)], $actor, (int) $record->school_id);
        });
    }

    public function assertOwnedByActor(User $actor, CorrespondenceRecord $record): void
    {
        if ((int) $actor->school_id !== (int) $record->school_id) {
            abort(404);
        }
    }

    public function logAttachmentDownload(User $actor, CorrespondenceRecord $record): void
    {
        $this->assertOwnedByActor($actor, $record);
        $this->audit->log(
            'administration.correspondence.attachment_downloaded',
            'CorrespondenceRecord',
            (string) $record->id,
            ['attachment_name' => $record->attachment_name],
            actor: $actor,
            schoolId: (int) $record->school_id,
        );
    }

    private function storeAttachment(UploadedFile $attachment, int $schoolId): string
    {
        return $attachment->storeAs(
            "correspondence/{$schoolId}",
            Str::uuid().'.'.$attachment->extension(),
            'local',
        );
    }

    private function safeOriginalName(UploadedFile $attachment): string
    {
        $name = basename(str_replace('\\', '/', $attachment->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return Str::limit($name !== '' ? $name : 'lampiran', 255, '');
    }
}
