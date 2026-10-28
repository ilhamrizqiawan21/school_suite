<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrespondenceRecord extends Model
{
    protected static function booted(): void
    {
        static::saving(function (CorrespondenceRecord $record): void {
            $record->letter_year = $record->letter_date?->format('Y');
        });
    }

    protected $fillable = [
        'school_id', 'created_by', 'letter_type', 'agenda_number', 'letter_number',
        'letter_template', 'classification', 'urgency',
        'letter_date', 'letter_year', 'received_at', 'sender', 'recipient', 'subject', 'summary',
        'disposition', 'status', 'attachment_path', 'attachment_name', 'archived_at',
        'approval_required', 'approval_status', 'approved_by', 'approved_at', 'approval_note',
        'signature_method', 'document_hash', 'letter_body', 'signer_name', 'signer_title', 'signer_identifier_type', 'signer_identifier',
        'attachment_note', 'verification_token', 'verification_expires_at', 'verification_revoked_at', 'school_snapshot',
        'signatory_id', 'recipient_address', 'opening_greeting', 'closing_text', 'carbon_copy',
        'signer_snapshot', 'is_legacy',
    ];

    protected $hidden = ['verification_token'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'created_by' => 'integer',
            'approval_required' => 'boolean',
            'approved_by' => 'integer',
            'letter_date' => 'date',
            'letter_year' => 'integer',
            'received_at' => 'date',
            'archived_at' => 'datetime',
            'approved_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'verification_revoked_at' => 'datetime',
            'school_snapshot' => 'array',
            'signer_snapshot' => 'array',
            'signatory_id' => 'integer',
            'is_legacy' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceSignatory::class, 'signatory_id');
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(CorrespondenceDocumentVersion::class, 'correspondence_record_id');
    }

    public function signatureRequests(): HasMany
    {
        return $this->hasMany(CorrespondenceSignatureRequest::class, 'correspondence_record_id');
    }
}
