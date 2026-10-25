<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Enums;

enum RoundingRuleEnum: string
{
    case HALF_UP = 'half_up';
    case FLOOR = 'floor';
    case CEIL = 'ceil';

    public function label(): string
    {
        return match ($this) {
            self::HALF_UP => 'Bulat Terdekat (.5 ke atas)',
            self::FLOOR => 'Pembulatan ke Bawah',
            self::CEIL => 'Pembulatan ke Atas',
        };
    }
}
