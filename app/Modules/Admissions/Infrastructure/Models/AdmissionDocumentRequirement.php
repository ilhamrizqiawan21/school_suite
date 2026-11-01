<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDocumentRequirement extends Model
{
    protected $fillable = ['track_id', 'code', 'label', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return ['track_id' => 'integer', 'is_required' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @return BelongsTo<AdmissionTrack, $this> */
    public function track(): BelongsTo
    {
        return $this->belongsTo(AdmissionTrack::class, 'track_id');
    }
}
