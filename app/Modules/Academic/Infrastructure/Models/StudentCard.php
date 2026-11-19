<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCard extends Model
{
    protected $table = 'student_cards';

    protected $fillable = [
        'school_id',
        'student_id',
        'card_uid',
        'qr_token',
        'status',
        'issued_at',
        'revoked_at',
        'revocation_reason',
        'issued_by_user_id',
        'revoked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'issued_by_user_id' => 'integer',
            'revoked_by_user_id' => 'integer',
            'issued_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
