<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionShareEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_share_id', 'actor_id', 'event_type', 'old_permission', 'new_permission',
        'old_status', 'new_status', 'note', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(QuestionShare::class, 'question_share_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
