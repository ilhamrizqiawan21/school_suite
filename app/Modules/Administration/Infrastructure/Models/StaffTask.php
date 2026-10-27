<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffTask extends Model
{
    protected $table = 'administration_staff_tasks';

    protected $fillable = [
        'school_id', 'created_by', 'assignee_id', 'title', 'description', 'category',
        'priority', 'status', 'progress', 'attachment_path', 'attachment_name', 'due_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(StaffTaskActivity::class, 'task_id')->orderBy('created_at');
    }
}
