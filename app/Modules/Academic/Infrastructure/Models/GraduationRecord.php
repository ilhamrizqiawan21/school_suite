<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraduationRecord extends Model
{
    protected $table = 'graduation_records';

    protected $fillable = [
        'school_id',
        'batch_id',
        'student_id',
        'enrollment_id',
        'decision',
        'skl_number',
        'average_score',
        'skl_document_id',
        'access_pin_hash',
        'reissue_count',
        'reissue_reason',
        'original_collected_at',
        'original_collected_by',
        'collector_relation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'batch_id' => 'integer',
            'student_id' => 'integer',
            'enrollment_id' => 'integer',
            'skl_document_id' => 'integer',
            'average_score' => 'decimal:2',
            'reissue_count' => 'integer',
            'original_collected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GraduationBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(GraduationBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }
}
