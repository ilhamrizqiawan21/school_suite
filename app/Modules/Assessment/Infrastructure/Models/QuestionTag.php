<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionTag extends Model
{
    protected $fillable = ['school_id', 'name'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(ExamQuestion::class, 'question_tag_links', 'tag_id', 'exam_question_id');
    }

    public function versions(): BelongsToMany
    {
        return $this->belongsToMany(ExamQuestionVersion::class, 'question_version_tag_links', 'tag_id', 'question_version_id');
    }
}
