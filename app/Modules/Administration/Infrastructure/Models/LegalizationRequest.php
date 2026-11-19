<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class LegalizationRequest extends Model
{
    protected $table = 'legalization_requests';

    protected $fillable = [
        'school_id',
        'service_ticket_id',
        'external_applicant_id',
        'applicant_user_id',
        'legalization_code',
        'applicant_name',
        'applicant_nis',
        'applicant_nisn',
        'graduation_year',
        'phone',
        'email',
        'document_type',
        'certificate_number',
        'copy_count',
        'purpose',
        'delivery_method',
        'proxy_name',
        'proxy_nik_last4',
        'proxy_relation',
        'proxy_phone',
        'signature_data',
        'attachment_document_ids',
        'power_of_attorney_document_id',
        'sptjm_document_id',
        'legalization_document_id',
        'status',
        'original_verified_at',
        'original_verified_by',
        'original_verification_note',
        'revision_note',
        'rejection_reason',
        'picked_up_at',
        'picked_up_by_name',
        'pickup_relation',
        'pickup_notes',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'service_ticket_id' => 'integer',
            'external_applicant_id' => 'integer',
            'applicant_user_id' => 'integer',
            'graduation_year' => 'integer',
            'copy_count' => 'integer',
            'attachment_document_ids' => 'array',
            'power_of_attorney_document_id' => 'integer',
            'sptjm_document_id' => 'integer',
            'legalization_document_id' => 'integer',
            'original_verified_by' => 'integer',
            'original_verified_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }
}
