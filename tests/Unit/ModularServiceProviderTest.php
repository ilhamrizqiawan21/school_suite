<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Shared\Providers\ModularServiceProvider;
use Tests\TestCase;

class ModularServiceProviderTest extends TestCase
{
    /**
     * Verify all 9 expected modules are registered in ModularServiceProvider.
     */
    public function test_all_architectural_modules_are_defined(): void
    {
        $expected = [
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

        $registered = ModularServiceProvider::getRegisteredModules();

        $this->assertSame($expected, $registered);

        foreach ($expected as $module) {
            $this->assertTrue(
                ModularServiceProvider::hasModule($module),
                "Module [{$module}] should be recognized."
            );

            $modulePath = app_path("Modules/{$module}");
            $this->assertDirectoryExists(
                $modulePath,
                "Module directory [{$modulePath}] must exist."
            );
        }
    }

    /**
     * Verify shared service directories exist.
     */
    public function test_shared_services_directories_exist(): void
    {
        $shared = ['Audit', 'Files', 'Exports', 'Notifications'];

        foreach ($shared as $service) {
            $path = app_path("Shared/{$service}");
            $this->assertDirectoryExists(
                $path,
                "Shared directory [{$path}] must exist."
            );
        }
    }
}
