<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplicantDocument extends Model
{
    protected $fillable = [
        'applicant_id', 'requirement_id', 'path', 'original_name', 'mime_type', 'size',
        'status', 'notes', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['applicant_id' => 'integer', 'requirement_id' => 'integer', 'size' => 'integer', 'verified_by' => 'integer', 'verified_at' => 'datetime'];
    }

    /** @return BelongsTo<AdmissionApplicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplicant::class, 'applicant_id');
    }

    /** @return BelongsTo<AdmissionDocumentRequirement, $this> */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(AdmissionDocumentRequirement::class, 'requirement_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
