<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Services\CorrespondenceService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExternalLetterController extends Controller
{
    public function __construct(
        private readonly CorrespondenceService $correspondence,
        private readonly ServiceTicketService $ticketService,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function create(): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();

        return Inertia::render('Public/Services/ExternalLetter', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'contentBlocks' => $school['content_blocks'] ?? [],
            'urgencyOptions' => [
                ['value' => 'ordinary', 'label' => 'Biasa'],
                ['value' => 'important', 'label' => 'Penting'],
                ['value' => 'urgent', 'label' => 'Segera'],
                ['value' => 'confidential', 'label' => 'Terbatas / Rahasia'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'agency_name' => ['required', 'string', 'max:180'],
            'contact_person' => ['required', 'string', 'max:150'],
            'contact_position' => ['required', 'string', 'max:150'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'letter_number' => ['required', 'string', 'max:120'],
            'letter_date' => ['required', 'date'],
            'urgency' => ['required', Rule::in(['ordinary', 'important', 'urgent', 'confidential'])],
            'classification' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:3000'],
            'primary_pdf' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'supporting_files' => ['nullable', 'array', 'max:5'],
            'supporting_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'statement_accepted' => ['accepted'],
            'verification_secret' => ['nullable', 'string', 'min:4', 'max:24'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ]);

        $supportingFiles = array_values(array_filter(
            (array) $request->file('supporting_files', []),
            static fn (mixed $item): bool => $item instanceof UploadedFile,
        ));

        $result = $this->correspondence->createIncomingFromExternal(
            schoolId: $schoolId,
            data: $validated,
            primaryPdf: $request->file('primary_pdf'),
            supportingFiles: $supportingFiles,
            verificationSecret: $validated['verification_secret'] ?? null,
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );

        $ticketCode = (string) ($result['ticket']['ticket_code'] ?? '');
        if ($ticketCode !== '') {
            $request->session()->put("external_letter_receipts.{$ticketCode}", true);
            $request->session()->flash('external_letter_secret', $result['plain_verification_secret']);
        }

        return redirect()->route('administration.external-letters.receipt', $ticketCode)
            ->with('message', 'Surat dinas eksternal berhasil diterima dan dicatat dalam agenda Tata Usaha.');
    }

    public function receipt(Request $request, string $ticketCode): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $receipt = $this->correspondence->findExternalReceiptByTicketCode($schoolId, $ticketCode);

        abort_if($receipt === null, 404);
        $this->authorizeReceiptViewer($request, $schoolId, $ticketCode);

        return Inertia::render('Public/Services/ExternalLetterReceipt', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'receipt' => $receipt,
            'issuedSecret' => $request->session()->get('external_letter_secret'),
        ]);
    }

    public function receiptPdf(Request $request, string $ticketCode): HttpResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $receipt = $this->correspondence->findExternalReceiptByTicketCode($schoolId, $ticketCode);

        abort_if($receipt === null, 404);
        $this->authorizeReceiptViewer($request, $schoolId, $ticketCode);

        $pdfBytes = $this->correspondence->renderExternalReceiptPdfBytes($schoolId, $receipt);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="tanda-terima-%s.pdf"', strtolower($ticketCode)),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeReceiptViewer(Request $request, int $schoolId, string $ticketCode): void
    {
        $user = $request->user();
        if (
            $user !== null
            && (int) $user->school_id === $schoolId
            && $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PRINCIPAL->value, RoleEnum::PTSP->value])
        ) {
            return;
        }

        if ($request->session()->get("external_letter_receipts.{$ticketCode}") === true) {
            return;
        }

        $secret = trim((string) ($request->query('secret') ?: $request->input('verification_secret', '')));
        if ($secret !== '' && $this->ticketService->verifyTicketSecret($schoolId, $ticketCode, $secret)) {
            $request->session()->put("external_letter_receipts.{$ticketCode}", true);

            return;
        }

        abort(403, 'Verifikasi kode lacak diperlukan untuk membuka tanda terima surat dinas.');
    }
}
