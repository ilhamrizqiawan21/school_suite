<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    protected $fillable = [
        'school_id', 'teaching_assignment_id', 'owner_id', 'subject_id', 'type', 'jenjang', 'curriculum',
        'prompt', 'indicator_text', 'cognitive_level', 'kko_term_id', 'kko_text', 'options', 'matching_pairs', 'essay_rubric', 'correct_answer', 'explanation',
        'points', 'is_active', 'current_version',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'matching_pairs' => 'array', 'points' => 'float', 'is_active' => 'boolean', 'current_version' => 'integer'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ExamQuestionVersion::class)->orderByDesc('version_number');
    }

    public function examItems(): HasMany
    {
        return $this->hasMany(ExamQuestionItem::class, 'exam_question_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(QuestionCategory::class, 'question_category_links', 'exam_question_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(QuestionTag::class, 'question_tag_links', 'exam_question_id', 'tag_id');
    }

    public function currentVersionRecord(): ?ExamQuestionVersion
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions->firstWhere('version_number', $this->current_version);
        }

        return $this->versions()->where('version_number', $this->current_version)->first();
    }
}
