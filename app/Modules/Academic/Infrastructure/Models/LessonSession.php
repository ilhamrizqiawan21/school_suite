<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LessonSession extends Model
{
    use HasFactory;

    protected $table = 'lesson_sessions';

    protected $fillable = [
        'school_id',
        'schedule_id',
        'teaching_assignment_id',
        'term_id',
        'class_group_id',
        'subject_id',
        'teacher_id',
        'date',
        'start_time',
        'end_time',
        'meeting_number',
        'status',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'schedule_id' => 'integer',
        'teaching_assignment_id' => 'integer',
        'term_id' => 'integer',
        'class_group_id' => 'integer',
        'subject_id' => 'integer',
        'teacher_id' => 'integer',
        'date' => 'date',
        'meeting_number' => 'integer',
    ];

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo<TeachingAssignment, $this>
     */
    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /**
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'lesson_session_id');
    }

    /**
     * @return HasOne<TeachingJournal, $this>
     */
    public function journal(): HasOne
    {
        return $this->hasOne(TeachingJournal::class, 'lesson_session_id');
    }
}
