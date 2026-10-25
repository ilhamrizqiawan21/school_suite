<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemedialRecord extends Model
{
    use HasFactory;

    protected $table = 'remedial_records';

    protected $fillable = [
        'score_id',
        'initial_score',
        'remedial_score',
        'passing_grade',
        'adjusted_score',
        'conducted_at',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'score_id' => 'integer',
        'initial_score' => 'float',
        'remedial_score' => 'float',
        'passing_grade' => 'float',
        'adjusted_score' => 'float',
        'conducted_at' => 'date',
        'recorded_by_user_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Score, $this>
     */
    public function score(): BelongsTo
    {
        return $this->belongsTo(Score::class, 'score_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
