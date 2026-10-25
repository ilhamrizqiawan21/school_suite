<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineClassSession extends Model
{
    protected $fillable = ['school_id', 'term_id', 'class_group_id', 'teaching_assignment_id', 'title', 'provider', 'meeting_url', 'starts_at', 'ends_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(OnlineAttendanceRecord::class);
    }
}
