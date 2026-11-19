<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
    protected $table = 'gate_passes';

    protected $fillable = [
        'school_id',
        'student_id',
        'term_id',
        'pass_code',
        'exit_type',
        'reason',
        'destination',
        'valid_from',
        'valid_until',
        'status',
        'issued_by_user_id',
        'exit_recorded_at',
        'exit_recorded_by',
        'return_recorded_at',
        'return_recorded_by',
        'cancelled_at',
        'cancellation_reason',
        'handover_notes',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'term_id' => 'integer',
            'issued_by_user_id' => 'integer',
            'exit_recorded_by' => 'integer',
            'return_recorded_by' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'exit_recorded_at' => 'datetime',
            'return_recorded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
