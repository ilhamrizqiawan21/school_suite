<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class ExamEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['exam_attempt_id', 'type', 'payload', 'ip_address', 'occurred_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime'];
    }
}
