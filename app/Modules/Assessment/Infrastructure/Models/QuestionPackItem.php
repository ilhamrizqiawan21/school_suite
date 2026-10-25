<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionPackItem extends Model
{
    protected $fillable = ['question_pack_id', 'exam_question_id', 'question_version_id', 'sequence', 'points'];

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'points' => 'float'];
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(QuestionPack::class, 'question_pack_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(ExamQuestionVersion::class, 'question_version_id');
    }
}
