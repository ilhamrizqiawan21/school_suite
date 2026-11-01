<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolGalleryPhoto extends Model
{
    protected $table = 'school_gallery_photos';

    protected $fillable = [
        'school_id',
        'school_gallery_album_id',
        'path',
        'original_name',
        'title',
        'caption',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(SchoolGalleryAlbum::class, 'school_gallery_album_id');
    }
}
