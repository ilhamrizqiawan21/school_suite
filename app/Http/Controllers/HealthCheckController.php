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

        $isHealthy = $checks['database']['healthy'] && $checks['storage']['healthy'];

        $payload = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'app_version' => '1.0.0',
            'environment' => config('app.env'),
            'checks' => $checks,
        ];

        $statusCode = $isHealthy ? 200 : 503;

        return response()->json($payload, $statusCode);
    }

    /**
     * @return array{healthy: bool, latency_ms?: float, error?: string}
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
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{healthy: bool, writable: bool, path: string}
     */
    protected function checkStorage(): array
    {
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
            'path' => $privatePath,
        ];
    }

    /**
     * @return array{healthy: bool, count: int}
     */
    protected function checkFailedJobs(): array
    {
        try {
            $count = DB::table('failed_jobs')->count();

            return [
                'healthy' => $count === 0,
                'count' => $count,
            ];
        } catch (\Throwable) {
            return [
                'healthy' => true,
                'count' => 0,
            ];
        }
    }
}
