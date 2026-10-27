<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeploymentHealthProbeTest extends TestCase
{
    #[DataProvider('probeResponses')]
    public function test_tls_probe_only_accepts_healthy_application_json(string $status, string $body, int $curlExit, bool $healthy): void
    {
        $directory = sys_get_temp_dir().'/school-probe-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory, 0700);
        File::put($directory.'/curl', <<<'SH'
#!/usr/bin/env bash
printf '%s\n' "$@" > "$PROBE_ARGUMENTS"
while [ "$#" -gt 0 ]; do
    if [ "$1" = '--output' ]; then
        printf '%s' "$PROBE_BODY" > "$2"
        shift
    fi
    shift
done
printf '%s' "$PROBE_STATUS"
exit "$PROBE_EXIT"
SH);
        chmod($directory.'/curl', 0700);
        try {
            $process = new Process(['bash', base_path('deploy/health-check.sh'), 'https://school.test/up'], null, [
                'PATH' => $directory.':'.getenv('PATH'),
                'PROBE_ARGUMENTS' => $directory.'/arguments',
                'PROBE_BODY' => $body,
                'PROBE_STATUS' => $status,
                'PROBE_EXIT' => (string) $curlExit,
            ]);
            $process->run();
            $this->assertSame($healthy, $process->isSuccessful(), $process->getErrorOutput());
            $arguments = File::get($directory.'/arguments');
            $this->assertStringContainsString('school.test:443:127.0.0.1', $arguments);
            $this->assertStringContainsString('https://school.test/up', $arguments);
            $this->assertStringContainsString('--max-time', $arguments);
            $this->assertStringNotContainsString('--insecure', $arguments);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public static function probeResponses(): array
    {
        return [
            'healthy' => ['200', '{"status":"healthy"}', 0, true],
            'unhealthy' => ['200', '{"status":"unhealthy"}', 0, false],
            'wrong vhost' => ['200', '<html>Welcome to nginx</html>', 0, false],
            'redirect' => ['302', '', 0, false],
            'http redirect' => ['301', '', 0, false],
            'unavailable' => ['503', '{"status":"unhealthy"}', 22, false],
            'timeout' => ['000', '', 28, false],
        ];
    }
}
