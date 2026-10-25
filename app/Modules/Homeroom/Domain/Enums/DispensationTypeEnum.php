<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Domain\Enums;

enum DispensationTypeEnum: string
{
    case SICK = 'sick';
    case EVENT = 'event';
    case PERSONAL = 'personal';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SICK => 'Surat Sakit / Dokter',
            self::EVENT => 'Dispensasi Lomba / Acara',
            self::PERSONAL => 'Izin Keluarga',
            self::OTHER => 'Lainnya',
        };
    }
}
