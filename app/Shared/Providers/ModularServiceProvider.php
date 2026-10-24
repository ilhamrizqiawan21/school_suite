<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModularServiceProvider extends ServiceProvider
{
    /**
     * List of recognized modular monolith modules.
     *
     * @var list<string>
     */
    public const MODULES = [
        'Core',
        'Academic',
        'Homeroom',
        'Learning',
        'Assessment',
        'Admissions',
        'Library',
        'Administration',
        'Integration',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register per-module custom service providers if they exist
        foreach (self::MODULES as $module) {
            $providerClass = "App\\Modules\\{$module}\\{$module}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        foreach (self::MODULES as $module) {
            $moduleDir = $modulesPath.DIRECTORY_SEPARATOR.$module;

            if (! is_dir($moduleDir)) {
                continue;
            }

            // 1. Auto-discover migrations
            $migrationsDir = $moduleDir.DIRECTORY_SEPARATOR.'Infrastructure'.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'Migrations';
            if (is_dir($migrationsDir)) {
                $this->loadMigrationsFrom($migrationsDir);
            }

            // 2. Auto-discover Web routes
            $webRoutes = $moduleDir.DIRECTORY_SEPARATOR.'Interface'.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'web.php';
            if (file_exists($webRoutes)) {
                Route::middleware('web')->group($webRoutes);
            }

            // 3. Auto-discover API routes
            $apiRoutes = $moduleDir.DIRECTORY_SEPARATOR.'Interface'.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php';
            if (file_exists($apiRoutes)) {
                Route::prefix('api')->middleware('api')->group($apiRoutes);
            }
        }
    }

    /**
     * Get the list of all registered modules.
     *
     * @return list<string>
     */
    public static function getRegisteredModules(): array
    {
        return self::MODULES;
    }

    /**
     * Check if a module name is valid in this modular monolith.
     */
    public static function hasModule(string $module): bool
    {
        return in_array($module, self::MODULES, true);
    }
}
