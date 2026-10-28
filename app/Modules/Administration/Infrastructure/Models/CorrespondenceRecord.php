<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrespondenceRecord extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'letter_type', 'agenda_number', 'letter_number',
        'letter_template', 'classification', 'urgency',
        'letter_date', 'received_at', 'sender', 'recipient', 'subject', 'summary',
        'disposition', 'status', 'attachment_path', 'attachment_name', 'archived_at',
        'approval_required', 'approval_status', 'approved_by', 'approved_at', 'approval_note',
        'signature_method', 'document_hash', 'letter_body', 'signer_name', 'signer_title',
        'attachment_note', 'verification_token', 'school_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'created_by' => 'integer',
            'approval_required' => 'boolean',
            'approved_by' => 'integer',
            'letter_date' => 'date',
            'received_at' => 'date',
            'archived_at' => 'datetime',
            'approved_at' => 'datetime',
            'school_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
