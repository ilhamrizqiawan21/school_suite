<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSubstituteAssignment extends Model
{
    protected $table = 'teacher_substitute_assignments';

    protected $fillable = [
        'school_id', 'attendance_record_id', 'schedule_id', 'substitute_teacher_id', 'assigned_by_user_id', 'date', 'notes',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'attendance_record_id' => 'integer',
        'schedule_id' => 'integer',
        'substitute_teacher_id' => 'integer',
        'assigned_by_user_id' => 'integer',
        'date' => 'date',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(TeacherAttendanceRecord::class, 'attendance_record_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
