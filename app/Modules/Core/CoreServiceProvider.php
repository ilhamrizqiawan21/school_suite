<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Core\Application\Policies\SchoolPolicy;
use App\Modules\Core\Application\Policies\UserPolicy;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register Core module bindings.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap Core module policies and bindings.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
    }
}
