<?php

declare(strict_types=1);

namespace App\Modules\Administration;

use App\Modules\Administration\Application\Policies\StaffTaskPolicy;
use App\Modules\Administration\Infrastructure\Models\StaffTask;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AdministrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(StaffTask::class, StaffTaskPolicy::class);
    }
}
