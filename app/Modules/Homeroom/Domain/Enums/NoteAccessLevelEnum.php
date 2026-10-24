<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Domain\Enums;

enum NoteAccessLevelEnum: string
{
    case PUBLIC = 'public';
    case INTERNAL = 'internal';
    case CONFIDENTIAL = 'confidential';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Publik (Tercetak di Rapor / Wali Murid)',
            self::INTERNAL => 'Internal (Dewan Guru)',
            self::CONFIDENTIAL => 'Rahasia (Wali Kelas, BK, & Pimpinan)',
        };
    }
}
