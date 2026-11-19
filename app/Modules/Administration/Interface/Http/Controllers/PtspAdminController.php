<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Services\PtspWorkflowService;
use App\Modules\Administration\Infrastructure\Models\PtspRequest;
use App\Modules\Administration\Infrastructure\Models\PtspService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PtspAdminController extends Controller
{
    public function __construct(
        private readonly PtspWorkflowService $ptsp,
        private readonly ServiceDocumentService $documentService,
    ) {}

    public function index(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $filters = [
            'service_id' => $request->query('service_id', ''),
            'status' => $request->query('status', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'search' => $request->query('search', ''),
        ];

        $queue = $this->ptsp->listQueueForStaff($schoolId, $filters);
        $catalog = $this->ptsp->listCatalogForStaff($schoolId);

        return Inertia::render('Administration/Ptsp/Index', [
            'requests' => $queue['requests'],
            'pagination' => $queue['pagination'],
            'summary' => $queue['summary'],
            'catalog' => $catalog,
            'filters' => $filters,
            'categories' => collect(PtspWorkflowService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'statusOptions' => collect(ServiceWorkflowMap::PUBLIC_STATUSES)
                ->map(static fn (string $status): array => [
                    'value' => $status,
                    'label' => match ($status) {
                        'submitted' => 'Diajukan',
                        'in_review' => 'Diproses',
                        'needs_revision' => 'Perlu Perbaikan',
                        'approved' => 'Siap Diambil',
                        'rejected' => 'Ditolak',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kedaluwarsa',
                        default => $status,
                    },
                ])
                ->values()
                ->all(),
        ]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('ptsp_services', 'code')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(PtspWorkflowService::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['nullable'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*.key' => ['nullable', 'string', 'max:60'],
            'required_documents.*.label' => ['required_with:required_documents', 'string', 'max:150'],
            'required_documents.*.required' => ['nullable', 'boolean'],
            'estimated_duration' => ['required', 'string', 'max:80'],
            'service_hours' => ['nullable', 'string', 'max:120'],
            'delivery_mode' => ['required', Rule::in(['digital', 'physical', 'both'])],
            'requires_verification_secret' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->ptsp->createCatalogService($request->user(), $validated);

        return back()->with('message', 'Layanan katalog PTSP berhasil ditambahkan.');
    }

    public function updateService(Request $request, PtspService $service): RedirectResponse
    {
        $schoolId = (int) $request->user()->school_id;
        abort_unless((int) $service->school_id === $schoolId, 404);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('ptsp_services', 'code')->where('school_id', $schoolId)->ignore($service->id)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(PtspWorkflowService::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['nullable'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*.key' => ['nullable', 'string', 'max:60'],
            'required_documents.*.label' => ['required_with:required_documents', 'string', 'max:150'],
            'required_documents.*.required' => ['nullable', 'boolean'],
            'estimated_duration' => ['required', 'string', 'max:80'],
            'service_hours' => ['nullable', 'string', 'max:120'],
            'delivery_mode' => ['required', Rule::in(['digital', 'physical', 'both'])],
            'requires_verification_secret' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->ptsp->updateCatalogService($request->user(), $service, $validated);

        return back()->with('message', 'Layanan katalog PTSP berhasil diperbarui.');
    }

    public function toggleServiceStatus(Request $request, PtspService $service): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $this->ptsp->toggleCatalogStatus(
            $request->user(),
            $service,
            array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            array_key_exists('is_published', $validated) ? (bool) $validated['is_published'] : null,
        );

        return back()->with('message', 'Status layanan katalog PTSP diperbarui.');
    }

    public function startReview(Request $request, PtspRequest $ptspRequest): RedirectResponse
    {
        $validated = $request->validate([
            'expected_version' => ['nullable', 'integer'],
        ]);

        $this->ptsp->startReview(
            $request->user(),
            $ptspRequest,
            isset($validated['expected_version']) ? (int) $validated['expected_version'] : null,
        );

        return back()->with('message', 'Permohonan masuk ke tahap pemeriksaan petugas.');
    }

    public function requestRevision(Request $request, PtspRequest $ptspRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1500'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $this->ptsp->requestRevision(
            $request->user(),
            $ptspRequest,
            $validated['reason'],
            isset($validated['expected_version']) ? (int) $validated['expected_version'] : null,
        );

        return back()->with('message', 'Permintaan perbaikan dikirim ke pemohon.');
    }

    public function reject(Request $request, PtspRequest $ptspRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1500'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $this->ptsp->rejectRequest(
            $request->user(),
            $ptspRequest,
            $validated['reason'],
            isset($validated['expected_version']) ? (int) $validated['expected_version'] : null,
        );

        return back()->with('message', 'Permohonan ditolak beserta alasan.');
    }

    public function disposition(Request $request, PtspRequest $ptspRequest): RedirectResponse
    {
        $validated = $request->validate([
            'disposition_to' => ['required', 'string', 'max:150'],
            'disposition_note' => ['nullable', 'string', 'max:1500'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $this->ptsp->assignDisposition(
            $request->user(),
            $ptspRequest,
            $validated['disposition_to'],
            $validated['disposition_note'] ?? null,
            isset($validated['expected_version']) ? (int) $validated['expected_version'] : null,
        );

        return back()->with('message', 'Disposisi permohonan berhasil dicatat.');
    }

    public function issueResult(Request $request, PtspRequest $ptspRequest): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_mode' => ['required', Rule::in(['digital', 'physical', 'both'])],
            'mark_completed' => ['nullable', 'boolean'],
            'result_summary' => ['nullable', 'string', 'max:2000'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
            'signature_mode' => ['nullable', Rule::in(['none', 'image', 'tte', 'manual_wet'])],
            'result_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $this->ptsp->issueResult(
            $request->user(),
            $ptspRequest,
            $validated,
            $request->file('result_file'),
            isset($validated['expected_version']) ? (int) $validated['expected_version'] : null,
        );

        return back()->with('message', 'Hasil layanan PTSP berhasil diterbitkan.');
    }

    public function recordPickup(Request $request, PtspRequest $ptspRequest): RedirectResponse
    {
        $validated = $request->validate([
            'physical_pickup_name' => ['required', 'string', 'max:150'],
            'physical_pickup_notes' => ['nullable', 'string', 'max:1000'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $this->ptsp->recordPhysicalPickup(
            $request->user(),
            $ptspRequest,
            $validated,
            isset($validated['expected_version']) ? (int) $validated['expected_version'] : null,
        );

        return back()->with('message', 'Pengambilan dokumen fisik berhasil dicatat.');
    }

    public function downloadDocument(Request $request, PtspRequest $ptspRequest, int $documentId): BinaryFileResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        abort_unless((int) $ptspRequest->school_id === $schoolId, 404);

        $file = $this->documentService->resolveDownloadForStaff($user, $schoolId, $documentId);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
