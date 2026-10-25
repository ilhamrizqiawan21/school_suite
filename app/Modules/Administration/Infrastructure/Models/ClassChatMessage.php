<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassChatMessage extends Model
{
    protected $fillable = ['class_chat_room_id', 'user_id', 'body', 'moderated_by', 'moderated_at', 'moderation_reason'];

    protected function casts(): array
    {
        return ['moderated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ClassChatRoom::class, 'class_chat_room_id');
    }
}
