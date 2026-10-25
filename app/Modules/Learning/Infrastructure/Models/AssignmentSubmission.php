<?php

declare(strict_types=1);

namespace App\Modules\Learning\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentSubmission extends Model
{
    protected $fillable = ['learning_assignment_id', 'enrollment_id', 'content', 'status', 'review_status', 'revision_count', 'submitted_at', 'score', 'feedback', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'graded_at' => 'datetime', 'score' => 'decimal:2'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(LearningAssignment::class, 'learning_assignment_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo('App\\Modules\\Academic\\Infrastructure\\Models\\Enrollment');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionFile::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionRevision::class)->orderByDesc('revision_number');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
