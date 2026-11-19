<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicDownload extends Model
{
    protected $table = 'public_downloads';

    protected $fillable = [
        'school_id',
        'category',
        'title',
        'description',
        'version',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'is_published',
        'download_count',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size_bytes' => 'integer',
            'is_published' => 'boolean',
            'download_count' => 'integer',
        ];
    }

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
