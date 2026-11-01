<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionTrack extends Model
{
    protected $fillable = ['period_id', 'code', 'name', 'quota', 'is_active'];

    protected function casts(): array
    {
        return ['period_id' => 'integer', 'quota' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<AdmissionPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class, 'period_id');
    }

    /** @return HasMany<AdmissionDocumentRequirement, $this> */
    public function requirements(): HasMany
    {
        return $this->hasMany(AdmissionDocumentRequirement::class, 'track_id')->orderBy('sort_order');
    }

    /** @return HasMany<AdmissionSelectionCriterion, $this> */
    public function criteria(): HasMany
    {
        return $this->hasMany(AdmissionSelectionCriterion::class, 'track_id')->orderBy('sort_order');
    }
}
