<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniProfile extends Model
{
    protected $table = 'alumni_profiles';

    protected $fillable = [
        'school_id',
        'student_id',
        'external_applicant_id',
        'user_id',
        'nis',
        'nisn',
        'full_name',
        'graduation_year',
        'major_or_class',
        'current_status',
        'institution_or_company',
        'position_or_program',
        'career_notes',
        'scholarship_info',
        'testimonial',
        'phone',
        'email',
        'proof_document_id',
        'diploma_serial',
        'activity_location',
        'residence_city',
        'linkedin_url',
        'instagram_handle',
        'photo_document_id',
        'opt_in_directory',
        'opt_in_contact',
        'opt_in_career',
        'opt_in_testimonial',
        'moderation_status',
        'moderation_note',
        'moderated_by_user_id',
        'moderated_at',
        'ekta_number',
        'ekta_document_id',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'external_applicant_id' => 'integer',
            'user_id' => 'integer',
            'graduation_year' => 'integer',
            'proof_document_id' => 'integer',
            'photo_document_id' => 'integer',
            'moderated_by_user_id' => 'integer',
            'ekta_document_id' => 'integer',
            'opt_in_directory' => 'boolean',
            'opt_in_contact' => 'boolean',
            'opt_in_career' => 'boolean',
            'opt_in_testimonial' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
