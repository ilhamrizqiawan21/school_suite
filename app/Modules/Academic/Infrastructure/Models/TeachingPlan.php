<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingPlan extends Model
{
    protected $table = 'teaching_plans';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'term_id',
        'subject_id',
        'grade_level',
        'curriculum',
        'data',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'teacher_id' => 'integer',
        'term_id' => 'integer',
        'subject_id' => 'integer',
        'grade_level' => 'integer',
        'data' => 'array',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
