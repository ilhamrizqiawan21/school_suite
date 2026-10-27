<?php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use App\Models\User;
use App\Modules\Academic\Application\Services\PrincipalDashboardService;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Assessment\Infrastructure\Models\Exam;
use App\Modules\Assessment\Infrastructure\Models\ExamAttempt;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Core\Infrastructure\Models\LoginAttemptLog;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Shared\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (PrincipalDashboardService $principalDashboard) {
    $user = request()->user();
    $schoolId = (int) $user->school_id;

    $activeTerm = Term::query()
        ->where('is_active', true)
        ->whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
        ->with('academicYear')
        ->first();

    $stats = [
        'modules_ready' => 9,
        'active_period' => $activeTerm?->academicYear?->name
            ? $activeTerm->academicYear->name.' '.$activeTerm->name
            : 'Belum ada periode aktif',
        'framework_version' => app()->version(),
        'role_metrics' => [],
        'latest_announcements' => [],
        'latest_login_logs' => [],
        'attendance_trend' => [],
    ];

    if ($user->hasRole(['admin', 'operator', 'principal'])) {
        $principalOverview = $user->hasRole('principal')
            ? $principalDashboard->overview($schoolId, $activeTerm, request()->query('period') === '30d' ? '30d' : 'term')
            : null;
        $stats['role_metrics'] = [
            'students' => Student::where('school_id', $schoolId)->where('status', 'active')->count(),
            'active_users' => User::where('school_id', $schoolId)->where('is_active', true)->count(),
            'recent_announcements' => Announcement::where('school_id', $schoolId)->where('published_at', '>=', now()->subDays(30))->count(),
            'active_exams' => $principalOverview['summary']['exams_live'] ?? Exam::where('school_id', $schoolId)->whereIn('status', ['published', 'results_published'])->count(),
            'teachers' => Teacher::where('school_id', $schoolId)->where('is_active', true)->count(),
            'active_classes' => ClassGroup::where('school_id', $schoolId)
                ->when($activeTerm, fn ($query) => $query->where('academic_year_id', $activeTerm->academic_year_id))
                ->count(),
            'active_subjects' => Subject::where('school_id', $schoolId)->where('is_active', true)->count(),
        ];
        $stats['latest_announcements'] = Announcement::with('author:id,name')
            ->where('school_id', $schoolId)
            ->whereNotNull('published_at')
            ->when($user->hasRole('principal'), fn ($query) => $query
                ->where('published_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now())))
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'author_id', 'title', 'published_at'])
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'author_name' => $announcement->author?->name,
                'published_at' => $announcement->published_at?->toIso8601String(),
            ])->values()->all();
        if ($user->hasRole('admin')) {
            $stats['latest_login_logs'] = LoginAttemptLog::with('user:id,name')
                ->where(fn ($query) => $query->whereNull('school_id')->orWhere('school_id', $schoolId))
                ->latest('created_at')
                ->limit(10)
                ->get(['id', 'user_id', 'status', 'reason', 'ip_address', 'created_at'])
                ->map(fn (LoginAttemptLog $log) => [
                    'id' => $log->id,
                    'user_name' => $log->user?->name ?? 'Tidak dikenal',
                    'status' => $log->status,
                    'reason' => $log->reason,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])->values()->all();
        }
        if ($principalOverview !== null) {
            $stats['principal_overview'] = $principalOverview;
        }
    } elseif ($user->hasRole(['teacher', 'homeroom'])) {
        $activeTermId = $activeTerm?->id;
        $stats['role_metrics'] = [
            'teaching_assignments' => TeachingAssignment::where('term_id', $activeTermId)->whereHas('teacher', fn ($query) => $query->where('user_id', $user->id))->count(),
            'active_questions' => ExamQuestion::where('school_id', $schoolId)->where('owner_id', $user->id)->where('is_active', true)->count(),
            'upcoming_exams' => Exam::where('school_id', $schoolId)->where('created_by', $user->id)->where('starts_at', '>=', now())->whereIn('status', ['draft', 'published'])->count(),
            'recorded_scores' => Score::where('school_id', $schoolId)->where('recorded_by_user_id', $user->id)->count(),
        ];

        $teacherId = Teacher::where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->value('id');

        if ($user->hasRole('teacher')) {
            $assignments = TeachingAssignment::query()->where('term_id', $activeTermId)->where('teacher_id', $teacherId ?? 0);
            $schoolNow = now('Asia/Jakarta');
            $today = $schoolNow->toDateString();
            $todaySchedules = Schedule::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId ?? 0)
                ->where('term_id', $activeTermId)->where('status', 'published')->where('day_of_week', $schoolNow->dayOfWeekIso)
                ->when(! $activeTerm || $activeTerm->start_date->toDateString() > $today || $activeTerm->end_date->toDateString() < $today, fn ($query) => $query->whereRaw('1 = 0'));
            $stats['role_metrics']['classes_count'] = (clone $assignments)->distinct()->count('class_group_id');
            $stats['teacher_overview'] = [
                'date' => $today,
                'has_teacher_profile' => $teacherId !== null,
                'has_active_term' => $activeTerm !== null,
                'today_total' => (clone $todaySchedules)->count(),
                'today_schedule' => $todaySchedules->with(['classGroup:id,name', 'subject:id,name'])->orderBy('start_time')->limit(5)->get()->map(fn (Schedule $schedule) => [
                    'id' => $schedule->id, 'class_name' => $schedule->classGroup?->name, 'subject_name' => $schedule->subject?->name,
                    'start_time' => substr($schedule->start_time, 0, 5), 'end_time' => substr($schedule->end_time, 0, 5), 'room' => $schedule->room,
                ])->values(),
                'pending_journals' => LessonSession::query()->where('school_id', $schoolId)->where('teacher_id', $teacherId ?? 0)
                    ->where('term_id', $activeTermId)->where('status', '!=', 'cancelled')->whereDate('date', '<=', $today)->whereDoesntHave('journal')->count(),
                'pending_reviews' => AssignmentSubmission::query()->where('review_status', 'pending')->whereNotNull('submitted_at')
                    ->whereHas('assignment', fn ($query) => $query->where('school_id', $schoolId)
                        ->whereHas('teachingAssignment', fn ($query) => $query->where('term_id', $activeTermId)->where('teacher_id', $teacherId ?? 0)))->count(),
            ];
        }

        if ($teacherId && $activeTerm) {
            $monthlyAttendance = DB::table('attendance_records')
                ->join('lesson_sessions', 'lesson_sessions.id', '=', 'attendance_records.lesson_session_id')
                ->where('attendance_records.school_id', $schoolId)
                ->where('lesson_sessions.teacher_id', $teacherId)
                ->where('lesson_sessions.term_id', $activeTerm->id)
                ->whereBetween('attendance_records.date', [
                    $activeTerm->start_date->toDateString(),
                    $activeTerm->end_date->toDateString(),
                ])
                ->select(['attendance_records.date', 'attendance_records.status'])
                ->get()
                ->groupBy(fn ($record) => substr((string) $record->date, 0, 7))
                ->map(fn ($records) => [
                    'total' => $records->count(),
                    'present' => $records->where('status', 'present')->count(),
                ]);

            $month = $activeTerm->start_date->copy()->startOfMonth();
            $lastMonth = $activeTerm->end_date->copy()->startOfMonth();
            $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            while ($month->lte($lastMonth)) {
                $monthKey = $month->format('Y-m');
                $aggregate = $monthlyAttendance->get($monthKey);
                $total = (int) ($aggregate['total'] ?? 0);
                $present = (int) ($aggregate['present'] ?? 0);

                $stats['attendance_trend'][] = [
                    'month_key' => $monthKey,
                    'label' => $monthLabels[(int) $month->format('n') - 1],
                    'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                    'present' => $present,
                    'total' => $total,
                ];

                $month->addMonth();
            }
        }
    } elseif ($user->hasRole('student')) {
        $student = Student::where('school_id', $schoolId)->where('user_id', $user->id)->first();
        $enrollments = $student?->enrollments()->whereHas('term', fn ($query) => $query->where('is_active', true))->get(['id', 'class_group_id']) ?? collect();
        $enrollmentIds = $enrollments->pluck('id');
        $classGroupIds = $enrollments->pluck('class_group_id')->unique()->values();
        $attendance = AttendanceRecord::query()
            ->where('school_id', $schoolId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present")
            ->first();

        $learningAssignments = LearningAssignment::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('published_at')
            ->whereHas('teachingAssignment', fn ($query) => $query
                ->where('term_id', $activeTerm?->id)
                ->whereIn('class_group_id', $classGroupIds))
            ->get(['id']);
        $submittedAssignments = $learningAssignments->isNotEmpty()
            ? AssignmentSubmission::query()
                ->whereIn('learning_assignment_id', $learningAssignments->pluck('id'))
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereIn('status', ['submitted', 'late', 'graded'])
                ->whereNotNull('submitted_at')
                ->distinct('learning_assignment_id')
                ->count('learning_assignment_id')
            : 0;

        $stats['role_metrics'] = [
            'active_enrollments' => $enrollmentIds->count(),
            'upcoming_exams' => ExamAttempt::whereIn('enrollment_id', $enrollmentIds)->whereHas('exam', fn ($query) => $query->where('starts_at', '>=', now())->whereIn('status', ['published', 'results_published']))->count(),
            'completed_exams' => ExamAttempt::whereIn('enrollment_id', $enrollmentIds)->whereIn('status', ['submitted', 'graded'])->count(),
            'recorded_scores' => Score::whereIn('enrollment_id', $enrollmentIds)->whereNotNull('final_score')->count(),
            'attendance_total' => (int) ($attendance?->total ?? 0),
            'attendance_present' => (int) ($attendance?->present ?? 0),
            'assignments_total' => $learningAssignments->count(),
            'assignments_submitted' => $submittedAssignments,
            'assignments_pending' => max(0, $learningAssignments->count() - $submittedAssignments),
        ];

        $stats['latest_announcements'] = Announcement::with('author:id,name')
            ->where('school_id', $schoolId)
            ->whereNotNull('published_at')
            ->where(fn ($query) => $query->whereNull('audience_role')->orWhere('audience_role', 'student'))
            ->where(fn ($query) => $query->whereNull('class_group_id')->orWhereIn('class_group_id', $classGroupIds))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'author_id', 'title', 'published_at'])
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'author_name' => $announcement->author?->name,
                'published_at' => $announcement->published_at?->toIso8601String(),
            ])->values()->all();
    }

    return Inertia::render('Dashboard', [
        'stats' => $stats,
    ]);
})->middleware('auth')->name('dashboard');

// Observability & Health Check Subsystem (SS-F-009)
Route::get('/up', HealthCheckController::class)->name('health.up');
Route::get('/health', HealthCheckController::class)->name('health.index');

// Route Pengunduhan Berkas Privat dengan URL Bertanda Tangan (SS-F-008)
Route::get('/berkas-privat/unduh', [PrivateFileController::class, 'download'])
    ->name('private.file.download');
