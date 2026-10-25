<?php

namespace App\Providers;

use App\Logging\SanitizeSensitiveDataProcessor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('sensitive', fn (Request $request) => [
            Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('uploads', fn (Request $request) => [
            Limit::perMinute(20)->by((string) ($request->user()?->id ?? $request->ip())),
        ]);

        // Pasang sanitasi log otomatis untuk melindungi parameter sensitif (AI_RULES Aturan 4 & 9)
        if (method_exists(Log::getFacadeRoot(), 'getLogger')) {
            Log::getLogger()->pushProcessor(new SanitizeSensitiveDataProcessor);
        }
    }
}
