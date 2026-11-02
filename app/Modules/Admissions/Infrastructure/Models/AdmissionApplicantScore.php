<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplicantScore extends Model
{
    protected $fillable = ['applicant_id', 'criterion_id', 'score', 'notes', 'scored_by'];

    protected function casts(): array
    {
        return ['applicant_id' => 'integer', 'criterion_id' => 'integer', 'score' => 'decimal:2', 'scored_by' => 'integer'];
    }

    /** @return BelongsTo<AdmissionApplicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplicant::class, 'applicant_id');
    }

    /** @return BelongsTo<AdmissionSelectionCriterion, $this> */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(AdmissionSelectionCriterion::class, 'criterion_id');
    }
}
