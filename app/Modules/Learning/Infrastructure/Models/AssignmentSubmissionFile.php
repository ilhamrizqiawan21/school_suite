<?php

declare(strict_types=1);

namespace App\Modules\Learning\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmissionFile extends Model
{
    protected $fillable = ['assignment_submission_id', 'file_path', 'original_filename', 'mime_type', 'size'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }
}
