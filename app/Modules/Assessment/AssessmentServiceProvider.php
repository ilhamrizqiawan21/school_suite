<?php

declare(strict_types=1);

namespace App\Modules\Assessment;

use App\Modules\Assessment\Application\Policies\AssessmentPolicy;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AssessmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/Database/Migrations');

        Gate::policy(AssessmentScheme::class, AssessmentPolicy::class);
        Gate::policy(ReportSnapshot::class, AssessmentPolicy::class);
        Gate::policy(Score::class, AssessmentPolicy::class);
    }
}
