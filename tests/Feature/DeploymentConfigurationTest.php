<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class DeploymentConfigurationTest extends TestCase
{
    public function test_security_headers_are_present_in_responses(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')->delete();
        }

        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_local_vite_dev_server_is_allowed_by_content_security_policy(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'school-suite-vite-hot-');
        File::put($hotFile, 'http://127.0.0.1:5173');
        Vite::useHotFile($hotFile);
        config(['app.env' => 'local']);

        try {
            $response = $this->get('/up');

            $response->assertHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' http://127.0.0.1:5173; style-src 'self' 'unsafe-inline' http://127.0.0.1:5173; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' http://127.0.0.1:5173 ws://127.0.0.1:5173; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
            );
        } finally {
            Vite::useHotFile(public_path('/hot'));
            File::delete($hotFile);
        }
    }

    public function test_production_content_security_policy_does_not_allow_vite_dev_server(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'school-suite-vite-hot-');
        File::put($hotFile, 'http://127.0.0.1:5173');
        Vite::useHotFile($hotFile);
        config(['app.env' => 'production']);

        try {
            $response = $this->get('/up');
            $csp = (string) $response->headers->get('Content-Security-Policy');
            $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
            $this->assertStringContainsString("style-src 'self' 'nonce-", $csp);
            $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
            $this->assertStringNotContainsString("style-src 'self' 'unsafe-inline'", $csp);
            $this->assertStringContainsString("style-src-attr 'unsafe-inline'", $csp);
            $this->assertStringNotContainsString('http://127.0.0.1:5173', $csp);
        } finally {
            Vite::useHotFile(public_path('/hot'));
            File::delete($hotFile);
        }
    }

    public function test_staging_content_security_policy_does_not_allow_vite_dev_server(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'school-suite-vite-hot-');
        File::put($hotFile, 'http://127.0.0.1:5173');
        Vite::useHotFile($hotFile);
        config(['app.env' => 'staging']);

        try {
            $response = $this->get('/up');
            $csp = (string) $response->headers->get('Content-Security-Policy');
            $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
            $this->assertStringContainsString("style-src 'self' 'nonce-", $csp);
            $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
            $this->assertStringNotContainsString("style-src 'self' 'unsafe-inline'", $csp);
            $this->assertStringContainsString("style-src-attr 'unsafe-inline'", $csp);
            $this->assertStringNotContainsString('http://127.0.0.1:5173', $csp);
        } finally {
            Vite::useHotFile(public_path('/hot'));
            File::delete($hotFile);
        }
    }

    public function test_malformed_vite_hot_url_does_not_relax_content_security_policy(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'school-suite-vite-hot-');
        File::put($hotFile, 'not a valid URL');
        Vite::useHotFile($hotFile);
        config(['app.env' => 'local']);

        try {
            $response = $this->get('/up');

            $response->assertHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
            );
        } finally {
            Vite::useHotFile(public_path('/hot'));
            File::delete($hotFile);
        }
    }

    public function test_deployment_artifacts_exist_and_are_executable(): void
    {
        $nginxConf = base_path('deploy/nginx/school-suite.conf');
        $this->assertTrue(File::exists($nginxConf));
        $nginxContents = File::get($nginxConf);
        $this->assertStringContainsString('fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;', $nginxContents);
        $this->assertStringContainsString('ssl_protocols', $nginxContents);
        $this->assertStringContainsString('server_name school.sch.id www.school.sch.id;', $nginxContents);
        $this->assertLessThan(
            strpos($nginxContents, 'location ~ \\.php$ {'),
            strpos($nginxContents, 'location ~* /(?:storage|uploads)/.*\\.php$ {'),
            'Upload PHP deny rule must precede the generic PHP handler.'
        );

        $opcacheIni = base_path('deploy/php/opcache.ini');
        $this->assertTrue(File::exists($opcacheIni));
        $this->assertStringContainsString('opcache.enable = 1', File::get($opcacheIni));

        $phpFpmConf = base_path('deploy/php/www.conf');
        $this->assertTrue(File::exists($phpFpmConf));
        $this->assertStringContainsString('pm = dynamic', File::get($phpFpmConf));
        $this->assertStringContainsString('listen = /var/run/php/php8.3-fpm.sock', File::get($phpFpmConf));

        $systemdService = base_path('deploy/systemd/school-suite-worker.service');
        $this->assertTrue(File::exists($systemdService));
        $systemdContents = File::get($systemdService);
        $this->assertStringContainsString('queue:work', $systemdContents);
        $this->assertStringContainsString('ProtectSystem=strict', $systemdContents);
        $this->assertStringContainsString('ReadWritePaths=/var/www/school_suite/shared/storage', $systemdContents);
        $this->assertStringContainsString('StandardOutput=journal', $systemdContents);

        $schedulerService = base_path('deploy/systemd/school-suite-scheduler.service');
        $this->assertTrue(File::exists($schedulerService));
        $this->assertStringContainsString('schedule:run', File::get($schedulerService));

        $schedulerTimer = base_path('deploy/systemd/school-suite-scheduler.timer');
        $this->assertTrue(File::exists($schedulerTimer));
        $this->assertStringContainsString('OnCalendar=*-*-* *:*:00', File::get($schedulerTimer));

        $this->assertFalse((bool) config('filesystems.disks.local.serve'));
        $this->assertGreaterThan(120, (int) config('queue.connections.database.retry_after'));

        $deployScript = base_path('deploy/deploy.sh');
        $this->assertTrue(File::exists($deployScript));
        $this->assertTrue(is_executable($deployScript));
        $deployContents = File::get($deployScript);
        $this->assertStringContainsString('mkdir -p "${RELEASE_DIR}"', $deployContents);
        $this->assertStringContainsString('git archive "${BRANCH}"', $deployContents);
        $this->assertStringContainsString('composer install', $deployContents);
        $this->assertStringContainsString('artisan migrate', $deployContents);
        $this->assertStringContainsString('HEALTH_CHECK_URL wajib diisi', $deployContents);
        $this->assertStringContainsString('validate-production-env.php', $deployContents);
        $this->assertStringContainsString('DEPLOY_SCRIPT_DIR', $deployContents);
        $this->assertStringContainsString('SCHEDULER_TIMER_SERVICE', $deployContents);
        $this->assertStringContainsString('systemctl enable --now', $deployContents);
        $this->assertStringContainsString('sudo systemctl stop school-suite-worker', $deployContents);
        $this->assertStringContainsString('rm -f "${CURRENT_SYMLINK}"', $deployContents);

        $environmentPreflight = base_path('deploy/validate-production-env.php');
        $this->assertTrue(File::exists($environmentPreflight));
        $this->assertStringContainsString('BACKUP_PASSWORD', File::get($environmentPreflight));

        $environmentExample = File::get(base_path('.env.example'));
        $this->assertStringContainsString('APP_ENV=production', $environmentExample);
        $this->assertStringContainsString('APP_DEBUG=false', $environmentExample);
        $this->assertStringContainsString('APP_URL=https://example.com', $environmentExample);
        $this->assertStringContainsString('LOG_LEVEL=info', $environmentExample);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $environmentExample);

        $healthCheckContents = File::get(base_path('deploy/health-check.sh'));
        $this->assertStringContainsString('--connect-timeout 5 --max-time 15', $healthCheckContents);
        $this->assertStringNotContainsString('school.sch.id/up', $healthCheckContents);

        $rollbackScript = base_path('deploy/rollback.sh');
        $this->assertTrue(File::exists($rollbackScript));
        $this->assertTrue(is_executable($rollbackScript));
        $rollbackContents = File::get($rollbackScript);
        $this->assertStringContainsString('ln -sfn', $rollbackContents);
        $this->assertStringContainsString('health-check.sh', $rollbackContents);
        $this->assertStringContainsString('HEALTH_CHECK_URL wajib diisi', $rollbackContents);

        $checklist = base_path('docs/DEPLOYMENT_CHECKLIST.md');
        $this->assertTrue(File::exists($checklist));
        $checklistContents = File::get($checklist);
        $this->assertStringContainsString('school-suite-scheduler.timer', $checklistContents);
        $this->assertStringContainsString('verifikasi health check', $checklistContents);
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
