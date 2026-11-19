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
    ];

    protected $hidden = [
        'submitted_ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
        ];
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
