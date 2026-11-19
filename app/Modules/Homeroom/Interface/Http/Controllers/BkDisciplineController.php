<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Homeroom\Application\Services\BkDisciplineService;
use App\Modules\Homeroom\Infrastructure\Models\StudentIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BkDisciplineController extends Controller
{
    public function __construct(
        private readonly BkDisciplineService $bkService,
        private readonly AcademicQueryService $academicQuery,
        private readonly ServiceDocumentService $documentService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->bkService->assertAuthorizedActor($user);
        $schoolId = (int) $user->school_id;

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        return Inertia::render('Homeroom/Bk/Index', [
            'cases' => $this->bkService->listCases($user, $filters),
            'students' => $this->academicQuery->searchActiveStudentsForStaff($schoolId, $filters['search'], 40),
            'categories' => collect(BkDisciplineService::VIOLATION_CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => $filters,
        ]);
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->bkService->assertAuthorizedActor($user);

        $q = trim((string) $request->query('q', ''));
        $students = $this->academicQuery->searchActiveStudentsForStaff((int) $user->school_id, $q, 15);

        return response()->json(['students' => $students]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->bkService->assertAuthorizedActor($user);

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'incident_date' => ['required', 'date'],
            'incident_time' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(BkDisciplineService::VIOLATION_CATEGORIES))],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'penalty_points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'follow_up_action' => ['nullable', 'string', 'max:3000'],
            'status' => ['nullable', Rule::in(['reported', 'investigating', 'resolved'])],
            'evidence_files' => ['nullable', 'array', 'max:5'],
            'evidence_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $evidenceFiles = array_values(array_filter(
            (array) $request->file('evidence_files', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $this->bkService->recordCase($user, $validated, $evidenceFiles);

        return back()->with('message', 'Kasus disiplin BK berhasil dicatat secara privat.');
    }

    public function update(Request $request, StudentIncident $incident): RedirectResponse
    {
        $user = $request->user();
        $this->bkService->assertAuthorizedActor($user);

        $validated = $request->validate([
            'incident_date' => ['required', 'date'],
            'incident_time' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(BkDisciplineService::VIOLATION_CATEGORIES))],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'penalty_points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'follow_up_action' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['reported', 'investigating', 'resolved'])],
            'evidence_files' => ['nullable', 'array', 'max:5'],
            'evidence_files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $evidenceFiles = array_values(array_filter(
            (array) $request->file('evidence_files', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $this->bkService->updateCase($user, $incident, $validated, $evidenceFiles);

        return back()->with('message', 'Kasus disiplin BK berhasil diperbarui.');
    }

    public function downloadEvidence(Request $request, StudentIncident $incident, int $documentId): BinaryFileResponse
    {
        $user = $request->user();
        $this->bkService->assertAuthorizedActor($user);
        $schoolId = (int) $user->school_id;
        abort_unless((int) $incident->school_id === $schoolId, 404);
        abort_unless(in_array($documentId, array_map('intval', $incident->evidence_document_ids ?? []), true), 404);

        $file = $this->documentService->resolveDownloadForStaff($user, $schoolId, $documentId);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function export(Request $request): HttpResponse
    {
        $user = $request->user();
        $this->bkService->assertAuthorizedActor($user);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $csv = $this->bkService->exportCsv($user, $filters);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap-kasus-bk.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
