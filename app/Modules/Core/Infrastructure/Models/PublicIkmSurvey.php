<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicIkmSurvey extends Model
{
    protected $table = 'public_ikm_surveys';

    protected $fillable = [
        'school_id',
        'respondent_name',
        'respondent_role',
        'service_name',
        'rating',
        'comment',
        'is_approved',
        'submitted_ip_hash',
        'instrument_snapshot',
    ];

    protected $hidden = [
        'submitted_ip_hash',
        'instrument_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'instrument_snapshot' => 'array',
            'is_approved' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (self $survey): void {
            if ($survey->isDirty('instrument_snapshot') || $survey->isDirty('rating')) {
                throw new \LogicException('Survey scores and instrument snapshots are immutable.');
            }
        });
    }

    public function getRatingAttribute(mixed $value): float
    {
        return (float) ($this->instrument_snapshot['score'] ?? $value);
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
