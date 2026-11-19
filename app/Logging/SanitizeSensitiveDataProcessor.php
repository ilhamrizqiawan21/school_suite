<?php

declare(strict_types=1);

namespace App\Logging;

use App\Modules\Core\Domain\DataClassificationMap;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class SanitizeSensitiveDataProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (! empty($context)) {
            $sanitizedContext = $this->sanitizeArray($context);
            $record = $record->with(context: $sanitizedContext);
        }

        $extra = $record->extra;
        if (! empty($extra)) {
            $sanitizedExtra = $this->sanitizeArray($extra);
            $record = $record->with(extra: $sanitizedExtra);
        }

        return $record;
    }

    /**
     * Rekursif menyamarkan nilai pada array jika kunci termasuk parameter sensitif.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $val) {
            $lowerKey = strtolower((string) $key);

            if ($this->isSensitiveKey($lowerKey)) {
                $data[$key] = '***REDACTED***';
            } elseif (is_array($val)) {
                $data[$key] = $this->sanitizeArray($val);
            }
        }

        return $data;
    }

    protected function isSensitiveKey(string $key): bool
    {
        foreach (DataClassificationMap::sensitiveKeys() as $sensitive) {
            if (str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
