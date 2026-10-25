<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    protected $fillable = ['exam_attempt_id', 'exam_question_id', 'answer', 'is_correct', 'score', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'score' => 'float', 'graded_at' => 'datetime'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
