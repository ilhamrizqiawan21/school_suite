<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionSelectionResult extends Model
{
    protected $fillable = ['applicant_id', 'total_score', 'rank', 'decision', 'override_reason', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['applicant_id' => 'integer', 'total_score' => 'decimal:2', 'rank' => 'integer', 'finalized_by' => 'integer', 'finalized_at' => 'datetime'];
    }

    /** @return BelongsTo<AdmissionApplicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplicant::class, 'applicant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
