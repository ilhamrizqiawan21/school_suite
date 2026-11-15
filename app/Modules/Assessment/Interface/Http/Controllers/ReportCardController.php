<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\AssessmentAccessService;
use App\Modules\Assessment\Application\Services\ReportGeneratorService;
use App\Modules\Assessment\Application\Services\ReportPublishingService;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportCardController extends Controller
{
    public function __construct(
        protected ReportPublishingService $publishingService,
        protected ReportGeneratorService $generatorService,
        protected AcademicQueryService $academicQuery,
        protected AssessmentAccessService $access
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', ReportSnapshot::class);

        $schoolId = (int) $request->user()->school_id;
        $isStudent = $request->user()->hasRole('student');
        $academicYears = $this->academicQuery->getAcademicYears($schoolId);
        $allTerms = $this->academicQuery->getTermsForSchool($schoolId);
        $defaultTerm = $this->academicQuery->getActiveTerm($schoolId) ?? $allTerms->first();

        $requestedTermId = $request->query('term_id') ? (int) $request->query('term_id') : null;
        $requestedYearId = $request->query('academic_year_id') ? (int) $request->query('academic_year_id') : null;
        $reportMode = in_array($request->query('report_mode'), ['sas', 'sts'], true)
            ? (string) $request->query('report_mode')
            : 'sas';

        if ($requestedTermId) {
            $activeTerm = $allTerms->firstWhere('id', $requestedTermId);
            abort_if(! $activeTerm, 404);
        } elseif ($requestedYearId) {
            $yearTerms = $allTerms->where('academic_year_id', $requestedYearId)->values();
            abort_if($yearTerms->isEmpty() && ! $academicYears->contains('id', $requestedYearId), 404);
            $activeTerm = $yearTerms->firstWhere('is_active', true) ?? $yearTerms->first();
        } else {
            $activeTerm = $defaultTerm;
        }

        $selectedAcademicYearId = $activeTerm?->academic_year_id
            ?? $requestedYearId
            ?? ($academicYears->first()?->id ? (int) $academicYears->first()->id : null);

        $studentId = $isStudent
            ? DB::table('students')->where('user_id', $request->user()->id)->where('school_id', $schoolId)->value('id')
            : null;
        $studentEnrollment = $isStudent && $activeTerm && $studentId
            ? DB::table('enrollments')->where('student_id', $studentId)->where('term_id', $activeTerm->id)->first(['class_group_id'])
            : null;

        $classes = $activeTerm
            ? $this->academicQuery->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();
        if ($isStudent) {
            $classes = $classes->where('id', $studentEnrollment?->class_group_id)->values();
        } elseif ($activeTerm) {
            $classes = $classes->filter(fn ($class) => $this->access->canViewClass($request->user(), $activeTerm->id, (int) $class->id))->values();
        }

        $classGroupId = $isStudent
            ? $studentEnrollment?->class_group_id
            : $request->query('class_group_id', $classes->first()?->id ? (string) $classes->first()->id : null);

        abort_if($classGroupId && ! $classes->contains('id', (int) $classGroupId), 403);

        $reports = collect();
        $readiness = null;
        if ($activeTerm && $classGroupId) {
            $reports = ReportSnapshot::where('school_id', $schoolId)
                ->where('term_id', $activeTerm->id)
                ->where('class_group_id', (int) $classGroupId)
                ->when($isStudent, fn ($query) => $query->where('student_id', $studentId))
                ->when($isStudent, fn ($query) => $query->where('status', 'published'))
                ->get();
            $students = DB::table('students')->where('school_id', $schoolId)->whereIn('id', $reports->pluck('student_id'))->get(['id', 'name', 'nis'])->keyBy('id');
            $explicitMode = $request->filled('report_mode') ? $reportMode : null;
            $reports->each(function ($report) use ($students, $explicitMode): void {
                $report->setAttribute('student_name', $students->get($report->student_id)?->name ?? 'Siswa');
                $report->setAttribute('nis', $students->get($report->student_id)?->nis ?? '—');
                $mode = $explicitMode ?? (string) data_get($report->scheme_snapshot, 'report_mode', 'sas');
                $report->setAttribute('report_mode', $mode);
                $modeGrades = data_get($report->scheme_snapshot, "grades_by_mode.{$mode}");
                if (is_array($modeGrades) && $modeGrades !== []) {
                    $report->setAttribute('grades_snapshot', $modeGrades);
                }
            });

            if (! $isStudent) {
                $readiness = $this->generatorService->getClassReadiness($schoolId, (int) $activeTerm->id, (int) $classGroupId, $reportMode);
            }
        }

        return Inertia::render('Assessment/Reports/Index', [
            'active_term' => $activeTerm,
            'academic_years' => $academicYears->map(fn ($y) => [
                'id' => (int) $y->id,
                'name' => (string) $y->name,
                'is_active' => (bool) $y->is_active,
            ])->values(),
            'terms' => $allTerms->map(fn ($t) => [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
                'academic_year_id' => (int) $t->academic_year_id,
                'academic_year_name' => (string) ($t->academicYear?->name ?? '—'),
                'is_active' => (bool) $t->is_active,
            ])->values(),
            'selected_academic_year_id' => $selectedAcademicYearId ? (string) $selectedAcademicYearId : '',
            'selected_term_id' => $activeTerm ? (string) $activeTerm->id : '',
            'selected_report_mode' => $reportMode,
            'classes' => $classes,
            'selected_class_id' => $classGroupId ? (string) $classGroupId : '',
            'reports' => $reports,
            'readiness' => $readiness,
            'is_student' => $isStudent,
            'can_publish' => ! $isStudent && Gate::allows('publishReports', ReportSnapshot::class),
        ]);
    }

    public function show(Request $request, ReportSnapshot $report): InertiaResponse
    {
        Gate::authorize('viewReport', $report);

        $requestedMode = in_array($request->query('report_mode'), ['sas', 'sts'], true)
            ? (string) $request->query('report_mode')
            : null;
        $reportData = $this->generatorService->formatReportView($report, $requestedMode);
        $reportData['integrity_valid'] = $this->publishingService->verifyReportIntegrity($report);

        return Inertia::render('Assessment/Reports/Show', [
            'report' => $reportData,
            'can_publish' => ! $request->user()->hasRole('student') && Gate::allows('publishReports', $report),
        ]);
    }

    public function generateBatch(Request $request): RedirectResponse
    {
        Gate::authorize('publishReports', ReportSnapshot::class);

        $validated = $request->validate([
            'class_group_id' => ['required', 'exists:class_groups,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'report_mode' => ['nullable', 'in:sas,sts'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = (int) $validated['class_group_id'];
        $termId = (int) $validated['term_id'];
        $reportMode = (string) ($validated['report_mode'] ?? 'sas');

        abort_unless($this->access->canPublishClass($request->user(), $termId, $classGroupId), 403);
        $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($termId, $classGroupId);
        $publishedEnrollmentIds = ReportSnapshot::where('school_id', $schoolId)
            ->where('term_id', $termId)->where('class_group_id', $classGroupId)
            ->where('status', 'published')->pluck('enrollment_id');

        $count = 0;
        foreach ($enrollments as $enr) {
            if ($publishedEnrollmentIds->contains((int) $enr->enrollment_id)) {
                continue;
            }
            $this->publishingService->generateReportSnapshot(
                schoolId: $schoolId,
                enrollmentId: (int) $enr->enrollment_id,
                termId: $termId,
                classGroupId: $classGroupId,
                studentId: (int) $enr->student_id,
                actor: $request->user(),
                reportMode: $reportMode,
            );
            $count++;
        }

        return back()->with('message', "Berhasil menyusun {$count} draf snapshot rapor kelas.");
    }

    public function updateSupplements(Request $request, ReportSnapshot $report): RedirectResponse
    {
        Gate::authorize('publishReports', $report);

        $validated = $request->validate([
            'report_mode' => ['nullable', 'in:sas,sts'],
            'extracurricular' => ['nullable', 'array', 'max:10'],
            'extracurricular.*.name' => ['nullable', 'string', 'max:120'],
            'extracurricular.*.predicate' => ['nullable', 'string', 'max:30'],
            'extracurricular.*.description' => ['nullable', 'string', 'max:500'],
            'homeroom_report_note' => ['nullable', 'string', 'max:2000'],
            'promotion_decision' => ['nullable', 'string', 'max:255'],
            'report_place' => ['nullable', 'string', 'max:120'],
            'report_date' => ['nullable', 'string', 'max:120'],
            'grade_competencies' => ['nullable', 'array'],
            'grade_competencies.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->publishingService->updateReportSupplements($report, $validated, $request->user());

        return back()->with('message', 'Data pelengkap rapor berhasil disimpan.');
    }

    public function publish(Request $request, ReportSnapshot $report): RedirectResponse
    {
        Gate::authorize('publishReports', $report);

        $this->publishingService->publishReport($report, $request->user());

        return back()->with('message', 'Rapor berhasil diterbitkan dan dikunci permanen.');
    }

    public function publishBatch(Request $request): RedirectResponse
    {
        Gate::authorize('publishReports', ReportSnapshot::class);

        $validated = $request->validate([
            'class_group_id' => ['required', 'exists:class_groups,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = (int) $validated['class_group_id'];
        $termId = (int) $validated['term_id'];

        abort_unless($this->access->canPublishClass($request->user(), $termId, $classGroupId), 403);

        $drafts = ReportSnapshot::where('school_id', $schoolId)
            ->where('term_id', $termId)
            ->where('class_group_id', $classGroupId)
            ->where('status', '!=', 'published')
            ->get();

        $count = 0;
        foreach ($drafts as $report) {
            $this->publishingService->publishReport($report, $request->user());
            $count++;
        }

        return back()->with('message', "Berhasil menerbitkan {$count} rapor kelas.");
    }

    public function ledger(Request $request): JsonResponse|InertiaResponse|RedirectResponse
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'principal', 'teacher', 'homeroom']), 403);
        $schoolId = (int) $request->user()->school_id;
        $reportMode = in_array($request->query('report_mode'), ['sas', 'sts'], true)
            ? (string) $request->query('report_mode')
            : 'sas';

        $academicYears = $this->academicQuery->getAcademicYears($schoolId);
        $allTerms = $this->academicQuery->getTermsForSchool($schoolId);
        $defaultTerm = $this->academicQuery->getActiveTerm($schoolId) ?? $allTerms->first();

        $explicitClassId = $request->query('class_group_id') ? (int) $request->query('class_group_id') : 0;
        $explicitTermId = $request->query('term_id') ? (int) $request->query('term_id') : 0;
        $explicitYearId = $request->query('academic_year_id') ? (int) $request->query('academic_year_id') : 0;

        if ($explicitClassId > 0 && $explicitTermId > 0) {
            abort_unless($this->access->canViewClass($request->user(), $explicitTermId, $explicitClassId), 403);
            $selectedTerm = $allTerms->firstWhere('id', $explicitTermId);
            $termId = $explicitTermId;
            $classGroupId = $explicitClassId;
        } else {
            if ($explicitTermId > 0) {
                $selectedTerm = $allTerms->firstWhere('id', $explicitTermId);
                abort_if(! $selectedTerm, 404);
            } elseif ($explicitYearId > 0) {
                $yearTerms = $allTerms->where('academic_year_id', $explicitYearId)->values();
                abort_if($yearTerms->isEmpty() && ! $academicYears->contains('id', $explicitYearId), 404);
                $selectedTerm = $yearTerms->firstWhere('is_active', true) ?? $yearTerms->first();
            } else {
                $selectedTerm = $defaultTerm;
            }

            $termId = (int) ($selectedTerm?->id ?? 0);
            $availableClasses = $selectedTerm
                ? $this->academicQuery->getClassGroups($schoolId, $selectedTerm->academic_year_id)
                    ->filter(fn ($class) => $this->access->canViewClass($request->user(), $termId, (int) $class->id))
                    ->values()
                : collect();

            if ($explicitClassId > 0) {
                abort_unless($termId > 0 && $this->access->canViewClass($request->user(), $termId, $explicitClassId), 403);
                $classGroupId = $explicitClassId;
            } else {
                $classGroupId = (int) ($availableClasses->first()?->id ?? 0);
            }
        }

        $selectedAcademicYearId = $selectedTerm?->academic_year_id
            ?? ($explicitYearId > 0 ? $explicitYearId : ($academicYears->first()?->id ? (int) $academicYears->first()->id : null));

        $classes = $selectedTerm
            ? $this->academicQuery->getClassGroups($schoolId, $selectedTerm->academic_year_id)
                ->filter(fn ($class) => $this->access->canViewClass($request->user(), (int) $selectedTerm->id, (int) $class->id))
                ->values()
            : collect();

        $ledgerData = ($termId > 0 && $classGroupId > 0)
            ? $this->generatorService->exportClassLedger($schoolId, $termId, $classGroupId, $reportMode)
            : [
                'report_mode' => $reportMode,
                'class_group_name' => '-',
                'class_group' => null,
                'term_name' => $selectedTerm?->name ?? '-',
                'term' => $selectedTerm ? [
                    'id' => $selectedTerm->id,
                    'name' => $selectedTerm->name,
                    'academic_year_id' => $selectedTerm->academic_year_id,
                    'academic_year_name' => $selectedTerm->academicYear?->name ?? '-',
                ] : null,
                'subjects' => [],
                'students' => [],
            ];

        if ($request->wantsJson()) {
            return response()->json($ledgerData);
        }

        return Inertia::render('Assessment/Reports/Ledger', [
            'ledger' => $ledgerData,
            'academic_years' => $academicYears->map(fn ($y) => [
                'id' => (int) $y->id,
                'name' => (string) $y->name,
                'is_active' => (bool) $y->is_active,
            ])->values(),
            'terms' => $allTerms->map(fn ($t) => [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
                'academic_year_id' => (int) $t->academic_year_id,
                'academic_year_name' => (string) ($t->academicYear?->name ?? '—'),
                'is_active' => (bool) $t->is_active,
            ])->values(),
            'classes' => $classes->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'grade_level' => (int) $c->grade_level,
            ])->values(),
            'selected_academic_year_id' => $selectedAcademicYearId ? (string) $selectedAcademicYearId : '',
            'selected_term_id' => $termId > 0 ? (string) $termId : '',
            'selected_class_id' => $classGroupId > 0 ? (string) $classGroupId : '',
            'selected_report_mode' => $reportMode,
        ]);
    }
}
