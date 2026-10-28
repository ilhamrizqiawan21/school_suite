<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeploymentEnvironmentPreflightTest extends TestCase
{
    public function test_safe_production_environment_passes_without_printing_values(): void
    {
        $secret = 'backup-secret-that-must-never-be-printed-1234567890';
        $directory = sys_get_temp_dir().'/school-env-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory, 0700);
        $environmentFile = $directory.'/.env';
        File::put($environmentFile, $this->environment([
            'BACKUP_PASSWORD' => $secret,
        ]));

        try {
            $process = $this->runPreflight($environmentFile);

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $this->assertStringNotContainsString($secret, $process->getOutput().$process->getErrorOutput());
        } finally {
            File::deleteDirectory($directory);
        }
    }

    #[DataProvider('unsafeProductionEnvironments')]
    public function test_unsafe_production_environment_is_rejected_without_printing_values(string $key, string $value): void
    {
        $secret = 'backup-secret-that-must-never-be-printed-1234567890';
        $directory = sys_get_temp_dir().'/school-env-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory, 0700);
        $environmentFile = $directory.'/.env';
        File::put($environmentFile, $this->environment([
            'BACKUP_PASSWORD' => $secret,
            $key => $value,
        ]));

        try {
            $process = $this->runPreflight($environmentFile);
            $output = $process->getOutput().$process->getErrorOutput();

            $this->assertFalse($process->isSuccessful(), $output);
            $this->assertStringContainsString($key, $output);
            $this->assertStringNotContainsString($secret, $output);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public static function unsafeProductionEnvironments(): array
    {
        return [
            'non-production environment' => ['APP_ENV', 'staging'],
            'debug enabled' => ['APP_DEBUG', 'true'],
            'non-HTTPS application URL' => ['APP_URL', 'http://school.test'],
            'missing application key' => ['APP_KEY', ''],
            'insecure session cookie' => ['SESSION_SECURE_COOKIE', 'false'],
            'weak backup password' => ['BACKUP_PASSWORD', 'too-short'],
            'debug log level' => ['LOG_LEVEL', 'debug'],
        ];
    }

    /** @param array<string, string> $overrides */
    private function environment(array $overrides = []): string
    {
        $values = array_merge([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://school.test',
            'APP_KEY' => 'base64:application-key-placeholder',
            'SESSION_SECURE_COOKIE' => 'true',
            'BACKUP_PASSWORD' => 'backup-secret-that-must-never-be-printed-1234567890',
            'LOG_LEVEL' => 'info',
        ], $overrides);

        return collect($values)
            ->map(fn (string $value, string $key): string => $key.'='.$value)
            ->implode("\n")."\n";
    }

    private function runPreflight(string $environmentFile): Process
    {
        $process = new Process([
            'php',
            base_path('deploy/validate-production-env.php'),
            $environmentFile,
        ]);
        $process->run();

        return $process;
    }
}
