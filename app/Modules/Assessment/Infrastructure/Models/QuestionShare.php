<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionShare extends Model
{
    protected $fillable = [
        'school_id', 'sender_id', 'recipient_id', 'resource_type', 'resource_id',
        'permission', 'status', 'active_key', 'request_note', 'decision_note', 'decided_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuestionShareEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function resource(): ExamQuestion|QuestionPack|null
    {
        return match ($this->resource_type) {
            'question' => ExamQuestion::query()->where('school_id', $this->school_id)->find($this->resource_id),
            'pack' => QuestionPack::query()->where('school_id', $this->school_id)->find($this->resource_id),
            default => null,
        };
    }
}
