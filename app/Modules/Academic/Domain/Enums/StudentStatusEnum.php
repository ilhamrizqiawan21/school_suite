<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\Enums;

enum StudentStatusEnum: string
{
    case ACTIVE = 'active';
    case GRADUATED = 'graduated';
    case TRANSFERRED = 'transferred';
    case DROPPED_OUT = 'dropped_out';
}
