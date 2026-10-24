<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $fillable = [
        'school_id',
        'user_id',
        'nis',
        'nisn',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'parent_phone',
        'parent_name',
        'status',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'user_id' => 'integer',
        'birth_date' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * Helper to get enrollment for a specific term.
     */
    public function enrollmentForTerm(int $termId): HasOne
    {
        return $this->hasOne(Enrollment::class, 'student_id')->where('term_id', $termId);
    }
}
