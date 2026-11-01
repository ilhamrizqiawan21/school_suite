<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolGalleryAlbum extends Model
{
    protected $table = 'school_gallery_albums';

    protected $fillable = [
        'school_id',
        'title',
        'caption',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<SchoolGalleryPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(SchoolGalleryPhoto::class)->orderBy('sort_order')->orderBy('id');
    }
}
