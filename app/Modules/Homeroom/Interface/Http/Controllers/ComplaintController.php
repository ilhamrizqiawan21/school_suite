<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Homeroom\Application\Services\ComplaintReportService;
use App\Modules\Homeroom\Infrastructure\Models\ComplaintReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintReportService $complaintService,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
    ) {}

    public function publicCreate(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();

        return Inertia::render('Public/Services/Complaint', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'categories' => collect(ComplaintReportService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'issuedReceipt' => $request->session()->get('issued_complaint_receipt'),
            'transparency' => $this->complaintService->publicTransparencySummary((int) $school['id']),
            'contentBlocks' => $school['content_blocks'] ?? [],
        ]);
    }

    public function publicStore(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(ComplaintReportService::CATEGORIES))],
            'is_anonymous' => ['nullable', 'boolean'],
            'reporter_name' => ['nullable', 'string', 'max:150'],
            'reporter_contact' => ['nullable', 'string', 'max:100'],
            'reporter_role' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'incident_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:180'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'evidence_files' => ['nullable', 'array', 'max:5'],
            'evidence_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $files = array_values(array_filter(
            (array) $request->file('evidence_files', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $result = $this->complaintService->submitReport(
            schoolId: $schoolId,
            data: $validated,
            evidenceFiles: $files,
            actor: $request->user(),
        );

        $report = $result['report'];

        return redirect()->route('homeroom.complaints.public.create')
            ->with('message', "Laporan pengaduan diterima ({$report->report_code}).")
            ->with('issued_complaint_receipt', [
                'report_code' => $report->report_code,
                'ticket_code' => $result['ticket']['ticket_code'] ?? null,
                'verification_secret' => $result['plain_verification_secret'],
                'is_anonymous' => $report->is_anonymous,
            ]);
    }

    public function transparencyIndex(): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        return Inertia::render('Public/Services/ComplaintTransparency', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'transparency' => $this->complaintService->publicTransparencySummary($schoolId),
        ]);
    }

    public function taskForceIndex(Request $request): Response
    {
        $user = $request->user();
        $this->complaintService->assertTaskForceActor($user);
        $schoolId = (int) $user->school_id;

        $status = trim((string) $request->query('status', ''));
        $category = trim((string) $request->query('category', ''));

        $reports = ComplaintReport::query()
            ->where('school_id', $schoolId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(function (ComplaintReport $r): array {
                return [
                    'id' => (int) $r->id,
                    'report_code' => (string) $r->report_code,
                    'category' => (string) $r->category,
                    'category_label' => ComplaintReportService::CATEGORIES[$r->category] ?? $r->category,
                    'is_anonymous' => (bool) $r->is_anonymous,
                    'reporter_label' => $r->is_anonymous ? 'Pelapor Anonim (Terlindungi)' : ($r->reporter_name ?: '-'),
                    'reporter_contact' => $r->is_anonymous ? null : $r->reporter_contact,
                    'title' => (string) $r->title,
                    'description' => (string) $r->description,
                    'incident_date' => $r->incident_date?->toDateString(),
                    'location' => $r->location,
                    'evidence_document_ids' => array_map('intval', $r->evidence_document_ids ?? []),
                    'resolution_document_ids' => array_map('intval', $r->resolution_document_ids ?? []),
                    'assigned_to_user_id' => $r->assigned_to_user_id,
                    'status' => (string) $r->status,
                    'sla_due_at' => $r->sla_due_at?->toIso8601String(),
                    'task_force_reply' => $r->task_force_reply,
                    'resolution_summary' => $r->resolution_summary,
                    'moderated_public_summary' => $r->moderated_public_summary,
                    'publish_to_transparency' => (bool) $r->publish_to_transparency,
                    'created_at' => $r->created_at?->toDateString(),
                ];
            })
            ->values()
            ->all();

        $assignees = User::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('name', [RoleEnum::ADMIN->value, RoleEnum::TASK_FORCE->value, RoleEnum::COUNSELOR->value]))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Homeroom/Complaints/Index', [
            'reports' => $reports,
            'assignees' => $assignees,
            'categories' => collect(ComplaintReportService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => ['status' => $status, 'category' => $category],
        ]);
    }

    public function handle(Request $request, ComplaintReport $report): RedirectResponse
    {
        $user = $request->user();
        $this->complaintService->assertTaskForceActor($user);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['submitted', 'in_review', 'needs_revision', 'completed', 'rejected'])],
            'assigned_to_user_id' => ['nullable', 'integer'],
            'task_force_reply' => ['nullable', 'string', 'max:3000'],
            'resolution_summary' => ['nullable', 'string', 'max:3000'],
            'moderated_public_summary' => ['nullable', 'string', 'max:1000'],
            'publish_to_transparency' => ['nullable', 'boolean'],
            'resolution_files' => ['nullable', 'array', 'max:5'],
            'resolution_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $files = array_values(array_filter(
            (array) $request->file('resolution_files', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $this->complaintService->handleReport($user, $report, $validated, $files);

        return back()->with('message', 'Penanganan aduan Satgas berhasil diperbarui.');
    }

    public function downloadEvidence(Request $request, ComplaintReport $report, int $documentId): BinaryFileResponse
    {
        $user = $request->user();
        $this->complaintService->assertTaskForceActor($user);
        $schoolId = (int) $user->school_id;
        abort_unless((int) $report->school_id === $schoolId, 404);

        $allowedIds = array_merge(
            array_map('intval', $report->evidence_document_ids ?? []),
            array_map('intval', $report->resolution_document_ids ?? []),
        );
        abort_unless(in_array($documentId, $allowedIds, true), 404);

        $file = $this->documentService->resolveDownloadForStaff($user, $schoolId, $documentId);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
