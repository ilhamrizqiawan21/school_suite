<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingDocument extends Model
{
    protected $table = 'teaching_documents';

    protected $fillable = ['school_id', 'teacher_id', 'term_id', 'plan_id', 'type', 'title', 'semester', 'content'];

    protected $casts = ['school_id' => 'integer', 'teacher_id' => 'integer', 'term_id' => 'integer', 'plan_id' => 'integer', 'content' => 'array'];

    public function plan()
    {
        return $this->belongsTo(TeachingPlan::class, 'plan_id');
    }
}
