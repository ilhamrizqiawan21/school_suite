<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingJournal extends Model
{
    use HasFactory;

    protected $table = 'teaching_journals';

    protected $fillable = [
        'lesson_session_id',
        'topic',
        'theme',
        'period_count',
        'learning_objectives',
        'opening_activity',
        'main_activity',
        'closing_activity',
        'notes',
        'follow_up',
        'attendance_notes',
        'observations',
        'reflection',
        'attendance_summary',
    ];

    protected $casts = [
        'lesson_session_id' => 'integer',
        'period_count' => 'integer',
        'attendance_summary' => 'array',
    ];

    /**
     * @return BelongsTo<LessonSession, $this>
     */
    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }
}
