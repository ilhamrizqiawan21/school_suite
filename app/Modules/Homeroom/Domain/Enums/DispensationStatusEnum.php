<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Domain\Enums;

enum DispensationStatusEnum: string
{
    case PENDING = 'pending';
    case NEEDS_REVISION = 'needs_revision';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Verifikasi',
            self::NEEDS_REVISION => 'Perlu Perbaikan',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
