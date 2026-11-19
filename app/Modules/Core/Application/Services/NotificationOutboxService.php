<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Infrastructure\Models\NotificationOutbox;
use App\Shared\Audit\AuditLogger;
use App\Shared\Jobs\DispatchNotificationOutboxJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Idempotent notification outbox with sensitive data redaction and safe retries (M4-FND-07, M4-FND-08).
 */
class NotificationOutboxService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Queue a notification idempotently. Sensitive payload fields are redacted before storage.
     *
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(
        int $schoolId,
        string $eventKey,
        string $channel,
        string $recipientType,
        ?int $recipientId,
        string $templateKey,
        array $payload,
        string $idempotencyKey,
        bool $dispatchJob = true,
    ): NotificationOutbox {
        $sanitizedPayload = $this->auditLogger->sanitize($payload);

        [$outbox, $created] = DB::transaction(function () use (
            $schoolId,
            $eventKey,
            $channel,
            $recipientType,
            $recipientId,
            $templateKey,
            $sanitizedPayload,
            $idempotencyKey,
        ): array {
            $existing = NotificationOutbox::query()
                ->where('school_id', $schoolId)
                ->where('channel', $channel)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return [$existing, false];
            }

            $createdOutbox = NotificationOutbox::query()->create([
                'school_id' => $schoolId,
                'event_key' => $eventKey,
                'channel' => $channel,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'template_key' => $templateKey,
                'payload' => $sanitizedPayload,
                'idempotency_key' => $idempotencyKey,
                'status' => 'queued',
                'attempts' => 0,
                'max_attempts' => 3,
            ]);

            return [$createdOutbox, true];
        });

        if ($dispatchJob && $created && $outbox->status === 'queued') {
            DispatchNotificationOutboxJob::dispatch($outbox->id);
        }

        return $outbox;
    }

    /**
     * Execute delivery for an outbox entry. Idempotent if already sent; records sanitized failure on error.
     *
     * @param  (callable(NotificationOutbox): void)|null  $transport
     */
    public function deliver(int $outboxId, ?callable $transport = null): NotificationOutbox
    {
        return DB::transaction(function () use ($outboxId, $transport): NotificationOutbox {
            /** @var NotificationOutbox $outbox */
            $outbox = NotificationOutbox::query()
                ->whereKey($outboxId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($outbox->status === 'sent') {
                return $outbox;
            }

            $attempts = $outbox->attempts + 1;

            try {
                if ($transport !== null) {
                    $transport($outbox);
                }

                $outbox->forceFill([
                    'status' => 'sent',
                    'attempts' => $attempts,
                    'last_error' => null,
                    'sent_at' => now(),
                    'next_retry_at' => null,
                ])->save();
            } catch (Throwable $exception) {
                $status = $attempts >= $outbox->max_attempts ? 'dead_letter' : 'failed';
                $safeMessage = substr($exception->getMessage(), 0, 240);

                $outbox->forceFill([
                    'status' => $status,
                    'attempts' => $attempts,
                    'last_error' => $safeMessage,
                    'next_retry_at' => $status === 'failed' ? now()->addMinutes($attempts * 5) : null,
                ])->save();

                Log::warning('Notification delivery failed', [
                    'outbox_id' => $outbox->id,
                    'school_id' => $outbox->school_id,
                    'channel' => $outbox->channel,
                    'status' => $status,
                    'attempts' => $attempts,
                ]);
            }

            return $outbox->refresh();
        });
    }

    /**
     * Re-queue a failed/dead-letter notification safely within the same school without creating duplicates.
     */
    public function retry(int $outboxId, int $schoolId, bool $dispatchJob = true): NotificationOutbox
    {
        $outbox = DB::transaction(function () use ($outboxId, $schoolId): NotificationOutbox {
            /** @var NotificationOutbox $record */
            $record = NotificationOutbox::query()
                ->where('school_id', $schoolId)
                ->whereKey($outboxId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->status === 'sent') {
                return $record;
            }

            $record->forceFill([
                'status' => 'queued',
                'max_attempts' => max($record->max_attempts, $record->attempts + 1),
                'next_retry_at' => null,
            ])->save();

            $this->auditLogger->log(
                eventType: 'notification.retried',
                targetType: 'NotificationOutbox',
                targetId: (string) $record->id,
                newValues: ['channel' => $record->channel, 'attempts' => $record->attempts],
                schoolId: $schoolId,
            );

            return $record->refresh();
        });

        if ($dispatchJob && $outbox->status === 'queued') {
            DispatchNotificationOutboxJob::dispatch($outbox->id);
        }

        return $outbox;
    }
}
