<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionPeriod extends Model
{
    protected $fillable = [
        'school_id', 'name', 'academic_year', 'level', 'status', 'guideline_path',
        'registration_start', 'registration_end', 'verification_start', 'verification_end',
        'selection_start', 'selection_end', 'announcement_at', 're_registration_start',
        're_registration_end',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'registration_start' => 'date',
            'registration_end' => 'date',
            'verification_start' => 'date',
            'verification_end' => 'date',
            'selection_start' => 'date',
            'selection_end' => 'date',
            'announcement_at' => 'date',
            're_registration_start' => 'date',
            're_registration_end' => 'date',
        ];
    }

    /** @return HasMany<AdmissionTrack, $this> */
    public function tracks(): HasMany
    {
        return $this->hasMany(AdmissionTrack::class, 'period_id');
    }

    /** @return HasMany<AdmissionApplicant, $this> */
    public function applicants(): HasMany
    {
        return $this->hasMany(AdmissionApplicant::class, 'period_id');
    }
}
