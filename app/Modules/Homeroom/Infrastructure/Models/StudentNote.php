<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNote extends Model
{
    use HasFactory;

    protected $table = 'student_notes';

    protected $fillable = [
        'school_id',
        'student_id',
        'term_id',
        'class_group_id',
        'author_id',
        'title',
        'content',
        'category',
        'access_level',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'term_id' => 'integer',
        'class_group_id' => 'integer',
        'author_id' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
