<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherLeaveRequest extends Model
{
    public const TYPES = [
        'permission' => 'Izin',
        'sick' => 'Sakit',
        'leave' => 'Cuti',
        'duty' => 'Dinas luar',
    ];

    public const MAX_DAYS = 60;

    protected $table = 'teacher_leave_requests';

    protected $fillable = [
        'school_id', 'teacher_id', 'type', 'start_date', 'end_date', 'reason', 'status',
        'reviewed_by_user_id', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'teacher_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_by_user_id' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function dateRangeLabel(): string
    {
        return $this->start_date->isSameDay($this->end_date)
            ? $this->start_date->translatedFormat('d F Y')
            : $this->start_date->translatedFormat('d F').' – '.$this->end_date->translatedFormat('d F Y');
    }
}
