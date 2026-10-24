<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\Enums;

enum AttendanceTypeEnum: string
{
    case DAILY = 'daily';
    case SESSION = 'session';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Presensi Harian Sekolah',
            self::SESSION => 'Presensi Sesi Pertemuan',
        };
    }
}
