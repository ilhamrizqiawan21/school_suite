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
        'learning_objectives',
        'notes',
        'attendance_summary',
    ];

    protected $casts = [
        'lesson_session_id' => 'integer',
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
