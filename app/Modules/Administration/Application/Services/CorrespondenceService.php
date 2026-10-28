<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Application\Contracts\ElectronicSignatureProvider;
use App\Modules\Administration\Application\Jobs\ProcessCorrespondenceSignature;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceDocumentVersion;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatory;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatureRequest;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CorrespondenceService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SchoolScopeService $schoolScope,
        private readonly ElectronicSignatureProvider $signatureProvider,
    ) {}

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
        return hash('sha256', $this->renderPdfBytes($record, $schoolSnapshot));
    }

    public function renderPdfBytes(CorrespondenceRecord $record, ?array $schoolSnapshot = null): string
    {
        $renderRecord = clone $record;
        $renderRecord->document_hash = null;
        $school = $schoolSnapshot ?? $renderRecord->school_snapshot ?? $this->getSchoolLegalSnapshot((int) $renderRecord->school_id);
        $qr = null;
        if ($renderRecord->verification_token) {
            $writer = new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd));
            $qr = base64_encode($writer->writeString(
                rtrim((string) config('app.url'), '/').route('administration.correspondence.verify', $renderRecord->verification_token, false),
            ));
        }

        $pdf = Pdf::loadView('correspondence.letter', ['record' => $renderRecord, 'school' => $school, 'qr' => $qr])
            ->setPaper('a4');
        $pdf->addInfo([
            'CreationDate' => 'D:20000101000000Z',
            'ModDate' => 'D:20000101000000Z',
        ]);

        return $pdf->output();
    }

    public function publishedPdfBytes(CorrespondenceRecord $record): string
    {
        $version = $record->documentVersions()->whereIn('kind', ['signed', 'manual_scan', 'issued'])->latest('version')->first();
        if ($version && Storage::disk('local')->exists($version->storage_path)) {
            return (string) Storage::disk('local')->get($version->storage_path);
        }

        return $this->renderPdfBytes($record);
    }

    public function workflowPdfBytes(CorrespondenceRecord $record): string
    {
        $kinds = in_array($record->status, ['issued', 'sent', 'archived'], true)
            ? ['signed', 'manual_scan', 'issued']
            : ['unsigned'];
        $version = $record->documentVersions()->whereIn('kind', $kinds)->latest('version')->first();

        if ($version && Storage::disk('local')->exists($version->storage_path)) {
            return (string) Storage::disk('local')->get($version->storage_path);
        }

        return $this->renderPdfBytes($record);
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
                    'letter_year' => filled($data['letter_date'] ?? null) ? (int) substr((string) $data['letter_date'], 0, 4) : null,
                    'school_id' => $schoolId,
                    'created_by' => $actor->id,
                    'status' => $data['letter_type'] === 'incoming' && filled($data['disposition'] ?? null)
                        ? 'disposition'
                        : ($data['letter_type'] === 'incoming' ? 'received' : 'draft'),
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
        $issuedPdf = null;
        DB::transaction(function () use ($actor, $record, $status, $disposition, &$issuedPdf): void {
            $record = CorrespondenceRecord::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($record->id);
            $this->assertOwnedByActor($actor, $record);
            $allowedTransitions = $record->letter_type === 'incoming'
                ? ['received' => ['disposition'], 'disposition' => ['done'], 'done' => ['archived']]
                : (filled($record->letter_body)
                    ? ($record->is_legacy
                        ? ['draft' => ['issued'], 'approved' => ['issued'], 'issued' => ['sent'], 'sent' => ['archived']]
                        : ['issued' => ['sent'], 'sent' => ['archived']])
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
                $record->verification_expires_at = now()->addDays((int) config('correspondence.verification_ttl_days', 365));
                $record->verification_revoked_at = null;
                if ($record->signatory_id) {
                    $signatory = CorrespondenceSignatory::query()->where('school_id', $record->school_id)->find($record->signatory_id);
                    $record->signer_snapshot = $signatory?->only(['name', 'title', 'employee_number', 'rank']);
                }
            }

            $oldValues = ['status' => $record->status, 'disposition' => $record->disposition];
            $record->status = $status;
            if ($disposition !== null) {
                $record->disposition = $disposition;
            }
            if ($status === 'archived') {
                $record->archived_at = now();
            }
            $issuedPdf = null;
            if ($status === 'issued' && filled($record->letter_body)) {
                $issuedPdf = $this->renderPdfBytes($record, $record->school_snapshot ?? []);
                $record->document_hash = hash('sha256', $issuedPdf);
            }
            $record->save();

            if ($issuedPdf !== null) {
                $this->storeDocumentVersion($actor, $record, $issuedPdf, 'issued', $record->signature_method, true);
            }

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
            if (! $record->approval_required || $record->letter_type !== 'outgoing' || $record->status !== 'pending_approval') {
                throw ValidationException::withMessages(['approval' => 'Surat ini tidak menunggu persetujuan.']);
            }

            $record->approval_status = $approved ? 'approved' : 'rejected';
            $record->approved_by = $actor->id;
            $record->approved_at = now();
            $record->approval_note = $note;
            if ($record->status === 'pending_approval') {
                $record->status = $approved ? 'approved' : 'draft';
            }
            $record->save();
            if ($approved) {
                $this->storeDocumentVersion($actor, $record, $this->renderPdfBytes($record), 'approved', $record->signature_method);
            }

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

    public function revokeVerification(User $actor, CorrespondenceRecord $record): void
    {
        DB::transaction(function () use ($actor, $record): void {
            $record = CorrespondenceRecord::query()
                ->where('school_id', $actor->school_id)
                ->lockForUpdate()
                ->findOrFail($record->id);
            $this->assertOwnedByActor($actor, $record);
            if (blank($record->verification_token) || $record->verification_revoked_at !== null) {
                throw ValidationException::withMessages([
                    'verification' => 'Verifikasi surat ini sudah tidak aktif.',
                ]);
            }

            $record->verification_revoked_at = now();
            $record->save();
            $this->audit->log(
                'administration.correspondence.verification_revoked',
                'CorrespondenceRecord',
                (string) $record->id,
                ['reason' => 'manual_revocation'],
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
            if ($record->letter_type !== 'outgoing' || ! in_array($record->status, ['draft', 'approved'], true) || blank($record->letter_body)) {
                throw ValidationException::withMessages(['status' => 'Hanya draf surat keluar yang dapat diubah.']);
            }
            $old = $record->only(array_keys($data));
            $wasApprovalRequired = (bool) $record->approval_required;
            $materialFields = array_diff(array_keys($data), ['approval_required']);
            $record->fill($data);
            if (array_key_exists('letter_date', $data)) {
                $record->letter_year = filled($data['letter_date']) ? (int) substr((string) $data['letter_date'], 0, 4) : null;
            }
            $materialChanged = collect($materialFields)->contains(fn (string $field): bool => $record->isDirty($field));
            $approvalRequired = (bool) $record->approval_required;

            if (! $approvalRequired) {
                $record->approval_status = 'not_required';
                $record->approved_by = null;
                $record->approved_at = null;
                $record->approval_note = null;
            } elseif (! $wasApprovalRequired || $materialChanged) {
                $record->approval_status = 'pending';
                $record->status = 'draft';
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
        if ($actor->school_id === null || (int) $actor->school_id !== (int) $record->school_id) {
            abort(404);
        }
    }

    public function logPdfRendered(User $actor, CorrespondenceRecord $record): void
    {
        $this->audit->log(
            'administration.correspondence.pdf_rendered',
            'CorrespondenceRecord',
            (string) $record->id,
            ['status' => $record->status, 'document_hash' => $record->document_hash],
            actor: $actor,
            schoolId: (int) $record->school_id,
        );
    }

    public function logVerificationAttempt(?CorrespondenceRecord $record, string $eventType, string $reason): void
    {
        $this->audit->log(
            $eventType,
            'CorrespondenceRecord',
            $record?->id !== null ? (string) $record->id : null,
            ['reason' => $reason],
            schoolId: $record?->school_id !== null ? (int) $record->school_id : null,
        );
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

    /** @param array<string, mixed> $data */
    public function createSignatory(User $actor, array $data): CorrespondenceSignatory
    {
        $profile = CorrespondenceSignatory::create([...$data, 'school_id' => $actor->school_id]);
        $this->audit->log('administration.correspondence.signatory_created', 'CorrespondenceSignatory', (string) $profile->id,
            ['title' => $profile->title], actor: $actor, schoolId: (int) $actor->school_id);

        return $profile;
    }

    public function submitForApproval(User $actor, CorrespondenceRecord $record): void
    {
        DB::transaction(function () use ($actor, $record): void {
            $record = CorrespondenceRecord::query()->where('school_id', $actor->school_id)->lockForUpdate()->findOrFail($record->id);
            if ($record->letter_type !== 'outgoing' || $record->status !== 'draft' || ! $record->approval_required || blank($record->letter_body)) {
                throw ValidationException::withMessages(['approval' => 'Draf belum siap diajukan untuk persetujuan.']);
            }
            $record->update(['status' => 'pending_approval', 'approval_status' => 'pending']);
            $this->audit->log('administration.correspondence.approval_requested', 'CorrespondenceRecord', (string) $record->id,
                actor: $actor, schoolId: (int) $record->school_id);
        });
    }

    public function prepareManualSignature(User $actor, CorrespondenceRecord $record): CorrespondenceDocumentVersion
    {
        return DB::transaction(function () use ($actor, $record): CorrespondenceDocumentVersion {
            $record = CorrespondenceRecord::query()->where('school_id', $actor->school_id)->lockForUpdate()->findOrFail($record->id);
            $this->assertReadyForSignature($record);
            $this->initializeVerification($record);
            if ($record->signatory_id) {
                $signatory = CorrespondenceSignatory::query()->where('school_id', $actor->school_id)
                    ->whereKey($record->signatory_id)->where('is_active', true)->first();
                if (! $signatory) {
                    throw ValidationException::withMessages(['signatory_id' => 'Pilih pejabat penandatangan yang aktif.']);
                }
                $record->signer_snapshot = $signatory->only(['name', 'title', 'employee_number', 'rank']);
            }
            $record->status = 'awaiting_signature';
            $record->signature_method = 'manual';
            $record->save();
            $version = $this->storeDocumentVersion($actor, $record, $this->renderPdfBytes($record), 'unsigned', 'manual');
            $this->audit->log('administration.correspondence.manual_signature_prepared', 'CorrespondenceRecord', (string) $record->id,
                ['version' => $version->version], actor: $actor, schoolId: (int) $record->school_id);

            return $version;
        });
    }

    public function finalizeManualSignature(User $actor, CorrespondenceRecord $record, UploadedFile $scan): CorrespondenceDocumentVersion
    {
        $bytes = (string) file_get_contents($scan->getRealPath());
        if (! str_starts_with($bytes, '%PDF-')) {
            throw ValidationException::withMessages(['signed_document' => 'Hasil pindai harus berupa PDF yang valid.']);
        }

        return DB::transaction(function () use ($actor, $record, $bytes): CorrespondenceDocumentVersion {
            $record = CorrespondenceRecord::query()->where('school_id', $actor->school_id)->lockForUpdate()->findOrFail($record->id);
            if ($record->status !== 'awaiting_signature' || $record->signature_method !== 'manual') {
                throw ValidationException::withMessages(['signed_document' => 'Surat tidak sedang menunggu tanda tangan basah.']);
            }
            $version = $this->storeDocumentVersion($actor, $record, $bytes, 'manual_scan', 'manual', true);
            $record->status = 'issued';
            $record->document_hash = $version->sha256;
            $record->save();
            $this->audit->log('administration.correspondence.manual_signature_finalized', 'CorrespondenceRecord', (string) $record->id,
                ['version' => $version->version, 'sha256' => $version->sha256], actor: $actor, schoolId: (int) $record->school_id);

            return $version;
        });
    }

    public function requestBsreSignature(User $actor, CorrespondenceRecord $record): CorrespondenceSignatureRequest
    {
        $signatureRequest = DB::transaction(function () use ($actor, $record): CorrespondenceSignatureRequest {
            $record = CorrespondenceRecord::query()->where('school_id', $actor->school_id)->lockForUpdate()->findOrFail($record->id);
            $this->assertReadyForSignature($record);
            $signatory = CorrespondenceSignatory::query()->where('school_id', $actor->school_id)
                ->whereKey($record->signatory_id)->where('is_active', true)->first();
            if (! $signatory) {
                throw ValidationException::withMessages(['signatory_id' => 'Pilih pejabat penandatangan yang aktif.']);
            }
            $this->initializeVerification($record);
            $record->status = 'signature_pending';
            $record->signature_method = 'bsre';
            $record->signer_snapshot = $signatory->only(['name', 'title', 'employee_number', 'rank']);
            $record->save();
            $version = $this->storeDocumentVersion($actor, $record, $this->renderPdfBytes($record), 'unsigned', 'bsre');

            return CorrespondenceSignatureRequest::firstOrCreate(
                ['document_version_id' => $version->id, 'provider' => 'bsre'],
                ['school_id' => $record->school_id, 'correspondence_record_id' => $record->id,
                    'signatory_id' => $signatory->id, 'idempotency_key' => (string) Str::uuid(), 'status' => 'pending'],
            );
        });
        $this->audit->log('administration.correspondence.bsre_requested', 'CorrespondenceSignatureRequest', (string) $signatureRequest->id,
            ['record_id' => $signatureRequest->correspondence_record_id, 'status' => $signatureRequest->status], actor: $actor, schoolId: (int) $actor->school_id);
        ProcessCorrespondenceSignature::dispatch($signatureRequest->id);

        return $signatureRequest;
    }

    public function retrySignature(User $actor, CorrespondenceSignatureRequest $signatureRequest): void
    {
        abort_unless((int) $actor->school_id === (int) $signatureRequest->school_id, 404);
        if (! in_array($signatureRequest->status, ['failed', 'pending'], true)) {
            throw ValidationException::withMessages(['signature' => 'Permintaan tanda tangan ini tidak dapat diulang.']);
        }
        $signatureRequest->update(['status' => 'pending', 'error_code' => null]);
        $signatureRequest->record()->update(['status' => 'signature_pending']);
        $this->audit->log('administration.correspondence.bsre_retried', 'CorrespondenceSignatureRequest', (string) $signatureRequest->id,
            ['attempts' => $signatureRequest->attempts], actor: $actor, schoolId: (int) $actor->school_id);
        ProcessCorrespondenceSignature::dispatch($signatureRequest->id);
    }

    public function processSignatureRequest(int $requestId): void
    {
        $request = CorrespondenceSignatureRequest::query()->with(['record.signatory', 'record.creator', 'documentVersion'])->findOrFail($requestId);
        if ($request->status === 'signed') {
            return;
        }
        $request->increment('attempts');
        $request->update(['status' => 'processing', 'last_attempted_at' => now(), 'error_code' => null]);
        try {
            $bytes = (string) Storage::disk('local')->get($request->documentVersion->storage_path);
            $result = $this->signatureProvider->sign($bytes, $request->record->signatory, $request->idempotency_key, ['record_id' => $request->record->id]);
            if (! str_starts_with($result->signedPdfBytes, '%PDF-')) {
                throw new \RuntimeException('signed_pdf_invalid');
            }
            DB::transaction(function () use ($request, $result): void {
                $version = $this->storeDocumentVersion($request->record->creator, $request->record, $result->signedPdfBytes, 'signed', 'bsre', true);
                $request->update(['provider_request_id' => $result->providerRequestId, 'status' => 'signed',
                    'certificate_metadata' => $result->certificateMetadata, 'signed_at' => now()]);
                $request->record->update(['status' => 'issued', 'document_hash' => $version->sha256]);
            });
            $this->audit->log('administration.correspondence.bsre_signed', 'CorrespondenceSignatureRequest', (string) $request->id,
                ['provider_request_id' => $result->providerRequestId], actor: $request->record->creator, schoolId: (int) $request->school_id);
        } catch (Throwable $exception) {
            $request->update(['status' => 'failed', 'error_code' => Str::limit(class_basename($exception), 100, '')]);
            $request->record->update(['status' => 'signature_failed']);
            $this->audit->log('administration.correspondence.bsre_failed', 'CorrespondenceSignatureRequest', (string) $request->id,
                ['error_code' => $request->error_code], actor: $request->record->creator, schoolId: (int) $request->school_id);
            throw $exception;
        }
    }

    public function verificationIntegrity(CorrespondenceRecord $record): string
    {
        if (blank($record->document_hash)) {
            return 'unverifiable';
        }

        $bytes = $this->publishedPdfBytes($record);
        if ($bytes === '') {
            return 'unverifiable';
        }

        return hash_equals((string) $record->document_hash, hash('sha256', $bytes)) ? 'valid' : 'changed';
    }

    private function assertReadyForSignature(CorrespondenceRecord $record): void
    {
        if ($record->letter_type !== 'outgoing' || ! in_array($record->status, ['draft', 'approved'], true) || blank($record->letter_body)) {
            throw ValidationException::withMessages(['status' => 'Hanya draf surat keluar lengkap yang dapat ditandatangani.']);
        }
        if ($record->approval_required && $record->approval_status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Surat harus disetujui sebelum ditandatangani.']);
        }
    }

    private function initializeVerification(CorrespondenceRecord $record): void
    {
        $record->school_snapshot = $this->getSchoolLegalSnapshot((int) $record->school_id);
        $record->verification_token ??= bin2hex(random_bytes(32));
        $record->verification_expires_at = now()->addDays((int) config('correspondence.verification_ttl_days', 365));
        $record->verification_revoked_at = null;
    }

    private function storeDocumentVersion(User $actor, CorrespondenceRecord $record, string $bytes, string $kind, ?string $signatureMethod, bool $final = false): CorrespondenceDocumentVersion
    {
        $next = ((int) $record->documentVersions()->max('version')) + 1;
        $path = "correspondence/{$record->school_id}/documents/{$record->id}/v{$next}-{$kind}.pdf";
        Storage::disk('local')->put($path, $bytes);

        return CorrespondenceDocumentVersion::create([
            'school_id' => $record->school_id, 'correspondence_record_id' => $record->id, 'version' => $next,
            'kind' => $kind, 'storage_path' => $path, 'original_name' => "surat-{$record->id}-v{$next}.pdf",
            'size_bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'signature_method' => $signatureMethod,
            'finalized_at' => $final ? now() : null, 'created_by' => $actor->id,
        ]);
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
