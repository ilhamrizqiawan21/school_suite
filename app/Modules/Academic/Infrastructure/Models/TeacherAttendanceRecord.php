<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherAttendanceRecord extends Model
{
    protected $table = 'teacher_attendance_records';

    protected $fillable = ['school_id', 'teacher_id', 'date', 'status', 'notes', 'recorded_by_user_id'];

    protected $casts = [
        'school_id' => 'integer',
        'teacher_id' => 'integer',
        'date' => 'date',
        'recorded_by_user_id' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function substituteAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubstituteAssignment::class, 'attendance_record_id');
    }
}
