<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionPack extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'name', 'description', 'jenjang', 'curriculum', 'duration_minutes',
        'randomize_questions', 'randomize_options', 'status',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuestionPackItem::class)->orderBy('sequence');
    }
}
