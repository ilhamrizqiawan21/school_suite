<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMutation extends Model
{
    protected $table = 'student_mutations';

    protected $fillable = [
        'school_id',
        'term_id',
        'service_ticket_id',
        'direction',
        'student_id',
        'target_class_group_id',
        'submitted_by_user_id',
        'mutation_code',
        'student_name',
        'nis',
        'nisn',
        'gender',
        'birth_date',
        'origin_school',
        'destination_school',
        'reason',
        'applicant_name',
        'applicant_phone',
        'attachment_document_ids',
        'official_letter_document_id',
        'status',
        'decision_note',
        'revision_note',
        'revocation_reason',
        'decided_by_user_id',
        'decided_at',
        'revoked_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'term_id' => 'integer',
            'service_ticket_id' => 'integer',
            'student_id' => 'integer',
            'target_class_group_id' => 'integer',
            'submitted_by_user_id' => 'integer',
            'official_letter_document_id' => 'integer',
            'decided_by_user_id' => 'integer',
            'birth_date' => 'date',
            'attachment_document_ids' => 'array',
            'decided_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /**
     * @return BelongsTo<ClassGroup, $this>
     */
    public function targetClassGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class, 'target_class_group_id');
    }
}
