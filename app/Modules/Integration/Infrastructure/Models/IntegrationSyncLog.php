<?php

declare(strict_types=1);

namespace App\Modules\Integration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationSyncLog extends Model
{
    protected $fillable = [
        'school_id',
        'system_key',
        'direction',
        'batch_code',
        'status',
        'total_rows',
        'success_rows',
        'failed_rows',
        'failed_details_json',
        'payload_snapshot_json',
        'idempotency_key',
        'triggered_by_user_id',
        'retried_from_log_id',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'success_rows' => 'integer',
            'failed_rows' => 'integer',
            'failed_details_json' => 'array',
            'payload_snapshot_json' => 'array',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    public function retriedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retried_from_log_id');
    }
}
