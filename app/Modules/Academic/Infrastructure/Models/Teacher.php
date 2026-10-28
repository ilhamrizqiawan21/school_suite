<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
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
        'birth_date',
        'phone',
        'email',
        'employment_status',
        'is_active',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'user_id' => 'integer',
        'birth_date' => 'immutable_date',
        'is_active' => 'boolean',
    ];

    public function retirementDate(): ?CarbonImmutable
    {
        return $this->birth_date?->addYears(60);
    }

    public function retirementStatus(?CarbonImmutable $today = null): string
    {
        $retirementDate = $this->retirementDate();
        if ($retirementDate === null) {
            return 'missing';
        }

        $today ??= CarbonImmutable::today();

        return match (true) {
            $retirementDate->lessThanOrEqualTo($today) => 'reached',
            $retirementDate->lessThanOrEqualTo($today->addYear()) => 'upcoming',
            default => 'not_due',
        };
    }

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
