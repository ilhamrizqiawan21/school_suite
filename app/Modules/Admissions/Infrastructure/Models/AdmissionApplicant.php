<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplicant extends Model
{
    protected $fillable = [
        'school_id', 'registration_no', 'name', 'nisn', 'gender', 'birth_date',
        'parent_name', 'parent_phone', 'previous_school', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'school_id' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
