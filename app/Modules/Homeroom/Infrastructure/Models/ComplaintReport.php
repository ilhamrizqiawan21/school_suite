<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintReport extends Model
{
    protected $table = 'complaint_reports';

    protected $fillable = [
        'school_id',
        'service_ticket_id',
        'report_code',
        'category',
        'is_anonymous',
        'reporter_user_id',
        'reporter_name',
        'reporter_contact',
        'reporter_role',
        'title',
        'description',
        'incident_date',
        'location',
        'evidence_document_ids',
        'resolution_document_ids',
        'assigned_to_user_id',
        'status',
        'sla_due_at',
        'task_force_reply',
        'resolution_summary',
        'moderated_public_summary',
        'publish_to_transparency',
        'resolved_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'service_ticket_id' => 'integer',
            'reporter_user_id' => 'integer',
            'assigned_to_user_id' => 'integer',
            'is_anonymous' => 'boolean',
            'publish_to_transparency' => 'boolean',
            'incident_date' => 'date',
            'evidence_document_ids' => 'array',
            'resolution_document_ids' => 'array',
            'sla_due_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
