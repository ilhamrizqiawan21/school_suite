<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Services\PtspWorkflowService;
use App\Modules\Administration\Infrastructure\Models\PtspRequest;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PtspPublicController extends Controller
{
    public function __construct(
        private readonly PtspWorkflowService $ptsp,
        private readonly ExternalApplicantAuthService $applicantAuth,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);
        $draftToken = (string) $request->session()->get('ptsp_draft_token', '');
        $selectedServiceId = (int) $request->query('layanan', 0);
        $draftId = (int) $request->query('draf', 0);

        $myRequests = $this->ptsp->listApplicantRequests(
            schoolId: $schoolId,
            user: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            sessionDraftToken: $draftToken !== '' ? $draftToken : null,
        );

        $activeDraft = null;
        if ($draftId > 0) {
            $activeDraft = collect($myRequests)->first(
                static fn (array $item): bool => (int) $item['id'] === $draftId && $item['status'] === 'draft'
            );
        }

        return Inertia::render('Public/Services/Ptsp', [
            'school' => $this->schoolPayload($school),
            'contentBlocks' => $school['content_blocks'] ?? [],
            'catalog' => $this->ptsp->listCatalogForPublic($schoolId),
            'categories' => collect(PtspWorkflowService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'applicant' => $applicant,
            'authUser' => $request->user() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'role' => $request->user()->role?->name,
            ] : null,
            'myRequests' => $myRequests,
            'activeDraft' => $activeDraft,
            'selectedServiceId' => $selectedServiceId > 0 ? $selectedServiceId : null,
            'applicantTypes' => [
                ['value' => 'general', 'label' => 'Masyarakat / Umum'],
                ['value' => 'guardian', 'label' => 'Orang Tua / Wali Siswa'],
                ['value' => 'student', 'label' => 'Siswa'],
                ['value' => 'alumni', 'label' => 'Alumni'],
                ['value' => 'external_agency', 'label' => 'Instansi / Mitra'],
            ],
        ]);
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);

        $validated = $request->validate([
            'request_id' => ['nullable', 'integer'],
            'ptsp_service_id' => ['required', 'integer'],
            'applicant_type' => ['required', Rule::in(ExternalApplicantAuthService::ALLOWED_TYPES)],
            'applicant_name' => ['required', 'string', 'max:150'],
            'applicant_phone' => ['nullable', 'string', 'max:30'],
            'applicant_email' => ['nullable', 'email', 'max:150'],
            'applicant_organization' => ['nullable', 'string', 'max:180'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $draftToken = (string) $request->session()->get('ptsp_draft_token', '');
        if ($request->user() === null && $applicant === null && $draftToken === '') {
            $draftToken = Str::random(40);
            $request->session()->put('ptsp_draft_token', $draftToken);
        }

        $uploadedFiles = $this->extractUploadedAttachments($request);

        $saved = $this->ptsp->saveDraft(
            schoolId: $schoolId,
            data: $validated,
            uploadedFiles: $uploadedFiles,
            user: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            sessionDraftToken: $draftToken !== '' ? $draftToken : null,
        );

        return redirect()->route('administration.ptsp.public.index', ['draf' => $saved->id])
            ->with('message', 'Draf permohonan PTSP berhasil disimpan.');
    }

    public function removeDraftAttachment(Request $request, PtspRequest $ptspRequest, int $documentId): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);
        $draftToken = (string) $request->session()->get('ptsp_draft_token', '');

        $this->ptsp->removeDraftAttachment(
            schoolId: $schoolId,
            ptspRequest: $ptspRequest,
            documentId: $documentId,
            user: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            sessionDraftToken: $draftToken !== '' ? $draftToken : null,
        );

        return back()->with('message', 'Lampiran draf berhasil dihapus.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);
        $draftToken = (string) $request->session()->get('ptsp_draft_token', '');

        $validated = $request->validate([
            'request_id' => ['nullable', 'integer'],
            'ptsp_service_id' => ['required_without:request_id', 'nullable', 'integer'],
            'applicant_type' => ['required_without:request_id', 'nullable', Rule::in(ExternalApplicantAuthService::ALLOWED_TYPES)],
            'applicant_name' => ['required_without:request_id', 'nullable', 'string', 'max:150'],
            'applicant_phone' => ['nullable', 'string', 'max:30'],
            'applicant_email' => ['nullable', 'email', 'max:150'],
            'applicant_organization' => ['nullable', 'string', 'max:180'],
            'purpose' => ['required_without:request_id', 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'agreement_accepted' => ['accepted'],
            'verification_secret' => ['nullable', 'string', 'min:4', 'max:24'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $uploadedFiles = $this->extractUploadedAttachments($request);

        if (! empty($validated['request_id'])) {
            $requestId = (int) $validated['request_id'];
            // Update draft fields/files if provided alongside submit
            if (! empty($validated['applicant_name']) && ! empty($validated['purpose'])) {
                $this->ptsp->saveDraft(
                    schoolId: $schoolId,
                    data: $validated,
                    uploadedFiles: $uploadedFiles,
                    user: $request->user(),
                    externalApplicantId: $applicant ? (int) $applicant['id'] : null,
                    sessionDraftToken: $draftToken !== '' ? $draftToken : null,
                );
            }
        } else {
            if ($request->user() === null && $applicant === null && $draftToken === '') {
                $draftToken = Str::random(40);
                $request->session()->put('ptsp_draft_token', $draftToken);
            }

            $draft = $this->ptsp->saveDraft(
                schoolId: $schoolId,
                data: $validated,
                uploadedFiles: $uploadedFiles,
                user: $request->user(),
                externalApplicantId: $applicant ? (int) $applicant['id'] : null,
                sessionDraftToken: $draftToken !== '' ? $draftToken : null,
            );
            $requestId = (int) $draft->id;
        }

        $result = $this->ptsp->submitRequest(
            schoolId: $schoolId,
            ptspRequestId: $requestId,
            verificationSecret: $validated['verification_secret'] ?? null,
            idempotencyKey: $validated['idempotency_key'] ?? null,
            user: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            sessionDraftToken: $draftToken !== '' ? $draftToken : null,
        );

        $ticketCode = (string) ($result['ticket']['ticket_code'] ?? '');
        if ($ticketCode !== '') {
            $request->session()->put("ptsp_receipt_access.{$ticketCode}", true);
            if ($result['plain_verification_secret'] !== null) {
                $request->session()->flash('ptsp_issued_secret', $result['plain_verification_secret']);
            }
        }

        return redirect()->route('administration.ptsp.public.receipt', $ticketCode)
            ->with('message', 'Permohonan PTSP berhasil diajukan dan resi diterbitkan.');
    }

    public function receipt(Request $request, string $ticketCode): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $found = $this->ptsp->findByTicketCode($schoolId, $ticketCode);

        abort_if($found === null, 404);
        $this->authorizeApplicantOrVerified($request, $schoolId, $found['model'], $ticketCode);

        return Inertia::render('Public/Services/PtspReceipt', [
            'school' => $this->schoolPayload($school),
            'ptspRequest' => $found['serialized'],
            'issuedSecret' => $request->session()->get('ptsp_issued_secret'),
            'canRevise' => ($found['serialized']['status'] ?? '') === ServiceWorkflowMap::STATUS_NEEDS_REVISION,
            'canCancel' => in_array($found['serialized']['status'] ?? '', [ServiceWorkflowMap::STATUS_SUBMITTED, ServiceWorkflowMap::STATUS_NEEDS_REVISION], true),
        ]);
    }

    public function receiptPdf(Request $request, string $ticketCode): HttpResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $found = $this->ptsp->findByTicketCode($schoolId, $ticketCode);

        abort_if($found === null, 404);
        $this->authorizeApplicantOrVerified($request, $schoolId, $found['model'], $ticketCode);

        $pdfBytes = $this->ptsp->renderReceiptPdfBytes($schoolId, $found['serialized']);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="resi-%s.pdf"', strtolower($ticketCode)),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function showRevision(Request $request, string $ticketCode): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $found = $this->ptsp->findByTicketCode($schoolId, $ticketCode);

        abort_if($found === null, 404);
        $this->authorizeApplicantOrVerified($request, $schoolId, $found['model'], $ticketCode);

        return Inertia::render('Public/Services/PtspRevision', [
            'school' => $this->schoolPayload($school),
            'ptspRequest' => $found['serialized'],
            'verificationSecret' => trim((string) $request->query('secret', '')),
        ]);
    }

    public function submitRevision(Request $request, string $ticketCode): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);
        $found = $this->ptsp->findByTicketCode($schoolId, $ticketCode);

        abort_if($found === null, 404);

        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'revision_reply' => ['nullable', 'string', 'max:1000'],
            'verification_secret' => ['nullable', 'string', 'max:80'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $secret = trim((string) ($validated['verification_secret'] ?? ''));
        if ($secret === '' && $request->session()->get("ptsp_verified_secret.{$ticketCode}")) {
            $secret = (string) $request->session()->get("ptsp_verified_secret.{$ticketCode}");
        }

        $this->ptsp->resubmitRevision(
            schoolId: $schoolId,
            ptspRequest: $found['model'],
            data: $validated,
            uploadedFiles: $this->extractUploadedAttachments($request),
            user: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            verificationSecret: $secret !== '' ? $secret : null,
        );

        $request->session()->put("ptsp_receipt_access.{$ticketCode}", true);

        return redirect()->route('administration.ptsp.public.receipt', $ticketCode)
            ->with('message', 'Perbaikan permohonan berhasil dikirim ulang ke petugas TU.');
    }

    public function cancel(Request $request, string $ticketCode): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);
        $found = $this->ptsp->findByTicketCode($schoolId, $ticketCode);

        abort_if($found === null, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
            'verification_secret' => ['nullable', 'string', 'max:80'],
        ]);

        $secret = trim((string) ($validated['verification_secret'] ?? ''));
        if ($secret === '' && $request->session()->get("ptsp_verified_secret.{$ticketCode}")) {
            $secret = (string) $request->session()->get("ptsp_verified_secret.{$ticketCode}");
        }

        $this->ptsp->cancelByApplicant(
            schoolId: $schoolId,
            ptspRequest: $found['model'],
            reason: (string) ($validated['reason'] ?? ''),
            user: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            verificationSecret: $secret !== '' ? $secret : null,
            hasSessionReceiptAccess: $request->session()->get("ptsp_receipt_access.{$ticketCode}") === true,
        );

        $request->session()->put("ptsp_receipt_access.{$ticketCode}", true);

        return redirect()->route('administration.ptsp.public.receipt', $ticketCode)
            ->with('message', 'Permohonan PTSP berhasil dibatalkan oleh pemohon.');
    }

    public function downloadDocument(Request $request, string $ticketCode, int $documentId): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $found = $this->ptsp->findByTicketCode($schoolId, $ticketCode);

        abort_if($found === null, 404);
        $this->authorizeApplicantOrVerified($request, $schoolId, $found['model'], $ticketCode);

        $ticketId = (int) $found['ticket']['id'];
        $file = $this->documentService->resolveDownloadForVerifiedTicket($schoolId, $ticketId, $documentId);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function authorizeApplicantOrVerified(Request $request, int $schoolId, PtspRequest $ptspRequest, string $ticketCode): void
    {
        $user = $request->user();
        if (
            $user !== null
            && (int) $user->school_id === $schoolId
            && $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PRINCIPAL->value, RoleEnum::PTSP->value])
        ) {
            return;
        }

        $applicant = $this->applicantAuth->currentFromSession($request, $schoolId);
        $draftToken = (string) $request->session()->get('ptsp_draft_token', '');

        if ($this->ptsp->isRequestOwner($ptspRequest, $user, $applicant ? (int) $applicant['id'] : null, $draftToken)) {
            return;
        }

        if ($request->session()->get("ptsp_receipt_access.{$ticketCode}") === true) {
            return;
        }

        $secret = trim((string) ($request->query('secret') ?: $request->input('verification_secret', '')));
        if ($secret !== '' && $this->ticketService->verifyTicketSecret($schoolId, $ticketCode, $secret)) {
            $request->session()->put("ptsp_receipt_access.{$ticketCode}", true);
            $request->session()->put("ptsp_verified_secret.{$ticketCode}", $secret);

            return;
        }

        abort(403, 'Verifikasi identitas pemohon diperlukan untuk membuka detail atau berkas tiket ini.');
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function extractUploadedAttachments(Request $request): array
    {
        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            return [];
        }

        $clean = [];
        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile) {
                $clean[(string) $key] = $file;
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $school
     * @return array<string, mixed>
     */
    private function schoolPayload(array $school): array
    {
        return [
            'name' => $school['name'],
            'logo_url' => $school['logo_url'] ?? null,
            'theme' => $school['theme'] ?? [],
        ];
    }
}
