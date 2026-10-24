<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistanceRecord extends Model
{
    use HasFactory;

    protected $table = 'assistance_records';

    protected $fillable = [
        'school_id',
        'student_id',
        'term_id',
        'facilitator_id',
        'date',
        'type',
        'summary',
        'agreed_solution',
        'access_level',
        'status',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'term_id' => 'integer',
        'facilitator_id' => 'integer',
        'date' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }
}
