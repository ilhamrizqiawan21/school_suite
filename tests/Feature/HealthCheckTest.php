<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Logging\SanitizeSensitiveDataProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_endpoint_returns_200_ok_when_healthy(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')->delete();
        }
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'app_version',
            'checks' => [
                'database' => ['healthy'],
                'storage' => ['healthy', 'writable'],
                'failed_jobs' => ['healthy'],
            ],
        ]);
        $response->assertJson([
            'status' => 'healthy',
            'checks' => [
                'database' => ['healthy' => true],
                'storage' => ['healthy' => true, 'writable' => true],
            ],
        ]);
        $payload = $response->json();
        $this->assertArrayNotHasKey('environment', $payload);
        $this->assertArrayNotHasKey('path', $payload['checks']['storage']);
        $this->assertArrayNotHasKey('error', $payload['checks']['database']);
        $this->assertArrayNotHasKey('count', $payload['checks']['failed_jobs']);

        // Uji endpoint alternatif /health
        $healthResponse = $this->get('/health');
        $healthResponse->assertOk();
        $healthResponse->assertJson(['status' => 'healthy']);
    }

    public function test_historical_failed_jobs_do_not_make_health_check_unhealthy(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            $this->markTestSkipped('failed_jobs table is unavailable.');
        }

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'testing',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Historical failure retained for review',
            'failed_at' => now(),
        ]);

        $this->get('/up')->assertOk()->assertJson(['status' => 'healthy']);
    }

    public function test_sensitive_data_processor_redacts_credentials_in_logs(): void
    {
        $processor = new SanitizeSensitiveDataProcessor;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'User login attempt',
            context: [
                'username' => 'guru_budi',
                'password' => 'secret12345',
                'api_token' => 'jwt.token.string',
                'student' => [
                    'name' => 'Ahmad',
                    'nisn' => '0011223344',
                    'parent_phone' => '081234567890',
                ],
            ],
            extra: [
                'secret_key' => 'shhh-secret',
            ]
        );

        $sanitized = $processor($record);

        $context = $sanitized->context;
        $this->assertSame('guru_budi', $context['username']);
        $this->assertSame('***REDACTED***', $context['password']);
        $this->assertSame('***REDACTED***', $context['api_token']);
        $this->assertSame('Ahmad', $context['student']['name']);
        $this->assertSame('***REDACTED***', $context['student']['nisn']);
        $this->assertSame('***REDACTED***', $context['student']['parent_phone']);

        $extra = $sanitized->extra;
        $this->assertSame('***REDACTED***', $extra['secret_key']);
    }
}
