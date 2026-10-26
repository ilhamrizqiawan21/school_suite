<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Modules\Academic\Infrastructure\Models\Term;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PrincipalDashboardService
{
    /** @return array<string, mixed> */
    public function overview(int $schoolId, ?Term $term, string $period = 'term'): array
    {
        $now = CarbonImmutable::now();
        $from = $term ? CarbonImmutable::parse($term->start_date)->startOfDay() : $now;
        $to = $term ? CarbonImmutable::parse($term->end_date)->endOfDay()->min($now) : $now;
        if ($period === '30d') {
            $from = $from->max($now->subDays(29)->startOfDay());
        }

        $students = $term ? (int) DB::table('students as st')
            ->join('enrollments as en', 'en.student_id', '=', 'st.id')
            ->join('class_groups as cg', 'cg.id', '=', 'en.class_group_id')
            ->where('st.school_id', $schoolId)->where('st.status', 'active')
            ->where('en.term_id', $term->id)->where('en.status', 'active')
            ->where('cg.school_id', $schoolId)->where('cg.academic_year_id', $term->academic_year_id)
            ->distinct()->count('st.id') : 0;
        $teachers = $term ? (int) DB::table('teachers as t')
            ->where('t.school_id', $schoolId)->where('t.is_active', true)
            ->where(function ($query) use ($schoolId, $term) {
                $query->whereExists(fn ($sub) => $sub->selectRaw('1')->from('teaching_assignments as ta')
                    ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
                    ->whereColumn('ta.teacher_id', 't.id')->where('ta.term_id', $term->id)
                    ->where('cg.school_id', $schoolId)->where('cg.academic_year_id', $term->academic_year_id))
                    ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('class_groups as cg')
                        ->whereColumn('cg.homeroom_teacher_id', 't.id')
                        ->where('cg.school_id', $schoolId)->where('cg.academic_year_id', $term->academic_year_id));
            })->count() : 0;
        $attendanceDays = $term && $from->lte($to)
            ? DB::table('attendance_records as ar')
                ->join('enrollments as en', 'en.id', '=', 'ar.enrollment_id')
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->join('class_groups as cg', 'cg.id', '=', 'en.class_group_id')
                ->where('ar.school_id', $schoolId)
                ->where('st.school_id', $schoolId)
                ->where('cg.school_id', $schoolId)
                ->where('cg.academic_year_id', $term->academic_year_id)
                ->where('en.term_id', $term->id)
                ->where('en.status', 'active')
                ->where('st.status', 'active')
                ->whereBetween('ar.date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw("ar.date as day, COUNT(*) as total, SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present")
                ->groupBy('ar.date')
                ->orderBy('ar.date')
                ->get()
            : collect();
        $attendanceTotal = (int) $attendanceDays->sum('total');
        $attendancePresent = (int) $attendanceDays->sum('present');

        $trend = [];
        if ($term && $from->lte($to)) {
            if ($period === '30d') {
                $cursor = $now->subDays(29)->startOfDay();
                while ($cursor->lte($to)) {
                    $end = $cursor->addDays(6)->min($to);
                    $days = $attendanceDays->filter(fn ($day) => $day->day >= $cursor->toDateString() && $day->day <= $end->toDateString());
                    $total = (int) $days->sum('total');
                    $present = (int) $days->sum('present');
                    $trend[] = [
                        'month_key' => $cursor->toDateString(),
                        'label' => $cursor->format('d M').'–'.$end->format('d M'),
                        'rate' => $this->rate($present, $total),
                        'present' => $present,
                        'total' => $total,
                    ];
                    $cursor = $end->addDay();
                }
            } else {
                $cursor = $from->startOfMonth();
                while ($cursor->lte($to)) {
                    $monthKey = $cursor->format('Y-m');
                    $days = $attendanceDays->filter(fn ($day) => str_starts_with((string) $day->day, $monthKey));
                    $total = (int) $days->sum('total');
                    $present = (int) $days->sum('present');
                    $trend[] = [
                        'month_key' => $monthKey,
                        'label' => $cursor->translatedFormat('M Y'),
                        'rate' => $this->rate($present, $total),
                        'present' => $present,
                        'total' => $total,
                    ];
                    $cursor = $cursor->addMonth();
                }
            }
        }

        $taskRows = $term && $from->lte($to)
            ? DB::table('learning_assignments as la')
                ->join('teaching_assignments as ta', 'ta.id', '=', 'la.teaching_assignment_id')
                ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
                ->join('enrollments as en', function ($join) use ($term) {
                    $join->on('en.class_group_id', '=', 'cg.id')->where('en.term_id', $term->id)->where('en.status', 'active');
                })
                ->join('students as st', 'st.id', '=', 'en.student_id')
                ->leftJoin('assignment_submissions as sub', function ($join) use ($from, $to) {
                    $join->on('sub.learning_assignment_id', '=', 'la.id')
                        ->on('sub.enrollment_id', '=', 'en.id')
                        ->whereIn('sub.status', ['submitted', 'late', 'graded'])
                        ->whereBetween('sub.submitted_at', [$from, $to]);
                })
                ->where('la.school_id', $schoolId)
                ->where('st.school_id', $schoolId)
                ->where('st.status', 'active')
                ->where('cg.school_id', $schoolId)
                ->where('cg.academic_year_id', $term->academic_year_id)
                ->where('ta.term_id', $term->id)
                ->whereBetween('la.published_at', [$from, $to])
                ->selectRaw('cg.grade_level as grade, COUNT(*) as total, COUNT(sub.id) as submitted')
                ->groupBy('cg.grade_level')
                ->orderBy('cg.grade_level')
                ->get()
            : collect();
        $tasksTotal = (int) $taskRows->sum('total');
        $tasksSubmitted = (int) $taskRows->sum('submitted');
        $classRows = $term ? DB::table('class_groups')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $term->academic_year_id)
            ->selectRaw('grade_level as grade, COUNT(*) as classes, SUM(CASE WHEN homeroom_teacher_id IS NULL THEN 1 ELSE 0 END) as missing_homeroom')
            ->groupBy('grade_level')
            ->orderBy('grade_level')
            ->get() : collect();
        $tasksByGrade = $taskRows->keyBy(fn ($row) => (string) $row->grade);
        $gradeNames = ['7' => 'VII', '8' => 'VIII', '9' => 'IX'];
        $byGrade = $classRows->map(function ($row) use ($tasksByGrade, $gradeNames): array {
            $grade = (string) $row->grade;
            $tasks = $tasksByGrade->get($grade);
            $submitted = (int) ($tasks?->submitted ?? 0);
            $total = (int) ($tasks?->total ?? 0);

            return [
                'grade' => $grade,
                'label' => 'Kelas '.($gradeNames[$grade] ?? $grade),
                'submitted' => $submitted,
                'total' => $total,
                'rate' => $this->rate($submitted, $total),
            ];
        })->all();

        $activeClasses = (int) $classRows->sum('classes');
        $missingHomeroom = (int) $classRows->sum('missing_homeroom');
        $reportsTotal = $term ? (int) DB::table('enrollments as en')->join('class_groups as cg', 'cg.id', '=', 'en.class_group_id')->join('students as st', 'st.id', '=', 'en.student_id')->where('en.term_id', $term->id)->where('en.status', 'active')->where('st.status', 'active')->where('st.school_id', $schoolId)->where('cg.school_id', $schoolId)->where('cg.academic_year_id', $term->academic_year_id)->count() : 0;
        $reportsPublished = $term ? (int) DB::table('report_snapshots as rs')->join('enrollments as en', 'en.id', '=', 'rs.enrollment_id')->join('students as st', 'st.id', '=', 'en.student_id')->join('class_groups as cg', 'cg.id', '=', 'en.class_group_id')->where('rs.school_id', $schoolId)->where('rs.term_id', $term->id)->where('rs.status', 'published')->where('en.term_id', $term->id)->where('en.status', 'active')->where('st.school_id', $schoolId)->where('st.status', 'active')->where('cg.school_id', $schoolId)->where('cg.academic_year_id', $term->academic_year_id)->count() : 0;
        $sessionsTotal = $term ? (int) DB::table('lesson_sessions')->where('school_id', $schoolId)->where('term_id', $term->id)->where('date', '<=', $to->toDateString())->where('status', '!=', 'cancelled')->count() : 0;
        $journalsCompleted = $term ? (int) DB::table('teaching_journals as tj')->join('lesson_sessions as ls', 'ls.id', '=', 'tj.lesson_session_id')->where('ls.school_id', $schoolId)->where('ls.term_id', $term->id)->where('ls.date', '<=', $to->toDateString())->where('ls.status', '!=', 'cancelled')->count() : 0;
        $examsLive = $term ? (int) DB::table('exams as ex')->join('teaching_assignments as ta', 'ta.id', '=', 'ex.teaching_assignment_id')->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')->where('ex.school_id', $schoolId)->where('cg.school_id', $schoolId)->where('cg.academic_year_id', $term->academic_year_id)->where('ta.term_id', $term->id)->where('ex.status', 'published')->where('ex.starts_at', '<=', $now)->where('ex.ends_at', '>=', $now)->count() : 0;

        $attention = [];
        if ($attendanceTotal > 0 && $this->rate($attendancePresent, $attendanceTotal) < 90) {
            $attention[] = ['key' => 'attendance', 'title' => 'Kehadiran perlu perhatian', 'detail' => 'Kehadiran tercatat di bawah 90%.', 'href' => '/laporan/presensi', 'level' => 'high'];
        }
        if ($sessionsTotal > $journalsCompleted) {
            $attention[] = ['key' => 'journals', 'title' => 'Jurnal belum lengkap', 'detail' => ($sessionsTotal - $journalsCompleted).' sesi belum memiliki jurnal.', 'href' => '/laporan', 'level' => 'medium'];
        }
        if ($tasksTotal > $tasksSubmitted) {
            $attention[] = ['key' => 'tasks', 'title' => 'Tugas belum terkumpul', 'detail' => ($tasksTotal - $tasksSubmitted).' pengumpulan belum tercatat.', 'href' => '/laporan/tugas', 'level' => 'medium'];
        }
        if ($missingHomeroom > 0) {
            $attention[] = ['key' => 'homeroom', 'title' => 'Wali kelas belum ditetapkan', 'detail' => $missingHomeroom.' rombel belum memiliki wali kelas.', 'href' => '/akademik/kelas', 'level' => 'info'];
        }

        return [
            'period' => $period,
            'summary' => [
                'attendance_rate' => $this->rate($attendancePresent, $attendanceTotal),
                'attendance_present' => $attendancePresent,
                'attendance_total' => $attendanceTotal,
                'tasks_submitted' => $tasksSubmitted,
                'tasks_total' => $tasksTotal,
                'tasks_rate' => $this->rate($tasksSubmitted, $tasksTotal),
                'reports_published' => $reportsPublished,
                'reports_total' => $reportsTotal,
                'exams_live' => $examsLive,
                'students' => $students,
                'teachers' => $teachers,
            ],
            'attendance_trend' => $trend,
            'learning_by_grade' => $byGrade,
            'activity' => [
                'admin' => ['active_classes' => $activeClasses, 'missing_homeroom' => $missingHomeroom],
                'teacher' => ['sessions_total' => $sessionsTotal, 'journals_completed' => $journalsCompleted],
                'homeroom' => ['reports_total' => $reportsTotal, 'reports_published' => $reportsPublished],
                'student' => ['tasks_total' => $tasksTotal, 'tasks_submitted' => $tasksSubmitted],
            ],
            'attention' => $attention,
        ];
    }

    private function rate(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round($numerator * 100 / $denominator, 1);
    }
}
