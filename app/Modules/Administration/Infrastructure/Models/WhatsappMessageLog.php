<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessageLog extends Model
{
    protected $fillable = ['school_id', 'recipient_name', 'phone', 'template_key', 'message', 'status', 'prepared_by', 'prepared_at', 'sent_at'];

    protected function casts(): array
    {
        return ['prepared_at' => 'datetime', 'sent_at' => 'datetime'];
    }
}
