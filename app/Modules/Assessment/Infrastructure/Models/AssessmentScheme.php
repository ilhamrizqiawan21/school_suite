<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentScheme extends Model
{
    use HasFactory;

    protected $table = 'assessment_schemes';

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'version',
        'is_active',
        'rounding_rule',
        'decimal_places',
        'passing_grade_default',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'version' => 'integer',
        'is_active' => 'boolean',
        'rounding_rule' => RoundingRuleEnum::class,
        'decimal_places' => 'integer',
        'passing_grade_default' => 'float',
    ];

    /**
     * @return HasMany<AssessmentComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class, 'scheme_id')->orderBy('sequence');
    }
}
