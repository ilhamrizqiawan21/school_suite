<?php

declare(strict_types=1);

namespace App\Modules\Library\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryVisit extends Model
{
    protected $table = 'library_visits';

    protected $fillable = [
        'school_id',
        'student_id',
        'user_id',
        'visitor_name',
        'visitor_identifier',
        'visitor_role',
        'check_in_method',
        'purpose',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'student_id' => 'integer',
            'user_id' => 'integer',
            'visited_at' => 'datetime',
        ];
    }
}
