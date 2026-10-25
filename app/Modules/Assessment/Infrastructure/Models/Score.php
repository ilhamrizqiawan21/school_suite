<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Score extends Model
{
    use HasFactory;

    protected $table = 'scores';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'assessment_component_id',
        'raw_score',
        'remedial_score',
        'final_score',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'enrollment_id' => 'integer',
        'assessment_component_id' => 'integer',
        'raw_score' => 'float',
        'remedial_score' => 'float',
        'final_score' => 'float',
        'recorded_by_user_id' => 'integer',
    ];

    /**
     * @return BelongsTo<AssessmentComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * @return HasMany<RemedialRecord, $this>
     */
    public function remedialRecords(): HasMany
    {
        return $this->hasMany(RemedialRecord::class, 'score_id');
    }
}
