<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLedgerDetail extends Model
{
    protected $table = 'administration_student_ledger_details';

    protected $fillable = [
        'school_id',
        'student_id',
        'nickname',
        'nik',
        'blood_type',
        'address_rt',
        'address_rw',
        'village',
        'district',
        'sports_hobby',
        'photo_path',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'previous_school',
        'diploma_number',
        'diploma_date',
    ];

    protected function casts(): array
    {
        return ['school_id' => 'integer', 'student_id' => 'integer', 'diploma_date' => 'date'];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
