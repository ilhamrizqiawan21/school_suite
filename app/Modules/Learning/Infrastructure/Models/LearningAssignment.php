<?php

declare(strict_types=1);

namespace App\Modules\Learning\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningAssignment extends Model
{
    protected $fillable = ['school_id', 'teaching_assignment_id', 'created_by', 'title', 'instructions', 'due_at', 'max_score', 'allow_late', 'revision_limit', 'published_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'published_at' => 'datetime', 'allow_late' => 'boolean', 'max_score' => 'integer', 'revision_limit' => 'integer'];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo('App\\Modules\\Academic\\Infrastructure\\Models\\TeachingAssignment');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
