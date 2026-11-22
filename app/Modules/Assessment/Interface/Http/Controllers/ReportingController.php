<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\AssessmentAccessService;
use App\Modules\Assessment\Application\Services\ReportGeneratorService;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Shared\Exports\SpreadsheetStyler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingController extends Controller
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
        private readonly ReportGeneratorService $reportGenerator,
        private readonly AssessmentAccessService $access,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeLeader($request);
        $schoolId = (int) $request->user()->school_id;
        $term = $this->academicQuery->getActiveTerm($schoolId);
        $classes = $term ? $this->academicQuery->getClassGroups($schoolId, $term->academic_year_id) : collect();
        $classIds = $classes->pluck('id');
        $enrollments = $term ? DB::table('enrollments')->where('term_id', $term->id)->whereIn('class_group_id', $classIds)->where('status', 'active')->get() : collect();
        $enrollmentIds = $enrollments->pluck('id');
        $attendance = DB::table('attendance_records')->where('school_id', $schoolId)->whereIn('enrollment_id', $enrollmentIds)->get(['date', 'status']);
        $present = $attendance->where('status', 'present')->count();
        $attendanceRate = $attendance->count() ? round($present / $attendance->count() * 100, 1) : 0;
        $averageScore = (float) (DB::table('scores')->where('school_id', $schoolId)->whereIn('enrollment_id', $enrollmentIds)->avg('final_score') ?? 0);
        $assignmentIds = $term ? DB::table('learning_assignments')->join('teaching_assignments', 'teaching_assignments.id', '=', 'learning_assignments.teaching_assignment_id')->where('teaching_assignments.term_id', $term->id)->whereIn('teaching_assignments.class_group_id', $classIds)->pluck('learning_assignments.id') : collect();
        $submissionCount = DB::table('assignment_submissions')->whereIn('learning_assignment_id', $assignmentIds)->count();
        $expected = 0;
        if ($term) {
            foreach (DB::table('learning_assignments')->join('teaching_assignments', 'teaching_assignments.id', '=', 'learning_assignments.teaching_assignment_id')->where('teaching_assignments.term_id', $term->id)->whereIn('teaching_assignments.class_group_id', $classIds)->get(['teaching_assignments.class_group_id']) as $a) {
                $expected += $enrollments->where('class_group_id', $a->class_group_id)->count();
            }
        }
        $monthly = $attendance->groupBy(fn ($r) => substr((string) $r->date, 0, 7))->map(fn ($items, $month) => ['month' => $month, 'rate' => $items->count() ? round($items->where('status', 'present')->count() / $items->count() * 100, 1) : 0])->values();
        $teachers = $this->teacherMetrics($schoolId, $term?->id);
        $selectedClassId = $enrollments->first()?->class_group_id ?? $classes->first()?->id;

        return Inertia::render('Assessment/Reporting/Index', ['active_term' => $term, 'classes' => $classes, 'selected_class_id' => $selectedClassId, 'metrics' => ['students' => $enrollments->count(), 'attendance_rate' => $attendanceRate, 'average_score' => round($averageScore, 1), 'assignment_completion' => $expected ? round($submissionCount / $expected * 100, 1) : 0, 'homeroom_coverage' => $classes->count() ? round($classes->whereNotNull('homeroom_teacher_id')->count() / $classes->count() * 100, 1) : 0], 'trends' => $monthly, 'teachers' => $teachers]);
    }

    public function reportPdf(Request $request, ReportSnapshot $report): HttpResponse
    {
        abort_unless((int) $report->school_id === (int) $request->user()->school_id, 404);
        abort_unless($request->user()->can('viewReport', $report), 403);
        $requestedMode = in_array($request->query('report_mode'), ['sas', 'sts'], true)
            ? (string) $request->query('report_mode')
            : null;
        $data = $this->reportGenerator->formatReportView($report, $requestedMode);
        $identityOnly = $request->boolean('identity_only');
        $gradesOnly = ! $identityOnly && $request->boolean('grades_only');
        $includeCover = ($gradesOnly || $identityOnly)
            ? false
            : ($request->has('cover') ? $request->boolean('cover') : (($data['report_mode'] ?? 'sas') === 'sas'));
        $modeSlug = ($data['report_mode'] ?? 'sas') === 'sts' ? 'sts-' : '';
        $scopeSlug = $identityOnly ? 'identitas-' : ($gradesOnly ? 'nilai-' : '');
        $filename = 'rapor-'.$scopeSlug.$modeSlug.str($data['student']['name'])->slug().'-'.$report->id.'.pdf';
        $pdf = Pdf::loadView('reports.report-card', [
            'report' => $data,
            'reports' => [$data],
            'includeCover' => $includeCover,
            'gradesOnly' => $gradesOnly,
            'identityOnly' => $identityOnly,
        ])->setPaper('a4');

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function reportBatchPdf(Request $request): HttpResponse
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'principal', 'teacher', 'homeroom']), 403);
        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)],
            'report_mode' => ['nullable', 'in:sas,sts'],
            'student_ids' => ['nullable'],
            'cover' => ['nullable', 'boolean'],
            'grades_only' => ['nullable', 'boolean'],
            'identity_only' => ['nullable', 'boolean'],
        ]);

        $term = ! empty($validated['term_id'])
            ? $this->academicQuery->getTermByIdForSchool($schoolId, (int) $validated['term_id'])
            : $this->academicQuery->getActiveTerm($schoolId);
        abort_unless($term, 422);

        $classGroupId = (int) $validated['class_group_id'];
        abort_unless($this->access->canViewClass($request->user(), (int) $term->id, $classGroupId), 403);
        $class = $this->academicQuery->getClassGroupById($classGroupId);
        abort_unless($class && (int) $class->academic_year_id === (int) $term->academic_year_id, 422);

        $studentIds = [];
        if (! empty($validated['student_ids'])) {
            $rawIds = is_array($validated['student_ids'])
                ? $validated['student_ids']
                : explode(',', (string) $validated['student_ids']);
            $studentIds = array_values(array_filter(array_map('intval', $rawIds), fn (int $id) => $id > 0));
        }

        $query = ReportSnapshot::where('school_id', $schoolId)
            ->where('term_id', $term->id)
            ->where('class_group_id', $classGroupId);

        if ($studentIds !== []) {
            $query->whereIn('student_id', $studentIds);
        }

        $snapshots = $query->limit(60)->get();

        abort_if($snapshots->isEmpty(), 404, 'Belum ada rapor untuk rombel ini.');

        $reportMode = in_array($validated['report_mode'] ?? null, ['sas', 'sts'], true)
            ? (string) $validated['report_mode']
            : null;
        $reports = $snapshots->map(fn (ReportSnapshot $snap) => $this->reportGenerator->formatReportView($snap, $reportMode))->all();
        $effectiveMode = $reportMode ?? ($reports[0]['report_mode'] ?? 'sas');
        $identityOnly = $request->boolean('identity_only');
        $gradesOnly = ! $identityOnly && $request->boolean('grades_only');
        $includeCover = ($gradesOnly || $identityOnly)
            ? false
            : ($request->has('cover') ? $request->boolean('cover') : ($effectiveMode === 'sas'));
        $modeSlug = $effectiveMode === 'sts' ? 'sts-' : '';
        $scopeSlug = $identityOnly ? 'identitas-' : ($gradesOnly ? 'nilai-' : '');
        $filename = 'rapor-'.$scopeSlug.$modeSlug.'kelas-'.str($class->name)->slug().'-'.now()->format('Ymd').'.pdf';
        $pdf = Pdf::loadView('reports.report-card', [
            'report' => $reports[0],
            'reports' => $reports,
            'includeCover' => $includeCover,
            'gradesOnly' => $gradesOnly,
            'identityOnly' => $identityOnly,
        ])->setPaper('a4');

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function ledgerExcel(Request $request): StreamedResponse
    {
        $context = $this->validatedLedgerContext($request);

        return $this->streamLedgerSpreadsheet($context);
    }

    public function ledgerPdf(Request $request): HttpResponse
    {
        $context = $this->validatedLedgerContext($request);
        $ledger = $this->reportGenerator->exportClassLedger(
            $context['school_id'],
            (int) $context['term']->id,
            (int) $context['class']->id,
            $context['report_mode']
        );

        $modeSlug = $context['report_mode'] === 'sts' ? 'sts-' : '';
        $filename = 'ledger-'.$modeSlug.$context['slug'].'-'.now()->format('Ymd').'.pdf';
        $pdf = Pdf::loadView('reports.class-ledger', [
            'ledger' => $ledger,
        ])->setPaper('a4', 'landscape');

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function scores(Request $request): StreamedResponse
    {
        $context = $this->validatedContext($request);

        return $this->streamLedgerSpreadsheet($context, 'nilai-'.$context['slug'].'.xlsx');
    }

    private function streamLedgerSpreadsheet(array $context, ?string $customFilename = null): StreamedResponse
    {
        $reportMode = $context['report_mode'] ?? 'sas';
        $ledger = $this->reportGenerator->exportClassLedger(
            $context['school_id'],
            (int) $context['term']->id,
            (int) $context['class']->id,
            $reportMode
        );
        $headers = [
            'No',
            'NIS',
            'Nama',
            ...array_map(fn ($s) => $s['name'], $ledger['subjects']),
            'Jumlah Nilai',
            'Rata-rata',
            'Peringkat',
            'Sakit (S)',
            'Izin (I)',
            'Alpa (A)',
            'Status Rapor',
        ];
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr('Ledger '.$context['class']->name, 0, 31));
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);

        foreach ($ledger['students'] as $index => $student) {
            $row = $index + 2;
            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValueExplicit('B'.$row, (string) $student['nis'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$row, (string) $student['name'], DataType::TYPE_STRING);
            $column = 4;
            foreach ($ledger['subjects'] as $subject) {
                $score = $student['grades'][$subject['id']] ?? null;
                if ($score !== null) {
                    $sheet->setCellValue([$column, $row], (float) $score);
                }
                $column++;
            }
            $sheet->setCellValue([$column++, $row], (float) ($student['total_score'] ?? 0));
            $sheet->setCellValue([$column++, $row], (float) ($student['average_score'] ?? 0));
            $sheet->setCellValue([$column++, $row], $student['rank'] ?? '-');
            $sheet->setCellValue([$column++, $row], (int) ($student['attendance']['sick'] ?? 0));
            $sheet->setCellValue([$column++, $row], (int) ($student['attendance']['permitted'] ?? 0));
            $sheet->setCellValue([$column++, $row], (int) ($student['attendance']['absent'] ?? 0));
            $sheet->setCellValueExplicit([$column, $row], (string) $student['report_status'], DataType::TYPE_STRING);
        }

        SpreadsheetStyler::styleWorksheet($sheet, 'D2');

        $modeSlug = $reportMode === 'sts' ? 'sts-' : '';
        $filename = $customFilename ?? ('ledger-nilai-'.$modeSlug.$context['slug'].'.xlsx');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function attendance(Request $request): StreamedResponse
    {
        $context = $this->validatedContext($request);
        $rows = DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')->leftJoin('attendance_records', 'attendance_records.enrollment_id', '=', 'enrollments.id')->where('enrollments.term_id', $context['term']->id)->where('enrollments.class_group_id', $context['class']->id)->groupBy('enrollments.id', 'students.nis', 'students.name')->selectRaw("students.nis,students.name,SUM(CASE WHEN attendance_records.status='present' THEN 1 ELSE 0 END) present,SUM(CASE WHEN attendance_records.status='sick' THEN 1 ELSE 0 END) sick,SUM(CASE WHEN attendance_records.status='permitted' THEN 1 ELSE 0 END) permitted,SUM(CASE WHEN attendance_records.status='absent' THEN 1 ELSE 0 END) absent")->get()->map(fn ($r) => (array) $r)->all();

        return $this->csv('presensi-'.$context['slug'].'.csv', ['NIS', 'Nama', 'Hadir', 'Sakit', 'Izin', 'Alpa'], $rows);
    }

    public function assignments(Request $request): StreamedResponse
    {
        $context = $this->validatedContext($request);
        $rows = DB::table('learning_assignments as la')->join('teaching_assignments as ta', 'ta.id', '=', 'la.teaching_assignment_id')->join('subjects', 'subjects.id', '=', 'ta.subject_id')->join('enrollments', 'enrollments.class_group_id', '=', 'ta.class_group_id')->join('students', 'students.id', '=', 'enrollments.student_id')->leftJoin('assignment_submissions as s', fn ($j) => $j->on('s.learning_assignment_id', '=', 'la.id')->on('s.enrollment_id', '=', 'enrollments.id'))->where('ta.term_id', $context['term']->id)->where('ta.class_group_id', $context['class']->id)->where('enrollments.term_id', $context['term']->id)->get(['students.nis', 'students.name', 'subjects.name as subject', 'la.title', 'la.due_at', 's.submitted_at', DB::raw("COALESCE(s.status,'not_submitted') status"), DB::raw("CASE WHEN s.submitted_at IS NULL THEN '-' WHEN s.submitted_at > la.due_at THEN 'terlambat' ELSE 'tepat waktu' END timeliness"), 's.score', 's.feedback'])->map(fn ($r) => (array) $r)->all();

        return $this->csv('tugas-'.$context['slug'].'.csv', ['NIS', 'Nama', 'Mapel', 'Tugas', 'Tenggat', 'Dikumpulkan', 'Status', 'Ketepatan', 'Nilai', 'Feedback'], $rows);
    }

    public function attitudes(Request $request): StreamedResponse
    {
        $context = $this->validatedContext($request);
        $rows = DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')->leftJoin('student_attitudes as spiritual', fn ($j) => $j->on('spiritual.enrollment_id', '=', 'enrollments.id')->where('spiritual.type', 'spiritual'))->leftJoin('student_attitudes as social', fn ($j) => $j->on('social.enrollment_id', '=', 'enrollments.id')->where('social.type', 'social'))->where('enrollments.term_id', $context['term']->id)->where('enrollments.class_group_id', $context['class']->id)->get(['students.nis', 'students.name', 'spiritual.predicate as spiritual_predicate', 'spiritual.description as spiritual_description', 'social.predicate as social_predicate', 'social.description as social_description'])->map(fn ($r) => (array) $r)->all();

        return $this->csv('sikap-'.$context['slug'].'.csv', ['NIS', 'Nama', 'Predikat Spiritual', 'Deskripsi Spiritual', 'Predikat Sosial', 'Deskripsi Sosial'], $rows);
    }

    private function validatedLedgerContext(Request $request): array
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'principal', 'teacher', 'homeroom']), 403);
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)],
            'report_mode' => ['nullable', 'in:sas,sts'],
        ]);
        $term = ! empty($data['term_id'])
            ? $this->academicQuery->getTermByIdForSchool($schoolId, (int) $data['term_id'])
            : $this->academicQuery->getActiveTerm($schoolId);
        abort_unless($term, 422);

        $classGroupId = (int) $data['class_group_id'];
        abort_unless($this->access->canViewClass($request->user(), (int) $term->id, $classGroupId), 403);
        $class = $this->academicQuery->getClassGroupById($classGroupId);
        abort_unless($class && (int) $class->academic_year_id === (int) $term->academic_year_id, 422);

        return [
            'school_id' => $schoolId,
            'term' => $term,
            'class' => $class,
            'slug' => str($class->name)->slug(),
            'report_mode' => in_array($data['report_mode'] ?? null, ['sas', 'sts'], true) ? (string) $data['report_mode'] : 'sas',
        ];
    }

    private function validatedContext(Request $request): array
    {
        $this->authorizeLeader($request);
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)],
            'report_mode' => ['nullable', 'in:sas,sts'],
        ]);
        $term = ! empty($data['term_id'])
            ? $this->academicQuery->getTermByIdForSchool($schoolId, (int) $data['term_id'])
            : $this->academicQuery->getActiveTerm($schoolId);
        abort_unless($term, 422);
        $class = $this->academicQuery->getClassGroupById((int) $data['class_group_id']);
        abort_unless($class && (int) $class->academic_year_id === (int) $term->academic_year_id, 422);

        return [
            'school_id' => $schoolId,
            'term' => $term,
            'class' => $class,
            'slug' => str($class->name)->slug(),
            'report_mode' => in_array($data['report_mode'] ?? null, ['sas', 'sts'], true) ? (string) $data['report_mode'] : 'sas',
        ];
    }

    private function teacherMetrics(int $schoolId, ?int $termId): array
    {
        if (! $termId) {
            return [];
        }$teachers = DB::table('teachers')->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();

        return $teachers->map(function ($t) use ($termId) {
            $ids = DB::table('teaching_assignments')->where('term_id', $termId)->where('teacher_id', $t->id)->pluck('id');
            $sessions = DB::table('lesson_sessions')->where('term_id', $termId)->where('teacher_id', $t->id)->count();
            $journals = DB::table('teaching_journals')->join('lesson_sessions', 'lesson_sessions.id', '=', 'teaching_journals.lesson_session_id')->where('lesson_sessions.term_id', $termId)->where('lesson_sessions.teacher_id', $t->id)->count();

            return ['id' => $t->id, 'name' => $t->name, 'assignments' => $ids->count(), 'sessions' => $sessions, 'journals' => $journals, 'journal_rate' => $sessions ? round($journals / $sessions * 100, 1) : 0, 'materials' => DB::table('learning_materials')->whereIn('teaching_assignment_id', $ids)->count(), 'tasks' => DB::table('learning_assignments')->whereIn('teaching_assignment_id', $ids)->count(), 'exams' => DB::table('exams')->whereIn('teaching_assignment_id', $ids)->count()];
        })->all();
    }

    private function csv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(static function (mixed $cell): mixed {
                    if (! is_string($cell)) {
                        return $cell;
                    }
                    $trimmed = ltrim($cell);

                    return ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) ? "'".$cell : $cell;
                }, (array) $row));
            }fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeLeader(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'principal']), 403);
    }
}
