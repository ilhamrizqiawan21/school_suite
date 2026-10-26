<?php

declare(strict_types=1);

namespace App\Modules\Admissions;

use App\Modules\Admissions\Application\Policies\AdmissionsPolicy;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AdmissionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(AdmissionApplicant::class, AdmissionsPolicy::class);
    }
}
