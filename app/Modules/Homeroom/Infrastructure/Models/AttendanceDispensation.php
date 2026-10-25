<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use App\Models\User;
use App\Modules\Homeroom\Domain\Enums\DispensationStatusEnum;
use App\Modules\Homeroom\Domain\Enums\DispensationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDispensation extends Model
{
    use HasFactory;

    protected $table = 'attendance_dispensations';

    protected $fillable = [
        'school_id',
        'student_id',
        'term_id',
        'submitted_by_user_id',
        'start_date',
        'end_date',
        'type',
        'title',
        'reason',
        'attachment_path',
        'status',
        'approved_by_user_id',
        'notes',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'term_id' => 'integer',
        'submitted_by_user_id' => 'integer',
        'approved_by_user_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'type' => DispensationTypeEnum::class,
        'status' => DispensationStatusEnum::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
