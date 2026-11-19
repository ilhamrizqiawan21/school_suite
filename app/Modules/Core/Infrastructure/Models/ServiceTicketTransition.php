<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicketTransition extends Model
{
    public $timestamps = false;

    protected $table = 'service_ticket_transitions';

    protected $fillable = [
        'school_id',
        'service_ticket_id',
        'actor_id',
        'actor_role',
        'from_status',
        'to_status',
        'public_status',
        'reason',
        'metadata',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
