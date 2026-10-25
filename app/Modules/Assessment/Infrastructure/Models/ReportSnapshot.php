<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Models\User;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSnapshot extends Model
{
    use HasFactory;

    protected $table = 'report_snapshots';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'term_id',
        'class_group_id',
        'student_id',
        'status',
        'scheme_snapshot',
        'grades_snapshot',
        'attendance_snapshot',
        'extracurricular_snapshot',
        'notes_snapshot',
        'checksum',
        'published_at',
        'published_by_user_id',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'enrollment_id' => 'integer',
        'term_id' => 'integer',
        'class_group_id' => 'integer',
        'student_id' => 'integer',
        'status' => ReportStatusEnum::class,
        'scheme_snapshot' => 'array',
        'grades_snapshot' => 'array',
        'attendance_snapshot' => 'array',
        'extracurricular_snapshot' => 'array',
        'notes_snapshot' => 'array',
        'published_at' => 'datetime',
        'published_by_user_id' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
