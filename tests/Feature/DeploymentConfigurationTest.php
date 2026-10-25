<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeploymentConfigurationTest extends TestCase
{
    public function test_security_headers_are_present_in_responses(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_deployment_artifacts_exist_and_are_executable(): void
    {
        $nginxConf = base_path('deploy/nginx/school-suite.conf');
        $this->assertTrue(File::exists($nginxConf));
        $this->assertStringContainsString('fastcgi_pass', File::get($nginxConf));
        $this->assertStringContainsString('ssl_protocols', File::get($nginxConf));

        $opcacheIni = base_path('deploy/php/opcache.ini');
        $this->assertTrue(File::exists($opcacheIni));
        $this->assertStringContainsString('opcache.enable = 1', File::get($opcacheIni));

        $phpFpmConf = base_path('deploy/php/www.conf');
        $this->assertTrue(File::exists($phpFpmConf));
        $this->assertStringContainsString('pm = dynamic', File::get($phpFpmConf));

        $systemdService = base_path('deploy/systemd/school-suite-worker.service');
        $this->assertTrue(File::exists($systemdService));
        $this->assertStringContainsString('queue:work', File::get($systemdService));

        $deployScript = base_path('deploy/deploy.sh');
        $this->assertTrue(File::exists($deployScript));
        $this->assertTrue(is_executable($deployScript));
        $this->assertStringContainsString('composer install', File::get($deployScript));
        $this->assertStringContainsString('artisan migrate', File::get($deployScript));

        $rollbackScript = base_path('deploy/rollback.sh');
        $this->assertTrue(File::exists($rollbackScript));
        $this->assertTrue(is_executable($rollbackScript));
        $this->assertStringContainsString('ln -sfn', File::get($rollbackScript));

        $checklist = base_path('docs/DEPLOYMENT_CHECKLIST.md');
        $this->assertTrue(File::exists($checklist));
    }

    public function test_scheduled_backup_and_worker_restart_are_configured(): void
    {
        /** @var Schedule $schedule */
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $backupEvent = $events->first(function ($event) {
            return str_contains((string) $event->command, 'school:backup');
        });

        $this->assertNotNull($backupEvent, 'Jadwal school:backup harus terdaftar di scheduler.');
        $this->assertSame('0 2 * * *', $backupEvent->expression);

        $restartEvent = $events->first(function ($event) {
            return str_contains((string) $event->command, 'queue:restart');
        });

        $this->assertNotNull($restartEvent, 'Jadwal queue:restart harus terdaftar di scheduler.');
        $this->assertSame('0 4 * * *', $restartEvent->expression);
    }
}

