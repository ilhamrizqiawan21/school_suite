<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLedgerDocument extends Model
{
    protected $table = 'administration_student_ledger_documents';

    protected $fillable = [
        'school_id',
        'student_id',
        'uploaded_by',
        'document_type',
        'file_path',
        'original_name',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return ['school_id' => 'integer', 'student_id' => 'integer', 'uploaded_by' => 'integer', 'size_bytes' => 'integer'];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
