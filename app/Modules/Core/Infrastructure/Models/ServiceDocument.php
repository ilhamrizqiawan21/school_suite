<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDocument extends Model
{
    protected $table = 'service_documents';

    protected $fillable = [
        'school_id',
        'service_ticket_id',
        'document_kind',
        'template_key',
        'template_version',
        'document_number',
        'verification_token',
        'storage_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sha256',
        'signatory_name',
        'signatory_title',
        'signature_mode',
        'status',
        'issued_at',
        'revoked_at',
        'revocation_reason',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'size_bytes' => 'integer',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * @return BelongsTo<ServiceTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
