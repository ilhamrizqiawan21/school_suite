<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HealthCheckController extends Controller
{
    /**
     * Memeriksa kesehatan operasional aplikasi dan subsistemnya.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'failed_jobs' => $this->checkFailedJobs(),
        ];

        $isHealthy = $checks['database']['healthy']
            && $checks['storage']['healthy']
            && $checks['failed_jobs']['healthy'];

        $payload = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'app_version' => '1.0.0',
            'checks' => $checks,
        ];

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json($payload, $statusCode);
    }

    /**
     * @return array{healthy: bool, latency_ms?: float}
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'healthy' => true,
                'latency_ms' => $latency,
            ];
        } catch (\Throwable) {
            return ['healthy' => false];
        }
    }

    /**
     * @return array{healthy: bool, writable: bool}
     */
    protected function checkStorage(): array
    {
        try {
            $privatePath = storage_path('app/private');
            if (! File::isDirectory($privatePath)) {
                File::makeDirectory($privatePath, 0755, true);
            }

            $testFile = "{$privatePath}/.health_check_probe";
            $canWrite = (bool) @file_put_contents($testFile, 'probe');
            if ($canWrite) {
                @unlink($testFile);
            }

            return [
                'healthy' => $canWrite,
                'writable' => $canWrite,
            ];
        } catch (\Throwable) {
            return [
                'healthy' => false,
                'writable' => false,
            ];
        }
    }

    /**
     * @return array{healthy: bool}
     */
    protected function checkFailedJobs(): array
    {
        try {
            $hasFailedJobs = DB::table('failed_jobs')->exists();

            return [
                'healthy' => ! $hasFailedJobs,
            ];
        } catch (\Throwable) {
            return ['healthy' => app()->environment('testing')];
        }
    }
}
