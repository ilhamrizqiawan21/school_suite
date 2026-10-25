<?php

declare(strict_types=1);

namespace App\Modules\Learning\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningMaterial extends Model
{
    protected $fillable = ['school_id', 'teaching_assignment_id', 'created_by', 'title', 'description', 'file_path', 'original_filename', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo('App\\Modules\\Academic\\Infrastructure\\Models\\TeachingAssignment');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(LearningMaterialRead::class);
    }
}
