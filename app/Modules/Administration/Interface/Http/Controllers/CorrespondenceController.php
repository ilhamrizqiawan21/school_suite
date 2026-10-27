<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\Application\Services\CorrespondenceService;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $user = $this->authorizeManager($request->user());
        $filter = $request->string('filter', 'all')->toString();
        abort_unless(in_array($filter, ['all', 'incoming', 'outgoing', 'archived'], true), 404);

        $records = CorrespondenceRecord::query()
            ->where('school_id', $user->school_id)
            ->when($filter === 'incoming', fn ($query) => $query->where('letter_type', 'incoming'))
            ->when($filter === 'outgoing', fn ($query) => $query->where('letter_type', 'outgoing'))
            ->when($filter === 'archived', fn ($query) => $query->where('status', 'archived'))
            ->latest('created_at')
            ->get([
                'id', 'letter_type', 'agenda_number', 'letter_number', 'letter_date', 'received_at',
                'sender', 'recipient', 'subject', 'summary', 'disposition', 'status',
                'attachment_name', 'attachment_path', 'archived_at', 'created_at',
                'letter_body', 'signer_name', 'signer_title', 'attachment_note',
            ])
            ->map(fn (CorrespondenceRecord $record): array => [
                'id' => $record->id,
                'letter_type' => $record->letter_type,
                'agenda_number' => $record->agenda_number,
                'letter_number' => $record->letter_number,
                'letter_date' => $record->letter_date?->toDateString(),
                'received_at' => $record->received_at?->toDateString(),
                'sender' => $record->sender,
                'recipient' => $record->recipient,
                'subject' => $record->subject,
                'summary' => $record->summary,
                'disposition' => $record->disposition,
                'status' => $record->status,
                'attachment_name' => $record->attachment_name,
                'has_attachment' => (bool) $record->attachment_path,
                'has_document' => filled($record->letter_body),
                'letter_body' => $record->status === 'draft' ? $record->letter_body : null,
                'signer_name' => $record->status === 'draft' ? $record->signer_name : null,
                'signer_title' => $record->status === 'draft' ? $record->signer_title : null,
                'attachment_note' => $record->status === 'draft' ? $record->attachment_note : null,
                'archived_at' => $record->archived_at?->toDateTimeString(),
            ]);

        return Inertia::render('Administration/Correspondence/Index', [
            'records' => $records,
            'filter' => $filter,
            'status_options' => [
                'incoming' => [
                    ['value' => 'received', 'label' => 'Diterima'],
                    ['value' => 'disposition', 'label' => 'Didisposisikan'],
                    ['value' => 'done', 'label' => 'Selesai'],
                    ['value' => 'archived', 'label' => 'Diarsipkan'],
                ],
                'outgoing' => [
                    ['value' => 'draft', 'label' => 'Draf'],
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
            'letter_number' => [Rule::requiredIf($request->input('letter_type') === 'outgoing'), 'nullable', 'string', 'max:100'],
            'letter_date' => ['nullable', 'date', Rule::requiredIf(filled($request->input('letter_body')))],
            'received_at' => [Rule::requiredIf($request->input('letter_type') === 'incoming'), 'nullable', 'date'],
            'sender' => [Rule::requiredIf($request->input('letter_type') === 'incoming'), 'nullable', 'string', 'max:180'],
            'recipient' => [Rule::requiredIf($request->input('letter_type') === 'outgoing'), 'nullable', 'string', 'max:180'],
            'subject' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'letter_body' => ['nullable', 'string', 'max:20000', Rule::prohibitedIf($request->input('letter_type') !== 'outgoing')],
            'signer_name' => ['nullable', 'string', 'max:180', Rule::requiredIf(filled($request->input('letter_body')))],
            'signer_title' => ['nullable', 'string', 'max:180', Rule::requiredIf(filled($request->input('letter_body')))],
            'attachment_note' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'extensions:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

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
            'letter_number' => ['required', 'string', 'max:100'],
            'letter_date' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:180'],
            'subject' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'letter_body' => ['required', 'string', 'max:20000'],
            'signer_name' => ['required', 'string', 'max:180'],
            'signer_title' => ['required', 'string', 'max:180'],
            'attachment_note' => ['nullable', 'string', 'max:255'],
        ]);
        $this->correspondence->updateDraft($user, $record, $data);

        return back()->with('message', 'Draf surat berhasil disimpan.');
    }

    public function pdf(Request $request, CorrespondenceRecord $record): HttpResponse
    {
        $user = $this->authorizeManager($request->user());
        $this->correspondence->assertOwnedByActor($user, $record);
        abort_unless($record->letter_type === 'outgoing' && filled($record->letter_body), 404);
        $school = $record->school_snapshot ?? $this->correspondence->getSchoolLegalSnapshot((int) $record->school_id);
        $qr = null;
        if ($record->verification_token && in_array($record->status, ['issued', 'sent', 'archived'], true)) {
            $writer = new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd));
            $qr = base64_encode($writer->writeString(rtrim((string) config('app.url'), '/').route('administration.correspondence.verify', $record->verification_token, false)));
        }

        return Pdf::loadView('correspondence.letter', compact('record', 'school', 'qr'))->setPaper('a4')
            ->stream('surat.pdf')->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
    }

    public function verify(string $token): HttpResponse
    {
        abort_unless(preg_match('/\A[a-f0-9]{64}\z/', $token), 404);
        $record = CorrespondenceRecord::query()->where('verification_token', $token)->whereIn('status', ['issued', 'sent', 'archived'])->firstOrFail();

        return response()->view('correspondence.verify', ['record' => $record, 'school' => $record->school_snapshot])
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Content-Type-Options', 'nosniff');
    }

    public function downloadAttachment(Request $request, int $record): StreamedResponse
    {
        $user = $this->authorizeManager($request->user());
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

    private function authorizeManager(?User $user): User
    {
        abort_unless($user && $user->is_active && $user->school_id !== null && $user->hasRole(['admin', 'operator']), 403);

        return $user;
    }
}
