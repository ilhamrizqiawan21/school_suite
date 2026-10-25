<?php

declare(strict_types=1);

namespace App\Modules\Learning\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmissionRevision extends Model
{
    protected $fillable = ['assignment_submission_id', 'revision_number', 'content', 'file_names', 'submitted_at'];

    protected function casts(): array
    {
        return ['file_names' => 'array', 'submitted_at' => 'datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }
}
