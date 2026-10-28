<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrespondenceDocumentVersion extends Model
{
    protected $fillable = ['school_id', 'correspondence_record_id', 'version', 'kind', 'storage_path', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'signature_method', 'finalized_at', 'created_by'];

    protected function casts(): array
    {
        return ['school_id' => 'integer', 'correspondence_record_id' => 'integer', 'version' => 'integer', 'size_bytes' => 'integer', 'created_by' => 'integer', 'finalized_at' => 'datetime'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceRecord::class, 'correspondence_record_id');
    }
}
