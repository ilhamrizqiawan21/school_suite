<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';

    protected $fillable = [
        'school_id',
        'user_id',
        'nip',
        'name',
        'gender',
        'phone',
        'email',
        'employment_status',
        'is_active',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<ClassGroup, $this>
     */
    public function homeroomClassGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'homeroom_teacher_id');
    }

    /**
     * @return HasMany<TeachingAssignment, $this>
     */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_id');
    }
}
