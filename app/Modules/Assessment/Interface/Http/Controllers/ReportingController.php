<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\ReportGeneratorService;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
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
    public function __construct(private readonly AcademicQueryService $academicQuery, private readonly ReportGeneratorService $reportGenerator) {}

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
        $data = $this->reportGenerator->formatReportView($report);

        return Pdf::loadView('reports.report-card', ['report' => $data])->setPaper('a4')->download('rapor-'.str($data['student']['name'])->slug().'-'.$report->id.'.pdf');
    }

    public function scores(Request $request): StreamedResponse
    {
        $context = $this->validatedContext($request);
        $ledger = $this->reportGenerator->exportClassLedger($context['school_id'], $context['term']->id, $context['class']->id);
        $headers = ['NIS', 'Nama', ...array_map(fn ($s) => $s['name'], $ledger['subjects']), 'Rata-rata', 'Status Rapor'];
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai '.$context['class']->name);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);

        foreach ($ledger['students'] as $index => $student) {
            $row = $index + 2;
            $sheet->setCellValueExplicit('A'.$row, (string) $student['nis'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$row, (string) $student['name'], DataType::TYPE_STRING);
            $column = 3;
            foreach ($ledger['subjects'] as $subject) {
                $score = $student['grades'][$subject['id']] ?? null;
                if ($score !== null) {
                    $sheet->setCellValue([$column, $row], (float) $score);
                }
                $column++;
            }
            $sheet->setCellValue([$column++, $row], (float) $student['average_score']);
            $sheet->setCellValueExplicit([$column, $row], (string) $student['report_status'], DataType::TYPE_STRING);
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'nilai-'.$context['slug'].'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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

    private function validatedContext(Request $request): array
    {
        $this->authorizeLeader($request);
        $schoolId = (int) $request->user()->school_id;
        $term = $this->academicQuery->getActiveTerm($schoolId);
        abort_unless($term, 422);
        $data = $request->validate(['class_group_id' => ['required', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)]]);
        $class = $this->academicQuery->getClassGroupById((int) $data['class_group_id']);
        abort_unless($class && (int) $class->academic_year_id === (int) $term->academic_year_id, 422);

        return ['school_id' => $schoolId, 'term' => $term, 'class' => $class, 'slug' => str($class->name)->slug()];
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
                fputcsv($out, $row);
            }fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeLeader(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'principal']), 403);
    }
}
