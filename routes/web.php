<?php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
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
use App\Shared\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
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
        $stats['role_metrics'] = [
            'students' => Student::where('school_id', $schoolId)->where('status', 'active')->count(),
            'active_users' => User::where('school_id', $schoolId)->where('is_active', true)->count(),
            'recent_announcements' => Announcement::where('school_id', $schoolId)->where('published_at', '>=', now()->subDays(30))->count(),
            'active_exams' => Exam::where('school_id', $schoolId)->whereIn('status', ['published', 'results_published'])->count(),
            'teachers' => Teacher::where('school_id', $schoolId)->where('is_active', true)->count(),
            'active_classes' => ClassGroup::where('school_id', $schoolId)
                ->when($activeTerm, fn ($query) => $query->where('academic_year_id', $activeTerm->academic_year_id))
                ->count(),
            'active_subjects' => Subject::where('school_id', $schoolId)->where('is_active', true)->count(),
        ];
        $stats['latest_announcements'] = Announcement::with('author:id,name')
            ->where('school_id', $schoolId)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'author_id', 'title', 'published_at'])
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'author_name' => $announcement->author?->name,
                'published_at' => $announcement->published_at?->toIso8601String(),
            ])->values()->all();
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
        $enrollmentIds = $student?->enrollments()->whereHas('term', fn ($query) => $query->where('is_active', true))->pluck('id') ?? collect();
        $stats['role_metrics'] = [
            'active_enrollments' => $enrollmentIds->count(),
            'upcoming_exams' => ExamAttempt::whereIn('enrollment_id', $enrollmentIds)->whereHas('exam', fn ($query) => $query->where('starts_at', '>=', now())->whereIn('status', ['published', 'results_published']))->count(),
            'completed_exams' => ExamAttempt::whereIn('enrollment_id', $enrollmentIds)->whereIn('status', ['submitted', 'graded'])->count(),
            'recorded_scores' => Score::whereIn('enrollment_id', $enrollmentIds)->whereNotNull('final_score')->count(),
        ];
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
