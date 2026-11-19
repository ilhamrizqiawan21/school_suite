<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPermitToken extends Model
{
    protected $table = 'student_permit_tokens';

    protected $fillable = [
        'school_id',
        'student_id',
        'purpose',
        'token_hash',
        'token_prefix',
        'expires_at',
        'used_at',
        'used_by_dispensation_id',
        'issued_by_user_id',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'used_by_dispensation_id' => 'integer',
            'issued_by_user_id' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
