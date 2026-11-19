<?php

declare(strict_types=1);

namespace App\Modules\Integration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    public const CATEGORY_ACADEMIC_SYNC = 'academic_sync';

    public const CATEGORY_EXTERNAL_CHANNEL = 'external_channel';

    protected $fillable = [
        'school_id',
        'system_key',
        'category',
        'mechanism',
        'is_enabled',
        'endpoint_url',
        'config_json',
        'secret_encrypted',
        'secret_last4',
        'last_health_status',
        'last_checked_at',
        'last_error',
        'updated_by_user_id',
    ];

    protected $hidden = [
        'secret_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config_json' => 'array',
            'secret_encrypted' => 'encrypted',
            'last_checked_at' => 'datetime',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
