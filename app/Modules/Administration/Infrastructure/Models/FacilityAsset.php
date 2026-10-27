<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityAsset extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'inventory_code', 'name', 'category', 'location', 'condition', 'status', 'notes',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(FacilityLoan::class, 'asset_id');
    }

    public function activeLoan(): HasMany
    {
        return $this->loans()->whereNull('returned_at');
    }
}
