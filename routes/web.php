<?php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Assessment\Infrastructure\Models\Exam;
use App\Modules\Assessment\Infrastructure\Models\ExamAttempt;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Shared\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = request()->user();
    $schoolId = (int) $user->school_id;

    $stats = [
        'modules_ready' => 9,
        'active_period' => '2026/2027 Ganjil',
        'framework_version' => app()->version(),
        'role_metrics' => [],
    ];

    if ($user->hasRole(['admin', 'operator', 'principal'])) {
        $stats['role_metrics'] = [
            'students' => Student::where('school_id', $schoolId)->where('status', 'active')->count(),
            'active_users' => User::where('school_id', $schoolId)->where('is_active', true)->count(),
            'recent_announcements' => Announcement::where('school_id', $schoolId)->where('published_at', '>=', now()->subDays(30))->count(),
            'active_exams' => Exam::where('school_id', $schoolId)->whereIn('status', ['published', 'results_published'])->count(),
            'teachers' => Teacher::where('school_id', $schoolId)->where('is_active', true)->count(),
        ];
    } elseif ($user->hasRole(['teacher', 'homeroom'])) {
        $activeTermId = Term::where('is_active', true)->value('id');
        $stats['role_metrics'] = [
            'teaching_assignments' => TeachingAssignment::where('term_id', $activeTermId)->whereHas('teacher', fn ($query) => $query->where('user_id', $user->id))->count(),
            'active_questions' => ExamQuestion::where('school_id', $schoolId)->where('owner_id', $user->id)->where('is_active', true)->count(),
            'upcoming_exams' => Exam::where('school_id', $schoolId)->where('created_by', $user->id)->where('starts_at', '>=', now())->whereIn('status', ['draft', 'published'])->count(),
            'recorded_scores' => Score::where('school_id', $schoolId)->where('recorded_by_user_id', $user->id)->count(),
        ];
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
