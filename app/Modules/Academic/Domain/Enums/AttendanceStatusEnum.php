<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\Enums;

enum AttendanceStatusEnum: string
{
    case PRESENT = 'present';
    case SICK = 'sick';
    case PERMITTED = 'permitted';
    case ABSENT = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Hadir',
            self::SICK => 'Sakit',
            self::PERMITTED => 'Izin',
            self::ABSENT => 'Alpa / Tanpa Keterangan',
        };
    }
}
