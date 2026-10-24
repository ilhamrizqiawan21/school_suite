<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Domain\Enums;

enum AchievementLevelEnum: string
{
    case SCHOOL = 'school';
    case DISTRICT = 'district';
    case CITY = 'city';
    case PROVINCE = 'province';
    case NATIONAL = 'national';
    case INTERNATIONAL = 'international';

    public function label(): string
    {
        return match ($this) {
            self::SCHOOL => 'Tingkat Sekolah',
            self::DISTRICT => 'Tingkat Kecamatan',
            self::CITY => 'Tingkat Kabupaten/Kota',
            self::PROVINCE => 'Tingkat Provinsi',
            self::NATIONAL => 'Tingkat Nasional',
            self::INTERNATIONAL => 'Tingkat Internasional',
        };
    }
}
