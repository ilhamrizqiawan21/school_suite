<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class GuestbookVisit extends Model
{
    protected $table = 'guestbook_visits';

    protected $fillable = [
        'school_id',
        'visit_code',
        'visitor_name',
        'visitor_organization',
        'visitor_phone',
        'visitor_email',
        'category',
        'purpose',
        'host_teacher_id',
        'host_name',
        'host_unit',
        'status',
        'checked_in_at',
        'checked_out_at',
        'recorded_by',
        'checked_out_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'host_teacher_id' => 'integer',
            'recorded_by' => 'integer',
            'checked_out_by' => 'integer',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }
}
