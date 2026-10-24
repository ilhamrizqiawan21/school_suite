<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Domain\Enums\AttendanceTypeEnum;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Mencatat presensi siswa secara massal untuk sesi pertemuan pelajaran tertentu.
     * Mendeteksi koreksi otomatis jika data sudah ada sebelumnya.
     *
     * @param  list<array{enrollment_id: int, status: string, notes?: ?string}>  $records
     * @return array{
     *     recorded_count: int,
     *     corrected_count: int,
     *     summary: array{present: int, sick: int, permitted: int, absent: int}
     * }
     */
    public function recordSessionAttendance(
        int $lessonSessionId,
        array $records,
        ?User $actor = null
    ): array {
        $session = LessonSession::findOrFail($lessonSessionId);

        return DB::transaction(function () use ($session, $records, $actor) {
            $recordedCount = 0;
            $correctedCount = 0;
            $summary = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];

            foreach ($records as $entry) {
                $enrollmentId = (int) $entry['enrollment_id'];
                $status = (string) $entry['status'];
                $notes = $entry['notes'] ?? null;

                if (! in_array($status, ['present', 'sick', 'permitted', 'absent'], true)) {
                    throw new InvalidArgumentException("Status presensi [{$status}] tidak valid.");
                }

                $summary[$status]++;

                $existing = AttendanceRecord::where('lesson_session_id', $session->id)
                    ->where('enrollment_id', $enrollmentId)
                    ->first();

                if ($existing !== null) {
                    // Cek apakah terjadi perubahan status / koreksi
                    if ($existing->status !== $status || $existing->notes !== $notes) {
                        $oldValues = [
                            'status' => $existing->status,
                            'notes' => $existing->notes,
                        ];

                        $existing->update([
                            'status' => $status,
                            'notes' => $notes,
                            'recorded_by_user_id' => $actor?->id,
                        ]);

                        $this->auditLogger->log(
                            eventType: 'academic.attendance.corrected',
                            targetType: 'attendance_record',
                            targetId: (string) $existing->id,
                            newValues: [
                                'status' => $status,
                                'notes' => $notes,
                                'lesson_session_id' => $session->id,
                            ],
                            oldValues: $oldValues,
                            actor: $actor,
                            schoolId: $session->school_id
                        );

                        $correctedCount++;
                    }
                } else {
                    $created = AttendanceRecord::create([
                        'school_id' => $session->school_id,
                        'enrollment_id' => $enrollmentId,
                        'lesson_session_id' => $session->id,
                        'date' => $session->date,
                        'type' => AttendanceTypeEnum::SESSION->value,
                        'status' => $status,
                        'notes' => $notes,
                        'recorded_by_user_id' => $actor?->id,
                    ]);

                    $this->auditLogger->log(
                        eventType: 'academic.attendance.recorded',
                        targetType: 'attendance_record',
                        targetId: (string) $created->id,
                        newValues: [
                            'status' => $status,
                            'lesson_session_id' => $session->id,
                        ],
                        oldValues: null,
                        actor: $actor,
                        schoolId: $session->school_id
                    );

                    $recordedCount++;
                }
            }

            return [
                'recorded_count' => $recordedCount,
                'corrected_count' => $correctedCount,
                'summary' => $summary,
            ];
        });
    }

    /**
     * Mengoreksi secara manual satu rekaman absensi dengan alasan koreksi (SS-F-005).
     */
    public function correctAttendanceRecord(
        int $attendanceRecordId,
        string $newStatus,
        ?string $reason = null,
        ?User $actor = null
    ): AttendanceRecord {
        if (! in_array($newStatus, ['present', 'sick', 'permitted', 'absent'], true)) {
            throw new InvalidArgumentException("Status presensi baru [{$newStatus}] tidak valid.");
        }

        $record = AttendanceRecord::findOrFail($attendanceRecordId);

        $oldValues = [
            'status' => $record->status,
            'notes' => $record->notes,
        ];

        $notes = $reason ? "[Koreksi: {$reason}] ".($record->notes ?? '') : $record->notes;

        $record->update([
            'status' => $newStatus,
            'notes' => $notes,
            'recorded_by_user_id' => $actor?->id,
        ]);

        $this->auditLogger->log(
            eventType: 'academic.attendance.corrected',
            targetType: 'attendance_record',
            targetId: (string) $record->id,
            newValues: [
                'status' => $newStatus,
                'notes' => $notes,
                'reason' => $reason,
            ],
            oldValues: $oldValues,
            actor: $actor,
            schoolId: $record->school_id
        );

        return $record;
    }
}
