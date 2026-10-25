<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Enums;

enum ReportStatusEnum: string
{
    case DRAFT = 'draft';
    case REVIEWED = 'reviewed';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf Rapor',
            self::REVIEWED => 'Telah Diverifikasi Wali Kelas',
            self::PUBLISHED => 'Rapor Sah & Terbit',
        };
    }
}
