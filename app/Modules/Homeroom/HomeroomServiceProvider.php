<?php

declare(strict_types=1);

namespace App\Modules\Homeroom;

use App\Modules\Homeroom\Application\Policies\StudentNotePolicy;
use App\Modules\Homeroom\Infrastructure\Models\StudentNote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class HomeroomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(StudentNote::class, StudentNotePolicy::class);
    }
}
