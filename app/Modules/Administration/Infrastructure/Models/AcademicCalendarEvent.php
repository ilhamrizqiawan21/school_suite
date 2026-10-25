<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicCalendarEvent extends Model
{
    protected $fillable = ['school_id', 'created_by', 'class_group_id', 'audience_role', 'title', 'description', 'event_type', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo('App\\Modules\\Academic\\Infrastructure\\Models\\ClassGroup');
    }
}
