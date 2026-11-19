<?php

declare(strict_types=1);

namespace App\Modules\Academic\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceKioskScan extends Model
{
    protected $table = 'attendance_kiosk_scans';

    protected $fillable = [
        'school_id',
        'kiosk_device_id',
        'student_id',
        'student_card_id',
        'scan_method',
        'scan_result',
        'client_event_id',
        'scanned_at',
        'recorded_by_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'kiosk_device_id' => 'integer',
            'student_id' => 'integer',
            'student_card_id' => 'integer',
            'recorded_by_user_id' => 'integer',
            'scanned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
