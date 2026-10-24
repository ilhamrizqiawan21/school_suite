<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\Enums;

enum ScheduleStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf (Belum Terbit)',
            self::PUBLISHED => 'Diterbitkan',
        };
    }
}
