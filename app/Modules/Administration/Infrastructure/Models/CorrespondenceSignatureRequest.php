<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrespondenceSignatureRequest extends Model
{
    protected $fillable = ['school_id', 'correspondence_record_id', 'document_version_id', 'signatory_id', 'provider', 'idempotency_key', 'provider_request_id', 'status', 'attempts', 'error_code', 'certificate_metadata', 'signed_at', 'last_attempted_at'];

    protected function casts(): array
    {
        return ['school_id' => 'integer', 'correspondence_record_id' => 'integer', 'document_version_id' => 'integer', 'signatory_id' => 'integer', 'attempts' => 'integer', 'certificate_metadata' => 'array', 'signed_at' => 'datetime', 'last_attempted_at' => 'datetime'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceRecord::class, 'correspondence_record_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceDocumentVersion::class, 'document_version_id');
    }
}
