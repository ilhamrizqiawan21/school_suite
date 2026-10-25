<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttitude extends Model
{
    protected $fillable = ['school_id', 'term_id', 'enrollment_id', 'type', 'predicate', 'description', 'recorded_by'];
}
