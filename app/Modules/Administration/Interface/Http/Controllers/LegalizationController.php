<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Services\LegalizationService;
use App\Modules\Administration\Infrastructure\Models\LegalizationRequest;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegalizationController extends Controller
{
    public function __construct(
        private readonly LegalizationService $legalizationService,
        private readonly ExternalApplicantAuthService $externalAuth,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
        private readonly AccessScopeService $accessScope,
    ) {}

    public function publicIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $ext = $this->externalAuth->currentFromSession($request, $schoolId);
        $user = $request->user();

        $myRequests = LegalizationRequest::query()
            ->where('school_id', $schoolId)
            ->when(
                $user !== null && (int) $user->school_id === $schoolId,
                fn ($q) => $q->where('applicant_user_id', $user->id),
                fn ($q) => $ext !== null
                    ? $q->where('external_applicant_id', (int) $ext['id'])
                    : $q->whereRaw('1 = 0'),
            )
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (LegalizationRequest $r): array => $this->serialize($r))
            ->values()
            ->all();

        return Inertia::render('Public/Services/Legalization', [
            'school' => [
                'name' => $school['name'],
                'address' => $school['address'] ?? null,
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'myRequests' => $myRequests,
            'issuedReceipt' => $request->session()->get('issued_legalization_receipt'),
            'contentBlocks' => $school['content_blocks'] ?? [],
        ]);
    }

    public function publicSubmit(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'applicant_name' => ['required', 'string', 'max:150'],
            'applicant_nis' => ['nullable', 'string', 'max:30'],
            'applicant_nisn' => ['nullable', 'string', 'max:30'],
            'graduation_year' => ['required', 'integer', 'min:1980', 'max:2100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'document_type' => ['required', Rule::in(['ijazah', 'skl', 'transkrip', 'rapor'])],
            'certificate_number' => ['required', 'string', 'max:80'],
            'copy_count' => ['required', 'integer', 'min:1', 'max:20'],
            'purpose' => ['required', 'string', 'max:255'],
            'delivery_method' => ['required', Rule::in(['pickup_direct', 'pickup_proxy'])],
            'proxy_name' => ['nullable', 'string', 'max:150'],
            'proxy_nik' => ['nullable', 'string', 'max:30'],
            'proxy_relation' => ['nullable', 'string', 'max:60'],
            'proxy_phone' => ['nullable', 'string', 'max:30'],
            'signature_data' => ['required', 'string', 'max:50000'],
            'power_of_attorney_document_id' => ['nullable', 'integer'],
            'sptjm_document_id' => ['nullable', 'integer'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $ext = $this->externalAuth->currentFromSession($request, $schoolId);
        $files = array_values(array_filter(
            (array) $request->file('attachments', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $result = $this->legalizationService->submit(
            schoolId: $schoolId,
            data: $validated,
            attachments: $files,
            actor: $request->user(),
            externalApplicantId: $ext ? (int) $ext['id'] : null,
        );

        $req = $result['request'];
        $request->session()->put("legalization_access.{$req->id}", true);

        return redirect()->route('administration.legalization.public.index')
            ->with('message', "Permohonan legalisir berhasil dikirim ({$req->legalization_code}).")
            ->with('issued_legalization_receipt', [
                'id' => (int) $req->id,
                'legalization_code' => $req->legalization_code,
                'ticket_code' => $result['ticket']['ticket_code'] ?? null,
                'verification_secret' => $result['plain_verification_secret'],
            ]);
    }

    public function powerOfAttorneyIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();

        return Inertia::render('Public/Services/PowerOfAttorney', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'legalizationCode' => trim((string) $request->query('kode', '')),
            'generatedDocument' => $request->session()->get('generated_auth_document'),
        ]);
    }

    public function generateAuthorizationDocument(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'template_type' => ['required', Rule::in(['power_of_attorney', 'sptjm'])],
            'grantor_name' => ['required', 'string', 'max:150'],
            'grantor_nisn' => ['nullable', 'string', 'max:30'],
            'grantor_nik' => ['required', 'string', 'max:30'],
            'grantor_graduation_year' => ['nullable', 'integer', 'min:1980', 'max:2100'],
            'grantor_phone' => ['required', 'string', 'max:30'],
            'proxy_name' => ['required_if:template_type,power_of_attorney', 'nullable', 'string', 'max:150'],
            'proxy_nik' => ['required_if:template_type,power_of_attorney', 'nullable', 'string', 'max:30'],
            'proxy_relation' => ['required_if:template_type,power_of_attorney', 'nullable', 'string', 'max:60'],
            'proxy_phone' => ['nullable', 'string', 'max:30'],
            'document_ref_number' => ['nullable', 'string', 'max:80'],
            'purpose' => ['required', 'string', 'max:255'],
            'legalization_code' => ['nullable', 'string', 'max:40'],
            'grantor_signature_data' => ['required', 'string', 'max:50000'],
            'proxy_signature_data' => ['nullable', 'string', 'max:50000'],
        ]);

        $issued = $this->legalizationService->generateAuthorizationDocument(
            schoolId: $schoolId,
            templateType: $validated['template_type'],
            data: $validated,
            actor: $request->user(),
        );

        $request->session()->put("service_document_access.{$issued['document_id']}", true);

        return redirect()->route('administration.power-of-attorney.index')
            ->with('message', 'Dokumen resmi berhasil dibuat dan siap diunduh.')
            ->with('generated_auth_document', $issued);
    }

    public function downloadGeneratedDocument(Request $request, int $document): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $user = $request->user();
        $isStaff = $user !== null && (int) $user->school_id === $schoolId && $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PTSP->value, RoleEnum::PRINCIPAL->value, RoleEnum::SIGNATORY->value]);
        $hasSession = $request->session()->get("service_document_access.{$document}") === true;

        $resolution = $this->documentService->authorizeAndResolveGeneratedDownload(
            schoolId: $schoolId,
            documentId: $document,
            allowedKinds: ['power_of_attorney', 'sptjm', 'legalization'],
            user: $user,
            hasSessionAccess: $hasSession,
            isStaff: $isStaff,
        );

        abort_if($resolution['status'] === 'not_found', 404);
        abort_if($resolution['status'] === 'forbidden', 403);

        $file = $resolution['file'];
        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function adminIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $status = trim((string) $request->query('status', ''));

        $requests = LegalizationRequest::query()
            ->where('school_id', $schoolId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(fn (LegalizationRequest $r): array => $this->serialize($r))
            ->values()
            ->all();

        return Inertia::render('Administration/Legalization/Index', [
            'canProcess' => $this->accessScope->canVerifyQueue($request->user(), $schoolId),
            'canPublish' => $this->accessScope->canApproveFinalDecision($request->user(), $schoolId) && $this->accessScope->canSignOfficialDocument($request->user(), $schoolId),
            'requests' => $requests,
            'filters' => ['status' => $status],
        ]);
    }

    public function verifyOriginal(Request $request, LegalizationRequest $legalizationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->legalizationService->verifyOriginalPhysical($request->user(), $legalizationRequest, (string) ($validated['note'] ?? ''));

        return back()->with('message', 'Pemeriksaan dokumen asli tercatat.');
    }

    public function approve(Request $request, LegalizationRequest $legalizationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
        ]);

        $this->legalizationService->approveAndStamp(
            $request->user(),
            $legalizationRequest,
            $validated['signatory_name'] ?? null,
            $validated['signatory_title'] ?? null,
        );

        return back()->with('message', 'Legalisir berhasil disahkan beserta nomor verifikasi.');
    }

    public function rejectOrRevision(Request $request, LegalizationRequest $legalizationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['request_revision', 'reject'])],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->legalizationService->requestRevisionOrReject(
            $request->user(),
            $legalizationRequest,
            $validated['action'],
            $validated['reason'],
        );

        return back()->with('message', 'Status permohonan legalisir berhasil diperbarui.');
    }

    public function recordPickup(Request $request, LegalizationRequest $legalizationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'picked_up_by_name' => ['required', 'string', 'max:150'],
            'relation' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->legalizationService->recordPickup(
            $request->user(),
            $legalizationRequest,
            $validated['picked_up_by_name'],
            (string) ($validated['relation'] ?? 'Pemohon Langsung'),
            $validated['notes'] ?? null,
        );

        return back()->with('message', 'Pengambilan berkas legalisir berhasil dicatat.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(LegalizationRequest $r): array
    {
        return [
            'id' => (int) $r->id,
            'legalization_code' => (string) $r->legalization_code,
            'applicant_name' => (string) $r->applicant_name,
            'applicant_nis' => $r->applicant_nis,
            'applicant_nisn' => $r->applicant_nisn,
            'graduation_year' => (int) $r->graduation_year,
            'document_type' => (string) $r->document_type,
            'certificate_number' => (string) $r->certificate_number,
            'copy_count' => (int) $r->copy_count,
            'purpose' => (string) $r->purpose,
            'delivery_method' => (string) $r->delivery_method,
            'proxy_name' => $r->proxy_name,
            'proxy_relation' => $r->proxy_relation,
            'power_of_attorney_document_id' => $r->power_of_attorney_document_id,
            'sptjm_document_id' => $r->sptjm_document_id,
            'legalization_document_id' => $r->legalization_document_id,
            'status' => (string) $r->status,
            'original_verified_at' => $r->original_verified_at?->toDateString(),
            'picked_up_at' => $r->picked_up_at?->toDateString(),
            'picked_up_by_name' => $r->picked_up_by_name,
            'revision_note' => $r->revision_note,
            'rejection_reason' => $r->rejection_reason,
            'created_at' => $r->created_at?->toDateString(),
        ];
    }
}
