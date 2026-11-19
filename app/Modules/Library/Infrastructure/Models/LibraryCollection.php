<?php

declare(strict_types=1);

namespace App\Modules\Library\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryCollection extends Model
{
    protected $table = 'library_collections';

    protected $fillable = [
        'school_id',
        'code',
        'title',
        'author',
        'publisher',
        'publish_year',
        'category',
        'format',
        'total_copies',
        'available_copies',
        'shelf_location',
        'description',
        'ebook_url',
        'ebook_document_id',
        'access_level',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'publish_year' => 'integer',
            'total_copies' => 'integer',
            'available_copies' => 'integer',
            'ebook_document_id' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ];
    }
}
