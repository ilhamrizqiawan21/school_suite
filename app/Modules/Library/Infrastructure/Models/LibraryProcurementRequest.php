<?php

declare(strict_types=1);

namespace App\Modules\Library\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryProcurementRequest extends Model
{
    protected $table = 'library_procurement_requests';

    protected $fillable = [
        'school_id',
        'requester_user_id',
        'requester_name',
        'title',
        'author',
        'publisher',
        'isbn',
        'reason',
        'estimated_copies',
        'status',
        'decision_note',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'requester_user_id' => 'integer',
            'estimated_copies' => 'integer',
            'decided_by' => 'integer',
            'decided_at' => 'datetime',
        ];
    }
}
