<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\Application\Services\CorrespondenceService;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatory;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceSignatureRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CorrespondenceController extends Controller
{
    public function __construct(private readonly CorrespondenceService $correspondence) {}

    public function index(Request $request): Response
    {
        $user = $this->authorizeViewer($request->user());
        $canManage = $user->hasRole(['admin', 'operator']);
        $filter = $request->string('filter', 'all')->toString();
        abort_unless(in_array($filter, ['all', 'incoming', 'outgoing', 'archived'], true), 404);

        $records = CorrespondenceRecord::query()
            ->where('school_id', $user->school_id)
            ->when($filter === 'incoming', fn ($query) => $query->where('letter_type', 'incoming'))
            ->when($filter === 'outgoing', fn ($query) => $query->where('letter_type', 'outgoing'))
            ->when($filter === 'archived', fn ($query) => $query->where('status', 'archived'))
            ->latest('created_at')
            ->get([
                'id', 'school_id', 'letter_type', 'agenda_number', 'letter_number', 'letter_date', 'received_at',
                'letter_template', 'classification', 'urgency', 'sender', 'recipient', 'subject', 'summary', 'disposition', 'status',
                'approval_required', 'approval_status', 'approved_at', 'approval_note', 'signature_method', 'document_hash',
                'attachment_name', 'attachment_path', 'archived_at', 'created_at',
                'letter_body', 'signer_name', 'signer_title', 'signer_identifier_type', 'signer_identifier', 'attachment_note', 'signatory_id',
                'recipient_address', 'opening_greeting', 'closing_text', 'carbon_copy', 'is_legacy',
            ])
            ->map(function (CorrespondenceRecord $record): array {
                $signatureRequest = CorrespondenceSignatureRequest::query()
                    ->where('school_id', $record->school_id)
                    ->where('correspondence_record_id', $record->id)
                    ->latest('id')->first(['id', 'status', 'error_code']);

                return [
                    'id' => $record->id,
                    'letter_type' => $record->letter_type,
                    'agenda_number' => $record->agenda_number,
                    'letter_number' => $record->letter_number,
                    'letter_template' => $record->letter_template,
                    'classification' => $record->classification,
                    'urgency' => $record->urgency,
                    'letter_date' => $record->letter_date?->toDateString(),
                    'received_at' => $record->received_at?->toDateString(),
                    'sender' => $record->sender,
                    'recipient' => $record->recipient,
                    'subject' => $record->subject,
                    'summary' => $record->summary,
                    'disposition' => $record->disposition,
                    'status' => $record->status,
                    'approval_required' => (bool) $record->approval_required,
                    'approval_status' => $record->approval_status,
                    'approved_at' => $record->approved_at?->toDateTimeString(),
                    'approval_note' => $record->approval_note,
                    'signature_method' => $record->signature_method,
                    'document_hash' => $record->document_hash,
                    'attachment_name' => $record->attachment_name,
                    'has_attachment' => (bool) $record->attachment_path,
                    'has_document' => filled($record->letter_body),
                    'letter_body' => $record->status === 'draft' ? $record->letter_body : null,
                    'signer_name' => $record->status === 'draft' ? $record->signer_name : null,
                    'signer_title' => $record->status === 'draft' ? $record->signer_title : null,
                    'signer_identifier_type' => $record->status === 'draft' ? $record->signer_identifier_type : null,
                    'signer_identifier' => $record->status === 'draft' ? $record->signer_identifier : null,
                    'attachment_note' => $record->status === 'draft' ? $record->attachment_note : null,
                    'signatory_id' => $record->signatory_id,
                    'recipient_address' => $record->status === 'draft' ? $record->recipient_address : null,
                    'opening_greeting' => $record->status === 'draft' ? $record->opening_greeting : null,
                    'closing_text' => $record->status === 'draft' ? $record->closing_text : null,
                    'carbon_copy' => $record->status === 'draft' ? $record->carbon_copy : null,
                    'is_legacy' => (bool) $record->is_legacy,
                    'signature_request_id' => $signatureRequest?->id,
                    'signature_status' => $signatureRequest?->status,
                    'signature_error' => $signatureRequest?->error_code,
                    'archived_at' => $record->archived_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Administration/Correspondence/Index', [
            'records' => $records,
            'filter' => $filter,
            'can_manage' => $canManage,
            'signatories' => CorrespondenceSignatory::query()->where('school_id', $user->school_id)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'title', 'employee_number', 'rank', 'template_scopes']),
            'queue_counts' => [
                'incoming_new' => CorrespondenceRecord::where('school_id', $user->school_id)->where('letter_type', 'incoming')->where('status', 'received')->count(),
                'awaiting_disposition' => CorrespondenceRecord::where('school_id', $user->school_id)->where('letter_type', 'incoming')->where('status', 'disposition')->count(),
                'draft' => CorrespondenceRecord::where('school_id', $user->school_id)->where('letter_type', 'outgoing')->where('status', 'draft')->count(),
                'awaiting_approval' => CorrespondenceRecord::where('school_id', $user->school_id)->where('approval_status', 'pending')->count(),
                'awaiting_signature' => CorrespondenceRecord::where('school_id', $user->school_id)->whereIn('status', ['awaiting_signature', 'signature_pending', 'signature_failed'])->count(),
                'ready_to_send' => CorrespondenceRecord::where('school_id', $user->school_id)->where('status', 'issued')->count(),
                'archived' => CorrespondenceRecord::where('school_id', $user->school_id)->where('status', 'archived')->count(),
            ],
            'status_options' => [
                'incoming' => [
                    ['value' => 'received', 'label' => 'Diterima'],
                    ['value' => 'disposition', 'label' => 'Didisposisikan'],
                    ['value' => 'done', 'label' => 'Selesai'],
                    ['value' => 'archived', 'label' => 'Diarsipkan'],
                ],
                'outgoing' => [
                    ['value' => 'draft', 'label' => 'Draf'],
                    ['value' => 'pending_approval', 'label' => 'Menunggu persetujuan'],
                    ['value' => 'approved', 'label' => 'Disetujui'],
                    ['value' => 'awaiting_signature', 'label' => 'Menunggu tanda tangan'],
                    ['value' => 'signature_pending', 'label' => 'TTE diproses'],
                    ['value' => 'signature_failed', 'label' => 'TTE gagal'],
                    ['value' => 'issued', 'label' => 'Diterbitkan'],
                    ['value' => 'sent', 'label' => 'Terkirim'],
                    ['value' => 'archived', 'label' => 'Diarsipkan'],
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $validated = $request->validate([
            'letter_type' => ['required', Rule::in(['incoming', 'outgoing'])],
            'letter_template' => ['nullable', Rule::in(['official_school', 'invitation', 'assignment', 'statement'])],
            'letter_number' => [
                Rule::requiredIf($request->input('letter_type') === 'outgoing'),
                'nullable', 'string', 'max:100',
                ...($request->input('letter_type') === 'outgoing' ? [
                    Rule::unique('correspondence_records', 'letter_number')
                        ->where(fn ($query) => $query->where('school_id', $user->school_id)->where('letter_type', 'outgoing')
                            ->where('letter_year', (int) substr((string) $request->input('letter_date'), 0, 4))),
                ] : []),
            ],
            'letter_date' => ['nullable', 'date', Rule::requiredIf(filled($request->input('letter_body')))],
            'received_at' => [Rule::requiredIf($request->input('letter_type') === 'incoming'), 'nullable', 'date'],
            'sender' => [Rule::requiredIf($request->input('letter_type') === 'incoming'), 'nullable', 'string', 'max:180'],
            'recipient' => [Rule::requiredIf($request->input('letter_type') === 'outgoing'), 'nullable', 'string', 'max:180'],
            'recipient_address' => ['nullable', 'string', 'max:2000'],
            'opening_greeting' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:200'],
            'classification' => ['nullable', 'string', 'max:40'],
            'urgency' => ['nullable', Rule::in(['ordinary', 'urgent', 'immediate'])],
            'summary' => ['nullable', 'string', 'max:5000'],
            'disposition' => ['nullable', 'string', 'max:3000'],
            'disposition_target' => ['nullable', 'string', 'max:180'],
            'letter_body' => ['nullable', 'string', 'max:20000', Rule::prohibitedIf($request->input('letter_type') !== 'outgoing')],
            'closing_text' => ['nullable', 'string', 'max:5000'],
            'carbon_copy' => ['nullable', 'string', 'max:3000'],
            'signer_name' => ['nullable', 'string', 'max:180', Rule::requiredIf(filled($request->input('letter_body')))],
            'signer_title' => ['nullable', 'string', 'max:180', Rule::requiredIf(filled($request->input('letter_body')))],
            'signer_identifier_type' => ['nullable', Rule::in(['nip', 'nuptk', 'npk'])],
            'signer_identifier' => ['nullable', 'string', 'max:80'],
            'attachment_note' => ['nullable', 'string', 'max:255'],
            'approval_required' => ['sometimes', 'boolean'],
            'signature_method' => ['nullable', Rule::in(['manual', 'principal_approval', 'bsre'])],
            'signatory_id' => ['nullable', Rule::exists('correspondence_signatories', 'id')->where(fn ($query) => $query->where('school_id', $user->school_id)->where('is_active', true))],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'extensions:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        if ($request->input('letter_type') === 'incoming' && filled($validated['disposition_target'] ?? null)) {
            $validated['disposition'] = 'Tujuan: '.$validated['disposition_target']
                .(filled($validated['disposition'] ?? null) ? "\n".$validated['disposition'] : '');
        }
        unset($validated['disposition_target']);
        $attachment = $request->file('attachment');
        unset($validated['attachment']);
        $this->correspondence->create($user, $validated, $attachment);

        return back()->with('message', 'Surat berhasil dicatat.');
    }

    public function updateStatus(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['disposition', 'done', 'issued', 'sent', 'archived'])],
            'disposition' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->correspondence->updateStatus($user, $record, $validated['status'], $validated['disposition'] ?? null);

        return back()->with('message', 'Status surat berhasil diperbarui.');
    }

    public function updateDraft(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $data = $request->validate([
            'letter_number' => [
                'required', 'string', 'max:100',
                Rule::unique('correspondence_records', 'letter_number')
                    ->ignore($record->id)
                    ->where(fn ($query) => $query->where('school_id', $user->school_id)->where('letter_type', 'outgoing')
                        ->where('letter_year', (int) substr((string) $request->input('letter_date'), 0, 4))),
            ],
            'letter_template' => ['nullable', Rule::in(['official_school', 'invitation', 'assignment', 'statement'])],
            'letter_date' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:180'],
            'recipient_address' => ['nullable', 'string', 'max:2000'],
            'opening_greeting' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:200'],
            'classification' => ['nullable', 'string', 'max:40'],
            'urgency' => ['nullable', Rule::in(['ordinary', 'urgent', 'immediate'])],
            'summary' => ['nullable', 'string', 'max:5000'],
            'letter_body' => ['required', 'string', 'max:20000'],
            'closing_text' => ['nullable', 'string', 'max:5000'],
            'carbon_copy' => ['nullable', 'string', 'max:3000'],
            'signer_name' => ['required', 'string', 'max:180'],
            'signer_title' => ['required', 'string', 'max:180'],
            'signer_identifier_type' => ['nullable', Rule::in(['nip', 'nuptk', 'npk'])],
            'signer_identifier' => ['nullable', 'string', 'max:80'],
            'attachment_note' => ['nullable', 'string', 'max:255'],
            'approval_required' => ['sometimes', 'boolean'],
            'signature_method' => ['nullable', Rule::in(['manual', 'principal_approval', 'bsre'])],
            'signatory_id' => ['nullable', Rule::exists('correspondence_signatories', 'id')->where(fn ($query) => $query->where('school_id', $user->school_id)->where('is_active', true))],
        ]);
        $this->correspondence->updateDraft($user, $record, $data);

        return back()->with('message', 'Draf surat berhasil disimpan.');
    }

    public function approve(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->school_id !== null && $user->hasRole('principal'), 403);
        $this->correspondence->assertOwnedByActor($user, $record);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);
        $this->correspondence->approve($user, $record, $validated['decision'] === 'approve', $validated['note'] ?? null);

        return back()->with('message', $validated['decision'] === 'approve' ? 'Surat disetujui kepala sekolah.' : 'Surat ditolak kepala sekolah.');
    }

    public function submitApproval(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $this->correspondence->submitForApproval($user, $record);

        return back()->with('message', 'Surat diajukan kepada kepala sekolah.');
    }

    public function revokeVerification(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $this->correspondence->revokeVerification($user, $record);

        return back()->with('message', 'Verifikasi surat berhasil dicabut.');
    }

    public function pdf(Request $request, CorrespondenceRecord $record): HttpResponse
    {
        $user = $this->authorizeViewer($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        abort_unless($record->letter_type === 'outgoing' && filled($record->letter_body), 404);

        $pdf = $this->correspondence->workflowPdfBytes($record);
        $this->correspondence->logPdfRendered($user, $record);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="surat.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function verify(string $token): HttpResponse
    {
        abort_unless(preg_match('/\A[a-f0-9]{64}\z/', $token), 404);
        $record = CorrespondenceRecord::query()->where('verification_token', $token)->first();
        if ($record === null) {
            $this->correspondence->logVerificationAttempt(null, 'administration.correspondence.verification_rejected', 'not_found');
            abort(404);
        }
        if (! in_array($record->status, ['issued', 'sent', 'archived'], true)) {
            $this->correspondence->logVerificationAttempt($record, 'administration.correspondence.verification_rejected', 'status_not_public');
            abort(404);
        }
        if ($record->verification_revoked_at !== null) {
            $this->correspondence->logVerificationAttempt($record, 'administration.correspondence.verification_rejected', 'revoked');
            abort(404);
        }
        if ($record->verification_expires_at === null || $record->verification_expires_at->isPast()) {
            $this->correspondence->logVerificationAttempt($record, 'administration.correspondence.verification_rejected', 'expired');
            abort(404);
        }

        $this->correspondence->logVerificationAttempt($record, 'administration.correspondence.verification_viewed', 'valid');

        $signatureRequest = CorrespondenceSignatureRequest::query()
            ->where('school_id', $record->school_id)
            ->where('correspondence_record_id', $record->id)
            ->latest('id')->first();

        return response()->view('correspondence.verify', ['record' => $record, 'school' => $record->school_snapshot ?? [],
            'integrity_status' => $this->correspondence->verificationIntegrity($record),
            'signature_status' => $signatureRequest?->status === 'signed'
                && data_get($signatureRequest->certificate_metadata, 'verified') === true
                    ? 'verified'
                    : $signatureRequest?->status,
        ])
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Content-Type-Options', 'nosniff');
    }

    public function downloadAttachment(Request $request, int $record): StreamedResponse
    {
        $user = $this->authorizeViewer($request->user());
        $correspondence = CorrespondenceRecord::query()
            ->where('school_id', $user->school_id)
            ->whereKey($record)
            ->firstOrFail();
        abort_unless($correspondence->attachment_path && Storage::disk('local')->exists($correspondence->attachment_path), 404);
        $this->correspondence->logAttachmentDownload($user, $correspondence);

        return Storage::disk('local')->download($correspondence->attachment_path, $correspondence->attachment_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function storeSignatory(Request $request): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('school_id', $user->school_id)->where('is_active', true))],
            'name' => ['required', 'string', 'max:180'], 'title' => ['required', 'string', 'max:180'],
            'employee_number' => ['nullable', 'string', 'max:80'], 'rank' => ['nullable', 'string', 'max:120'],
            'template_scopes' => ['nullable', 'array'], 'template_scopes.*' => [Rule::in(['official_school', 'invitation', 'assignment', 'statement'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $this->correspondence->createSignatory($user, $data);

        return back()->with('message', 'Profil penandatangan berhasil disimpan.');
    }

    public function prepareManual(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $this->correspondence->prepareManualSignature($user, $record);

        return back()->with('message', 'PDF siap dicetak dan ditandatangani.');
    }

    public function uploadManual(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $request->validate(['signed_document' => ['required', 'file', 'max:15360', 'mimetypes:application/pdf', 'extensions:pdf']]);
        $this->correspondence->finalizeManualSignature($user, $record, $request->file('signed_document'));

        return back()->with('message', 'Hasil tanda tangan basah telah menjadi dokumen final.');
    }

    public function requestBsre(Request $request, CorrespondenceRecord $record): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        $this->correspondence->requestBsreSignature($user, $record);

        return back()->with('message', 'Permintaan tanda tangan BSrE masuk antrean.');
    }

    public function retryBsre(Request $request, CorrespondenceSignatureRequest $signatureRequest): RedirectResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->retrySignature($user, $signatureRequest);

        return back()->with('message', 'Permintaan tanda tangan BSrE dijadwalkan ulang.');
    }

    private function authorizeManager(?User $user): User
    {
        abort_unless($user && $user->is_active && $user->school_id !== null && $user->hasRole(['admin', 'operator']), 403);

        return $user;
    }

    private function authorizeViewer(?User $user): User
    {
        abort_unless($user && $user->is_active && $user->school_id !== null && $user->hasRole(['admin', 'operator', 'principal']), 403);

        return $user;
    }
}
