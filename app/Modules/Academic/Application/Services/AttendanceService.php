<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Domain\Enums\AttendanceTypeEnum;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
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

    /**
     * Apply an approved student leave/dispensation to daily (and existing absent session) attendance records (M4-OPS-02).
     */
    public function applyApprovedDispensationToAttendance(
        int $schoolId,
        int $studentId,
        int $termId,
        string $startDate,
        string $endDate,
        string $attendanceStatus,
        string $title,
        ?User $actor = null,
    ): int {
        if (! in_array($attendanceStatus, ['sick', 'permitted'], true)) {
            throw new InvalidArgumentException("Status presensi izin [{$attendanceStatus}] tidak valid.");
        }

        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->first();

        if ($enrollment === null) {
            return 0;
        }

        return DB::transaction(function () use ($schoolId, $enrollment, $startDate, $endDate, $attendanceStatus, $title, $actor): int {
            $cursor = CarbonImmutable::parse($startDate)->startOfDay();
            $end = CarbonImmutable::parse($endDate)->startOfDay();
            $affected = 0;
            $noteTag = sprintf('[Izin Disetujui] %s', mb_substr(trim($title), 0, 180));

            while ($cursor->lte($end)) {
                $dateStr = $cursor->toDateString();

                $daily = AttendanceRecord::query()
                    ->where('school_id', $schoolId)
                    ->where('enrollment_id', $enrollment->id)
                    ->whereDate('date', $dateStr)
                    ->where('type', AttendanceTypeEnum::DAILY->value)
                    ->first();

                if ($daily !== null) {
                    $old = ['status' => $daily->status, 'notes' => $daily->notes];
                    $daily->update([
                        'status' => $attendanceStatus,
                        'notes' => $noteTag,
                        'recorded_by_user_id' => $actor?->id,
                    ]);
                    $this->auditLogger->log(
                        eventType: 'academic.attendance.dispensation_applied',
                        targetType: 'attendance_record',
                        targetId: (string) $daily->id,
                        newValues: ['status' => $attendanceStatus, 'date' => $dateStr],
                        oldValues: $old,
                        actor: $actor,
                        schoolId: $schoolId,
                    );
                } else {
                    $created = AttendanceRecord::query()->create([
                        'school_id' => $schoolId,
                        'enrollment_id' => $enrollment->id,
                        'lesson_session_id' => null,
                        'date' => $dateStr,
                        'type' => AttendanceTypeEnum::DAILY->value,
                        'status' => $attendanceStatus,
                        'notes' => $noteTag,
                        'recorded_by_user_id' => $actor?->id,
                    ]);
                    $this->auditLogger->log(
                        eventType: 'academic.attendance.dispensation_applied',
                        targetType: 'attendance_record',
                        targetId: (string) $created->id,
                        newValues: ['status' => $attendanceStatus, 'date' => $dateStr],
                        oldValues: null,
                        actor: $actor,
                        schoolId: $schoolId,
                    );
                }

                // Update existing session attendance rows marked absent on the same date
                $sessionRows = AttendanceRecord::query()
                    ->where('school_id', $schoolId)
                    ->where('enrollment_id', $enrollment->id)
                    ->whereDate('date', $dateStr)
                    ->where('type', AttendanceTypeEnum::SESSION->value)
                    ->where('status', 'absent')
                    ->get();

                foreach ($sessionRows as $row) {
                    $row->update([
                        'status' => $attendanceStatus,
                        'notes' => $noteTag,
                        'recorded_by_user_id' => $actor?->id,
                    ]);
                }

                $affected++;
                $cursor = $cursor->addDay();
            }

            return $affected;
        });
    }

    /**
     * Revert attendance impact when an approved dispensation is corrected/cancelled with audit trail (M4-OPS-02).
     */
    public function revertDispensationAttendance(
        int $schoolId,
        int $studentId,
        int $termId,
        string $startDate,
        string $endDate,
        string $reason,
        ?User $actor = null,
    ): int {
        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->first();

        if ($enrollment === null) {
            return 0;
        }

        return DB::transaction(function () use ($schoolId, $enrollment, $startDate, $endDate, $reason, $actor): int {
            $records = AttendanceRecord::query()
                ->where('school_id', $schoolId)
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->where('notes', 'like', '[Izin Disetujui]%')
                ->get();

            $reverted = 0;
            foreach ($records as $record) {
                $old = ['status' => $record->status, 'notes' => $record->notes];
                $record->update([
                    'status' => 'absent',
                    'notes' => sprintf('[Koreksi Izin: %s]', mb_substr(trim($reason), 0, 180)),
                    'recorded_by_user_id' => $actor?->id,
                ]);
                $this->auditLogger->log(
                    eventType: 'academic.attendance.dispensation_reverted',
                    targetType: 'attendance_record',
                    targetId: (string) $record->id,
                    newValues: ['status' => 'absent', 'reason' => $reason],
                    oldValues: $old,
                    actor: $actor,
                    schoolId: $schoolId,
                );
                $reverted++;
            }

            return $reverted;
        });
    }

    /**
     * Record a daily present entry from an authorized RFID/QR kiosk scan without creating duplicates (M4-OPS-03).
     */
    public function recordDailyPresenceFromKiosk(
        int $schoolId,
        int $studentId,
        int $termId,
        string $date,
        string $method,
        ?User $actor = null,
    ): ?AttendanceRecord {
        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->first();

        if ($enrollment === null) {
            return null;
        }

        $existing = AttendanceRecord::query()
            ->where('school_id', $schoolId)
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('date', $date)
            ->where('type', AttendanceTypeEnum::DAILY->value)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $created = AttendanceRecord::query()->create([
            'school_id' => $schoolId,
            'enrollment_id' => $enrollment->id,
            'lesson_session_id' => null,
            'date' => $date,
            'type' => AttendanceTypeEnum::DAILY->value,
            'status' => 'present',
            'notes' => sprintf('[Kios %s]', strtoupper($method)),
            'recorded_by_user_id' => $actor?->id,
        ]);

        $this->auditLogger->log(
            eventType: 'academic.attendance.kiosk_recorded',
            targetType: 'attendance_record',
            targetId: (string) $created->id,
            newValues: ['status' => 'present', 'date' => $date, 'method' => $method],
            oldValues: null,
            actor: $actor,
            schoolId: $schoolId,
        );

        return $created;
    }
}
