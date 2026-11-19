<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTicket extends Model
{
    protected $table = 'service_tickets';

    protected $fillable = [
        'school_id',
        'service_type',
        'ticket_code',
        'verification_secret_hash',
        'applicant_user_id',
        'external_applicant_id',
        'subject_student_id',
        'applicant_snapshot',
        'public_status',
        'internal_status',
        'public_summary',
        'is_anonymous',
        'idempotency_key',
        'version',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'verification_secret_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applicant_snapshot' => 'array',
            'is_anonymous' => 'boolean',
            'version' => 'integer',
            'expires_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function applicantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    /**
     * @return BelongsTo<ExternalApplicant, $this>
     */
    public function externalApplicant(): BelongsTo
    {
        return $this->belongsTo(ExternalApplicant::class, 'external_applicant_id');
    }

    /**
     * @return HasMany<ServiceTicketTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(ServiceTicketTransition::class, 'service_ticket_id')->orderBy('id');
    }

    /**
     * @return HasMany<ServiceDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ServiceDocument::class, 'service_ticket_id')->orderBy('id');
    }
}
