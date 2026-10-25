<?php

declare(strict_types=1);

namespace App\Modules\Learning;

use App\Modules\Learning\Application\Policies\LearningPolicy;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class LearningServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(LearningMaterial::class, LearningPolicy::class);
        Gate::policy(LearningAssignment::class, LearningPolicy::class);
        Gate::policy(AssignmentSubmission::class, LearningPolicy::class);
    }
}
