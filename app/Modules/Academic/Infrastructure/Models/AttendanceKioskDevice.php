<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceKioskDevice extends Model
{
    protected $table = 'attendance_kiosk_devices';

    protected $fillable = [
        'school_id',
        'name',
        'location',
        'device_token_hash',
        'gate_enabled',
        'is_active',
        'last_seen_at',
        'created_by_user_id',
    ];

    protected $hidden = ['device_token_hash'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'gate_enabled' => 'boolean',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}
