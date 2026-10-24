<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    use HasFactory;

    protected $table = 'student_achievements';

    protected $fillable = [
        'school_id',
        'student_id',
        'term_id',
        'title',
        'achievement_type',
        'level',
        'rank',
        'event_date',
        'organizer',
        'notes',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'term_id' => 'integer',
        'event_date' => 'date',
    ];
}
