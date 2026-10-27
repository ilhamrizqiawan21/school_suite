<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityLoan extends Model
{
    protected $fillable = [
        'school_id', 'asset_id', 'recorded_by', 'borrower_name', 'borrower_reference',
        'borrowed_at', 'due_at', 'returned_at', 'return_condition', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FacilityAsset::class, 'asset_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
