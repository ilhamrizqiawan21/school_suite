<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ExamQuestionVersion extends Model
{
    protected $fillable = [
        'exam_question_id', 'version_number', 'type', 'prompt', 'indicator_text', 'cognitive_level', 'kko_term_id', 'kko_text',
        'options', 'matching_pairs', 'essay_rubric', 'correct_answer', 'explanation', 'points', 'subject_id', 'subject_code_snapshot',
        'subject_name_snapshot', 'jenjang', 'curriculum', 'created_by',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'matching_pairs' => 'array', 'points' => 'float', 'version_number' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Versi butir soal bersifat immutable.'));
        static::deleting(fn () => throw new LogicException('Versi butir soal bersifat immutable.'));
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }

    public function examItems(): HasMany
    {
        return $this->hasMany(ExamQuestionItem::class, 'question_version_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'question_version_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(QuestionCategory::class, 'question_version_category_links', 'question_version_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(QuestionTag::class, 'question_version_tag_links', 'question_version_id', 'tag_id');
    }
}
