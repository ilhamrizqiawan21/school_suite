<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemErrorEvent extends Model
{
    protected $fillable = ['school_id', 'fingerprint', 'exception_class', 'request_method', 'request_path', 'user_id', 'occurrences', 'last_seen_at', 'resolved_at', 'resolved_by'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
