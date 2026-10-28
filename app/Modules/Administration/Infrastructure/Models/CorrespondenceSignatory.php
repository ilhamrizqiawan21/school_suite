<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrespondenceSignatory extends Model
{
    protected $fillable = ['school_id', 'user_id', 'name', 'title', 'employee_number', 'rank', 'template_scopes', 'is_active'];

    protected function casts(): array
    {
        return ['school_id' => 'integer', 'user_id' => 'integer', 'template_scopes' => 'array', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signatureRequests(): HasMany
    {
        return $this->hasMany(CorrespondenceSignatureRequest::class, 'signatory_id');
    }
}
