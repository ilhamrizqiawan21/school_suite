<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentComponent extends Model
{
    use HasFactory;

    protected $table = 'assessment_components';

    protected $fillable = [
        'scheme_id',
        'teaching_assignment_id',
        'name',
        'code',
        'weight_percentage',
        'passing_grade',
        'max_score',
        'sequence',
    ];

    protected $casts = [
        'scheme_id' => 'integer',
        'teaching_assignment_id' => 'integer',
        'weight_percentage' => 'float',
        'passing_grade' => 'float',
        'max_score' => 'float',
        'sequence' => 'integer',
    ];

    /**
     * @return BelongsTo<AssessmentScheme, $this>
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(AssessmentScheme::class, 'scheme_id');
    }

    /**
     * @return HasMany<Score, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'assessment_component_id');
    }
}
