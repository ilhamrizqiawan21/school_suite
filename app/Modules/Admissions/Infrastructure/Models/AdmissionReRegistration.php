<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use App\Modules\Academic\Infrastructure\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionReRegistration extends Model
{
    protected $fillable = ['applicant_id', 'status', 'notes', 'student_id', 'confirmed_by', 'confirmed_at'];

    protected function casts(): array
    {
        return ['applicant_id' => 'integer', 'student_id' => 'integer', 'confirmed_by' => 'integer', 'confirmed_at' => 'datetime'];
    }

    /** @return BelongsTo<AdmissionApplicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplicant::class, 'applicant_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
