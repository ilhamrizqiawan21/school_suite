<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Domain\Enums;

enum NoteCategoryEnum: string
{
    case ACADEMIC = 'academic';
    case BEHAVIOR = 'behavior';
    case COUNSELING = 'counseling';
    case GENERAL = 'general';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMIC => 'Akademik & Pembelajaran',
            self::BEHAVIOR => 'Perilaku & Karakter',
            self::COUNSELING => 'Konseling & Pembinaan',
            self::GENERAL => 'Catatan Umum',
        };
    }
}
