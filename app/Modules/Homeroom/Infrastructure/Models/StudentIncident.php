<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIncident extends Model
{
    use HasFactory;

    protected $table = 'student_incidents';

    protected $fillable = [
        'school_id',
        'student_id',
        'term_id',
        'class_group_id',
        'recorded_by_user_id',
        'incident_date',
        'title',
        'description',
        'category',
        'penalty_points',
        'follow_up_action',
        'status',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'term_id' => 'integer',
        'class_group_id' => 'integer',
        'recorded_by_user_id' => 'integer',
        'incident_date' => 'date',
        'penalty_points' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
