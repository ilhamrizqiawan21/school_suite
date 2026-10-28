<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Production environment preflight requires an environment file.\n");
    exit(64);
}

$contents = @file_get_contents($argv[1]);
if ($contents === false) {
    fwrite(STDERR, "Production environment file is missing or unreadable.\n");
    exit(1);
}

$values = [];
foreach (preg_split('/\R/', $contents) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    if (str_starts_with($line, 'export ')) {
        $line = substr($line, 7);
    }

    if (! preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
        continue;
    }

    $value = trim($matches[2]);
    if (strlen($value) >= 2) {
        $first = $value[0];
        $last = $value[strlen($value) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }
    }

    $values[$matches[1]] = $value;
}

$failures = [];
$required = static function (string $key) use (&$values, &$failures): string {
    $value = trim((string) ($values[$key] ?? ''));
    if ($value === '') {
        $failures[] = $key;
    }

    return $value;
};
$boolean = static function (string $value, bool $expected): bool {
    $normalized = strtolower(trim($value));
    $trueValues = ['1', 'true', 'on', 'yes'];
    $falseValues = ['0', 'false', 'off', 'no'];

    if ($expected) {
        return in_array($normalized, $trueValues, true);
    }

    return in_array($normalized, $falseValues, true);
};

if ($required('APP_ENV') !== 'production') {
    $failures[] = 'APP_ENV';
}

if (! $boolean((string) ($values['APP_DEBUG'] ?? ''), false)) {
    $failures[] = 'APP_DEBUG';
}

$appUrl = $required('APP_URL');
$parsedUrl = $appUrl !== '' ? parse_url($appUrl) : false;
if ($parsedUrl === false || strtolower((string) ($parsedUrl['scheme'] ?? '')) !== 'https' || empty($parsedUrl['host'])) {
    $failures[] = 'APP_URL';
}

$required('APP_KEY');

if (! $boolean((string) ($values['SESSION_SECURE_COOKIE'] ?? ''), true)) {
    $failures[] = 'SESSION_SECURE_COOKIE';
}

$backupPassword = $required('BACKUP_PASSWORD');
if (strlen($backupPassword) < 32) {
    $failures[] = 'BACKUP_PASSWORD';
}

$logLevel = strtolower(trim((string) ($values['LOG_LEVEL'] ?? '')));
$allowedLogLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info'];
if (! in_array($logLevel, $allowedLogLevels, true)) {
    $failures[] = 'LOG_LEVEL';
}

$failures = array_values(array_unique($failures));
if ($failures !== []) {
    fwrite(STDERR, 'Unsafe production environment configuration: '.implode(', ', $failures).".\n");
    exit(1);
}

echo "Production environment preflight passed.\n";
