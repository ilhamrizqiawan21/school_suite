<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

use InvalidArgumentException;

final class ServiceWorkflowMap
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const PUBLIC_STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_IN_REVIEW,
        self::STATUS_NEEDS_REVISION,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    /** @var array<string, list<string>> */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_SUBMITTED => [
            self::STATUS_IN_REVIEW,
            self::STATUS_NEEDS_REVISION,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ],
        self::STATUS_IN_REVIEW => [
            self::STATUS_NEEDS_REVISION,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ],
        self::STATUS_NEEDS_REVISION => [
            self::STATUS_SUBMITTED,
            self::STATUS_IN_REVIEW,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ],
        self::STATUS_APPROVED => [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ],
        self::STATUS_REJECTED => [
            self::STATUS_IN_REVIEW, // Controlled correction/re-evaluation by authorized officer
        ],
        self::STATUS_COMPLETED => [
            self::STATUS_REJECTED, // Controlled correction by authorized officer/homeroom
            self::STATUS_CANCELLED, // Authorized revocation of an issued result document
        ],
        self::STATUS_CANCELLED => [],
        self::STATUS_EXPIRED => [],
    ];

    /** @var list<string> */
    private const REASON_REQUIRED_TARGETS = [
        self::STATUS_NEEDS_REVISION,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        if (! in_array($toStatus, self::PUBLIC_STATUSES, true)) {
            return false;
        }

        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    public static function assertCanTransition(string $fromStatus, string $toStatus, ?string $reason): void
    {
        if (! self::canTransition($fromStatus, $toStatus)) {
            throw new InvalidArgumentException(
                sprintf('Transisi status dari [%s] ke [%s] tidak diizinkan.', $fromStatus, $toStatus)
            );
        }

        if (self::requiresReason($toStatus) && trim((string) $reason) === '') {
            throw new InvalidArgumentException(
                sprintf('Alasan wajib diisi untuk transisi status ke [%s].', $toStatus)
            );
        }
    }

    public static function requiresReason(string $toStatus): bool
    {
        return in_array($toStatus, self::REASON_REQUIRED_TARGETS, true);
    }

    public static function publicSummaryFor(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'Permohonan diterima dan menunggu pemeriksaan.',
            self::STATUS_IN_REVIEW => 'Permohonan sedang diproses oleh petugas.',
            self::STATUS_NEEDS_REVISION => 'Permohonan memerlukan perbaikan berkas atau data.',
            self::STATUS_APPROVED => 'Permohonan telah disetujui dan sedang disiapkan.',
            self::STATUS_REJECTED => 'Permohonan ditolak. Periksa catatan petugas.',
            self::STATUS_COMPLETED => 'Layanan selesai dan hasil telah tersedia.',
            self::STATUS_CANCELLED => 'Permohonan dibatalkan.',
            self::STATUS_EXPIRED => 'Masa berlaku permohonan atau tiket telah berakhir.',
            default => 'Status permohonan sedang diperbarui.',
        };
    }
}
