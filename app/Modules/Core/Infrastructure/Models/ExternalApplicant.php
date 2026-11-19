<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalApplicant extends Model
{
    protected $table = 'external_applicants';

    protected $fillable = [
        'school_id',
        'user_id',
        'applicant_type',
        'full_name',
        'identifier_last4',
        'identifier_hash',
        'phone',
        'email',
        'organization_name',
        'is_verified',
        'verified_at',
    ];

    protected $hidden = [
        'identifier_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
