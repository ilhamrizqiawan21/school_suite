<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GraduationBatch extends Model
{
    protected $table = 'graduation_batches';

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'title',
        'skl_prefix',
        'release_at',
        'is_published',
        'signatory_name',
        'signatory_title',
        'published_by_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'academic_year_id' => 'integer',
            'release_at' => 'datetime',
            'is_published' => 'boolean',
            'published_by_user_id' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function isReleased(): bool
    {
        return $this->is_published && ($this->release_at === null || ! $this->release_at->isFuture());
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * @return HasMany<GraduationRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(GraduationRecord::class, 'batch_id');
    }
}
