<?php

declare(strict_types=1);

namespace App\Modules\Academic;

use App\Modules\Academic\Application\Policies\AttendancePolicy;
use App\Modules\Academic\Application\Policies\ClassGroupPolicy;
use App\Modules\Academic\Application\Policies\SchedulePolicy;
use App\Modules\Academic\Application\Policies\StudentPolicy;
use App\Modules\Academic\Application\Policies\TeacherPolicy;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AcademicServiceProvider extends ServiceProvider
{
    /**
     * Register Academic module bindings.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap Academic module policies.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(ClassGroup::class, ClassGroupPolicy::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(LessonSession::class, AttendancePolicy::class);
        Gate::policy(AttendanceRecord::class, AttendancePolicy::class);
    }
}
