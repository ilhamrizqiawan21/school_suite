<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'lesson_session_id',
        'date',
        'type',
        'status',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'enrollment_id' => 'integer',
        'lesson_session_id' => 'integer',
        'date' => 'date',
        'recorded_by_user_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    /**
     * @return BelongsTo<LessonSession, $this>
     */
    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
