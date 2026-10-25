<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    protected $fillable = ['school_id', 'teaching_assignment_id', 'owner_id', 'type', 'prompt', 'options', 'correct_answer', 'explanation', 'points', 'is_active'];

    protected function casts(): array
    {
        return ['options' => 'array', 'points' => 'float', 'is_active' => 'boolean'];
    }
}
