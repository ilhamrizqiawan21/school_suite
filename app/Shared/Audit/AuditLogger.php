<?php

declare(strict_types=1);

namespace App\Shared\Audit;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * List of sensitive keys that must be redacted automatically.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'pin',
        'credit_card',
    ];

    /**
     * Record a new material audit event with automatic redaction of sensitive data.
     *
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $oldValues
     */
    public function log(
        string $eventType,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $newValues = null,
        ?array $oldValues = null,
        ?User $actor = null,
        ?int $schoolId = null
    ): AuditEvent {
        $actorId = $actor?->id ?? auth()->id();
        $resolvedSchoolId = $schoolId ?? $actor?->school_id ?? auth()->user()?->school_id;

        $ipAddress = Request::ip();
        $userAgent = Request::userAgent();

        return AuditEvent::create([
            'school_id' => $resolvedSchoolId,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_values' => $oldValues !== null ? $this->sanitize($oldValues) : null,
            'new_values' => $newValues !== null ? $this->sanitize($newValues) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * Recursively sanitize sensitive keys in payloads.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Determine whether the key is considered sensitive.
     */
    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($key === $sensitive || str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
