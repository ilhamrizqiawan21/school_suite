<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = ['school_id', 'teaching_assignment_id', 'assessment_component_id', 'created_by', 'title', 'instructions', 'starts_at', 'ends_at', 'duration_minutes', 'randomize_questions', 'randomize_options', 'max_violations', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'randomize_questions' => 'boolean', 'randomize_options' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExamQuestionItem::class)->orderBy('sequence');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
