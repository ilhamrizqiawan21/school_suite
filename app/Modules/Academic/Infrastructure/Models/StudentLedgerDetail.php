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
        'kk_number',
        'citizenship',
        'child_order',
        'siblings_count',
        'family_status',
        'daily_language',
        'blood_type',
        'height_cm',
        'weight_kg',
        'health_history',
        'address_rt',
        'address_rw',
        'village',
        'district',
        'city',
        'postal_code',
        'living_with',
        'distance_km',
        'sports_hobby',
        'arts_hobby',
        'photo_path',
        'father_name',
        'father_nik',
        'father_education',
        'father_occupation',
        'father_income',
        'father_phone',
        'mother_name',
        'mother_nik',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'mother_phone',
        'guardian_relation',
        'guardian_occupation',
        'guardian_address',
        'previous_school',
        'diploma_number',
        'diploma_date',
        'study_duration_years',
        'accepted_date',
        'accepted_grade',
        'admission_type',
        'graduation_date',
        'graduation_diploma_number',
        'continuing_to',
        'leaving_reason',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'child_order' => 'integer',
            'siblings_count' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'integer',
            'distance_km' => 'float',
            'diploma_date' => 'date',
            'accepted_date' => 'date',
            'graduation_date' => 'date',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
