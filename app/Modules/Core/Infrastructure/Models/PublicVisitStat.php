<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicVisitStat extends Model
{
    protected $table = 'public_visit_stats';

    protected $fillable = [
        'school_id',
        'visit_date',
        'hits',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'hits' => 'integer',
        ];
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
