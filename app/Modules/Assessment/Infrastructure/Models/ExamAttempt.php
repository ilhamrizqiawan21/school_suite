<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['exam_id', 'enrollment_id', 'status', 'started_at', 'expires_at', 'last_heartbeat_at', 'submitted_at', 'score', 'violation_count', 'question_order', 'random_seed'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'last_heartbeat_at' => 'datetime', 'submitted_at' => 'datetime', 'score' => 'float', 'question_order' => 'array', 'random_seed' => 'integer'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExamEvent::class)->latest('occurred_at');
    }
}
