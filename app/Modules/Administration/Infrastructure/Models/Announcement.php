<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $fillable = ['school_id', 'author_id', 'class_group_id', 'audience_role', 'title', 'body', 'attachment_path', 'attachment_name', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo('App\\Modules\\Academic\\Infrastructure\\Models\\ClassGroup');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
