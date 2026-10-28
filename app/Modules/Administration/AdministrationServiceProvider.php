<?php

declare(strict_types=1);

namespace App\Modules\Administration;

use App\Modules\Administration\Application\Contracts\ElectronicSignatureProvider;
use App\Modules\Administration\Application\Policies\StaffTaskPolicy;
use App\Modules\Administration\Infrastructure\Models\StaffTask;
use App\Modules\Administration\Infrastructure\Signatures\BsreElectronicSignatureProvider;
use App\Modules\Administration\Infrastructure\Signatures\UnavailableElectronicSignatureProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AdministrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ElectronicSignatureProvider::class, function ($app): ElectronicSignatureProvider {
            return config('correspondence.bsre.enabled', false)
                ? $app->make(BsreElectronicSignatureProvider::class)
                : $app->make(UnavailableElectronicSignatureProvider::class);
        });
    }

    public function boot(): void
    {
        Gate::policy(StaffTask::class, StaffTaskPolicy::class);
    }
}
