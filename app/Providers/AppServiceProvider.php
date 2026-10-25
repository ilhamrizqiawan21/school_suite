<?php

namespace App\Providers;

use App\Logging\SanitizeSensitiveDataProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pasang sanitasi log otomatis untuk melindungi parameter sensitif (AI_RULES Aturan 4 & 9)
        if (method_exists(Log::getFacadeRoot(), 'getLogger')) {
            Log::getLogger()->pushProcessor(new SanitizeSensitiveDataProcessor);
        }
    }
}
