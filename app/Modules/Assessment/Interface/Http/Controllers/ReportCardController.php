<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\ReportGeneratorService;
use App\Modules\Assessment\Application\Services\ReportPublishingService;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportCardController extends Controller
{
    public function __construct(
        protected ReportPublishingService $publishingService,
        protected ReportGeneratorService $generatorService,
        protected AcademicQueryService $academicQuery
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', ReportSnapshot::class);

        $schoolId = (int) $request->user()->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);

        $classes = $activeTerm
            ? $this->academicQuery->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();

        $classGroupId = $request->query('class_group_id', $classes->first()?->id ? (string) $classes->first()->id : null);

        $reports = collect();
        if ($activeTerm && $classGroupId) {
            $reports = ReportSnapshot::where('school_id', $schoolId)
                ->where('term_id', $activeTerm->id)
                ->where('class_group_id', (int) $classGroupId)
                ->get();
        }

        return Inertia::render('Assessment/Reports/Index', [
            'active_term' => $activeTerm,
            'classes' => $classes,
            'selected_class_id' => $classGroupId ? (string) $classGroupId : '',
            'reports' => $reports,
        ]);
    }

    public function show(Request $request, ReportSnapshot $report): InertiaResponse
    {
        Gate::authorize('viewReport', $report);

        $reportData = $this->generatorService->formatReportView($report);

        return Inertia::render('Assessment/Reports/Show', [
            'report' => $reportData,
        ]);
    }

    public function generateBatch(Request $request): RedirectResponse
    {
        Gate::authorize('publishReports', ReportSnapshot::class);

        $validated = $request->validate([
            'class_group_id' => ['required', 'exists:class_groups,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = (int) $validated['class_group_id'];
        $termId = (int) $validated['term_id'];

        $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($termId, $classGroupId);

        $count = 0;
        foreach ($enrollments as $enr) {
            $this->publishingService->generateReportSnapshot(
                schoolId: $schoolId,
                enrollmentId: (int) $enr->enrollment_id,
                termId: $termId,
                classGroupId: $classGroupId,
                studentId: (int) $enr->student_id,
                actor: $request->user()
            );
            $count++;
        }

        return back()->with('message', "Berhasil menyusun {$count} draf snapshot rapor kelas.");
    }

    public function publish(Request $request, ReportSnapshot $report): RedirectResponse
    {
        Gate::authorize('publishReports', $report);

        $this->publishingService->publishReport($report, $request->user());

        return back()->with('message', 'Rapor berhasil diterbitkan dan dikunci permanen.');
    }

    public function ledger(Request $request): JsonResponse|InertiaResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $classGroupId = (int) $request->query('class_group_id');
        $termId = (int) $request->query('term_id');

        if (! $classGroupId || ! $termId) {
            return back()->with('error', 'Pilih rombel dan semester terlebih dahulu.');
        }

        $ledgerData = $this->generatorService->exportClassLedger($schoolId, $termId, $classGroupId);

        if ($request->wantsJson()) {
            return response()->json($ledgerData);
        }

        return Inertia::render('Assessment/Reports/Ledger', [
            'ledger' => $ledgerData,
        ]);
    }
}
