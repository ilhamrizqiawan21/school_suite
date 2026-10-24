<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\TeachingJournal;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Menyimpan jurnal mengajar guru dan menutup sesi pertemuan pembelajaran.
     */
    public function saveJournal(
        int $lessonSessionId,
        string $topic,
        ?string $learningObjectives = null,
        ?string $notes = null,
        ?User $actor = null
    ): TeachingJournal {
        $session = LessonSession::findOrFail($lessonSessionId);

        return DB::transaction(function () use ($session, $topic, $learningObjectives, $notes, $actor) {
            // Hitung ringkasan presensi dari attendance_records
            $summary = [
                'present' => AttendanceRecord::where('lesson_session_id', $session->id)->where('status', 'present')->count(),
                'sick' => AttendanceRecord::where('lesson_session_id', $session->id)->where('status', 'sick')->count(),
                'permitted' => AttendanceRecord::where('lesson_session_id', $session->id)->where('status', 'permitted')->count(),
                'absent' => AttendanceRecord::where('lesson_session_id', $session->id)->where('status', 'absent')->count(),
            ];

            $journal = TeachingJournal::updateOrCreate(
                ['lesson_session_id' => $session->id],
                [
                    'topic' => $topic,
                    'learning_objectives' => $learningObjectives,
                    'notes' => $notes,
                    'attendance_summary' => $summary,
                ]
            );

            // Tandai sesi selesai
            $session->update(['status' => 'completed']);

            $this->auditLogger->log(
                eventType: 'academic.journal.saved',
                targetType: 'teaching_journal',
                targetId: (string) $journal->id,
                newValues: [
                    'topic' => $topic,
                    'lesson_session_id' => $session->id,
                    'attendance_summary' => $summary,
                ],
                oldValues: null,
                actor: $actor,
                schoolId: $session->school_id
            );

            return $journal;
        });
    }
}
