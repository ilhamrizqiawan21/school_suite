<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionKkoTerm extends Model
{
    protected $table = 'question_kko_terms';

    protected $fillable = ['school_id', 'subject_id', 'term', 'cognitive_level', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
