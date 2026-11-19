<?php

declare(strict_types=1);

namespace App\Modules\Library\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoan extends Model
{
    protected $table = 'library_loans';

    protected $fillable = [
        'school_id',
        'collection_id',
        'student_id',
        'borrower_user_id',
        'borrower_name',
        'borrower_identifier',
        'loan_code',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'collection_id' => 'integer',
            'student_id' => 'integer',
            'borrower_user_id' => 'integer',
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
            'recorded_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LibraryCollection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(LibraryCollection::class, 'collection_id');
    }
}
