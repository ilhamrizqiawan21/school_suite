<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdmissionApplicant extends Model
{
    protected $fillable = [
        'school_id', 'period_id', 'track_id', 'registration_no', 'name', 'nisn', 'gender', 'birth_date',
        'parent_name', 'parent_phone', 'previous_school', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'school_id' => 'integer', 'period_id' => 'integer', 'track_id' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<AdmissionPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class, 'period_id');
    }

    /** @return BelongsTo<AdmissionTrack, $this> */
    public function track(): BelongsTo
    {
        return $this->belongsTo(AdmissionTrack::class, 'track_id');
    }

    /** @return HasMany<AdmissionApplicantDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(AdmissionApplicantDocument::class, 'applicant_id');
    }

    /** @return HasMany<AdmissionApplicantScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(AdmissionApplicantScore::class, 'applicant_id');
    }

    /** @return HasOne<AdmissionSelectionResult, $this> */
    public function selectionResult(): HasOne
    {
        return $this->hasOne(AdmissionSelectionResult::class, 'applicant_id');
    }

    /** @return HasOne<AdmissionReRegistration, $this> */
    public function reRegistration(): HasOne
    {
        return $this->hasOne(AdmissionReRegistration::class, 'applicant_id');
    }
}
