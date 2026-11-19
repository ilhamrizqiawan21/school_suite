<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolNews extends Model
{
    protected $table = 'school_news';

    protected $fillable = [
        'school_id',
        'author_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'body',
        'video_url',
        'source_url',
        'status',
        'is_archived',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<SchoolNewsPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(SchoolNewsPhoto::class)->orderBy('sort_order')->orderBy('id');
    }
}
