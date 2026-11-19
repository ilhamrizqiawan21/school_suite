<?php

declare(strict_types=1);

namespace App\Modules\Integration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityProposal extends Model
{
    public const MODE_TEACHER_WORK = 'teacher_work';

    public const MODE_INDEPENDENT = 'independent';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LPJ_SUBMITTED = 'lpj_submitted';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'school_id',
        'service_ticket_id',
        'proposal_mode',
        'proposal_code',
        'title',
        'activity_type',
        'organizer_name',
        'organizer_unit',
        'committee_members',
        'start_date',
        'end_date',
        'location',
        'purpose',
        'budget_items',
        'total_requested_amount',
        'revision_number',
        'revision_history',
        'attachment_document_id',
        'status',
        'review_note',
        'rejection_reason',
        'submitted_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'lpj_realization_amount',
        'lpj_summary',
        'lpj_document_ids',
        'lpj_submitted_at',
        'closed_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'committee_members' => 'array',
            'budget_items' => 'array',
            'revision_history' => 'array',
            'lpj_document_ids' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'total_requested_amount' => 'integer',
            'revision_number' => 'integer',
            'lpj_realization_amount' => 'integer',
            'reviewed_at' => 'datetime',
            'lpj_submitted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
