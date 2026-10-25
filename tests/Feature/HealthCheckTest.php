<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Logging\SanitizeSensitiveDataProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_endpoint_returns_200_ok_when_healthy(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'app_version',
            'environment',
            'checks' => [
                'database' => ['healthy'],
                'storage' => ['healthy', 'writable'],
                'failed_jobs' => ['healthy', 'count'],
            ],
        ]);
        $response->assertJson([
            'status' => 'healthy',
            'checks' => [
                'database' => ['healthy' => true],
                'storage' => ['healthy' => true, 'writable' => true],
            ],
        ]);

        // Uji endpoint alternatif /health
        $healthResponse = $this->get('/health');
        $healthResponse->assertOk();
        $healthResponse->assertJson(['status' => 'healthy']);
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
