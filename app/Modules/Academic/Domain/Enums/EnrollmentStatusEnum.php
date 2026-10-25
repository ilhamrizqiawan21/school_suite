<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\Enums;

enum EnrollmentStatusEnum: string
{
    case ACTIVE = 'active';
    case TRANSFERRED = 'transferred';
    case WITHDRAWN = 'withdrawn';
    case PROMOTED = 'promoted';
    case RETAINED = 'retained';
    case GRADUATED = 'graduated';
}
