<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Academic\Application\Services\GraduationService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\GraduationBatch;
use App\Modules\Academic\Infrastructure\Models\GraduationRecord;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GraduationController extends Controller
{
    public function __construct(
        private readonly GraduationService $graduationService,
        private readonly AcademicQueryService $academicQuery,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
    ) {}

    public function publicIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();

        return Inertia::render('Public/Services/Graduation', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'result' => $request->session()->get('graduation_lookup_result'),
        ]);
    }

    public function publicCheck(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'nisn' => ['required', 'string', 'max:30'],
            'pin' => ['nullable', 'string', 'max:20'],
        ]);

        $res = $this->graduationService->checkPortalResult(
            $schoolId,
            $validated['nisn'],
            (string) ($validated['pin'] ?? ''),
            $request->user(),
        );

        if (! empty($res['found']) && ! empty($res['released']) && ! empty($res['record']['id'])) {
            $request->session()->put("graduation_record_access.{$res['record']['id']}", true);
        }

        return redirect()->route('academic.graduation.public.index')
            ->with('graduation_lookup_result', $res);
    }

    public function downloadSkl(Request $request, GraduationRecord $record): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $record->loadMissing('batch');

        $hasSession = $request->session()->get("graduation_record_access.{$record->id}") === true;
        abort_unless($this->graduationService->canUserDownloadSkl($request->user(), $schoolId, $record, $hasSession), 403);
        abort_if($record->skl_document_id === null, 404);

        $file = $this->documentService->resolveDownloadByDocumentId($schoolId, (int) $record->skl_document_id);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function adminIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $years = AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('start_date')->get(['id', 'name', 'is_active']);
        $batches = GraduationBatch::query()
            ->where('school_id', $schoolId)
            ->with(['records.student:id,nis,nisn,name'])
            ->latest('id')
            ->get();

        $docIds = $batches->flatMap(fn (GraduationBatch $b) => $b->records->pluck('skl_document_id'))->filter()->map(static fn ($id): int => (int) $id)->unique()->values()->all();
        $tokensByDoc = $this->documentService->getVerificationTokensMapByIds($schoolId, $docIds);

        $serializedBatches = $batches->map(function (GraduationBatch $b) use ($tokensByDoc): array {
            return [
                'id' => (int) $b->id,
                'academic_year_id' => (int) $b->academic_year_id,
                'title' => (string) $b->title,
                'skl_prefix' => (string) $b->skl_prefix,
                'release_at' => $b->release_at?->toIso8601String(),
                'is_published' => (bool) $b->is_published,
                'is_released_now' => $this->graduationService->isBatchReleased($b),
                'signatory_name' => $b->signatory_name,
                'signatory_title' => $b->signatory_title,
                'records' => $b->records->map(fn (GraduationRecord $r): array => [
                    'id' => (int) $r->id,
                    'student_id' => (int) $r->student_id,
                    'student_name' => $r->student?->name ?? '-',
                    'student_nis' => $r->student?->nis ?? '-',
                    'student_nisn' => $r->student?->nisn ?? '-',
                    'decision' => (string) $r->decision,
                    'skl_number' => $r->skl_number,
                    'average_score' => $r->average_score !== null ? (float) $r->average_score : null,
                    'skl_verification_token' => $r->skl_document_id ? ($tokensByDoc[$r->skl_document_id] ?? null) : null,
                    'reissue_count' => (int) $r->reissue_count,
                    'original_collected_at' => $r->original_collected_at?->toDateString(),
                    'original_collected_by' => $r->original_collected_by,
                ])->values()->all(),
            ];
        })->values()->all();

        return Inertia::render('Academic/Graduation/Index', [
            'years' => $years,
            'batches' => $serializedBatches,
            'students' => $this->academicQuery->searchActiveStudentsForStaff($schoolId, '', 60),
            'issuedPin' => $request->session()->get('issued_graduation_pin'),
        ]);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'skl_prefix' => ['nullable', 'string', 'max:40'],
            'release_at' => ['nullable', 'date'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
        ]);

        $this->graduationService->createBatch($request->user(), $validated);

        return back()->with('message', 'Batch kelulusan berhasil dibuat.');
    }

    public function upsertRecord(Request $request, GraduationBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'decision' => ['required', 'in:graduated,not_graduated'],
            'skl_number' => ['nullable', 'string', 'max:80'],
            'average_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'access_pin' => ['nullable', 'string', 'min:4', 'max:12'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $res = $this->graduationService->upsertRecord($request->user(), $batch, $validated);

        return back()
            ->with('message', 'Data kelulusan siswa berhasil disimpan.')
            ->with('issued_graduation_pin', $res['plain_pin'] ? [
                'student_id' => (int) $res['record']->student_id,
                'pin' => $res['plain_pin'],
            ] : null);
    }

    public function publishBatch(Request $request, GraduationBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'signatory_name' => ['required', 'string', 'max:150'],
            'signatory_title' => ['required', 'string', 'max:150'],
            'release_at' => ['nullable', 'date'],
        ]);

        $this->graduationService->publishBatch(
            $request->user(),
            $batch,
            $validated['signatory_name'],
            $validated['signatory_title'],
            $validated['release_at'] ?? null,
        );

        return back()->with('message', 'Batch kelulusan berhasil disahkan dan diterbitkan.');
    }

    public function reissueSkl(Request $request, GraduationRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->graduationService->reissueSkl($request->user(), $record, $validated['reason']);

        return back()->with('message', 'SKL berhasil diterbitkan ulang (SKL sebelumnya telah dicabut).');
    }

    public function markCollected(Request $request, GraduationRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'collected_by' => ['required', 'string', 'max:150'],
            'relation' => ['nullable', 'string', 'max:60'],
        ]);

        $this->graduationService->markOriginalCollected(
            $request->user(),
            $record,
            $validated['collected_by'],
            (string) ($validated['relation'] ?? 'Siswa Bersangkutan'),
        );

        return back()->with('message', 'Pengambilan dokumen kelulusan asli berhasil dicatat.');
    }
}
