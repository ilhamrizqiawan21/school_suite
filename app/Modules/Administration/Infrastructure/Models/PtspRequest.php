<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtspRequest extends Model
{
    protected $table = 'ptsp_requests';

    protected $fillable = [
        'school_id',
        'ptsp_service_id',
        'service_ticket_id',
        'applicant_user_id',
        'external_applicant_id',
        'draft_token',
        'service_snapshot',
        'applicant_type',
        'applicant_name',
        'applicant_phone',
        'applicant_email',
        'applicant_organization',
        'purpose',
        'notes',
        'attachment_document_ids',
        'status',
        'revision_note',
        'rejection_reason',
        'disposition_to',
        'disposition_note',
        'result_document_id',
        'result_delivery_mode',
        'physical_pickup_name',
        'physical_pickup_at',
        'physical_pickup_notes',
        'submitted_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'ptsp_service_id' => 'integer',
            'service_ticket_id' => 'integer',
            'applicant_user_id' => 'integer',
            'external_applicant_id' => 'integer',
            'service_snapshot' => 'array',
            'attachment_document_ids' => 'array',
            'result_document_id' => 'integer',
            'physical_pickup_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PtspService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(PtspService::class, 'ptsp_service_id');
    }
}
