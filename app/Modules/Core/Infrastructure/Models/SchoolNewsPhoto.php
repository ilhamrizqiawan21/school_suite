<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolNewsPhoto extends Model
{
    protected $table = 'school_news_photos';

    protected $fillable = ['school_news_id', 'path', 'original_name', 'caption', 'sort_order'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(SchoolNews::class, 'school_news_id');
    }
}
