<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Teacher;
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
        ?string $theme = null,
        int $periodCount = 2,
        ?string $openingActivity = null,
        ?string $mainActivity = null,
        ?string $closingActivity = null,
        ?string $followUp = null,
        ?string $attendanceNotes = null,
        ?string $observations = null,
        ?string $reflection = null,
        ?User $actor = null
    ): TeachingJournal {
        $session = LessonSession::findOrFail($lessonSessionId);

        if ($actor) {
            $teacherId = Teacher::query()
                ->where('school_id', $actor->school_id)
                ->where('user_id', $actor->id)
                ->value('id');

            abort_unless($teacherId && (int) $session->teacher_id === (int) $teacherId, 403);
        }

        return DB::transaction(function () use ($session, $topic, $learningObjectives, $notes, $theme, $periodCount, $openingActivity, $mainActivity, $closingActivity, $followUp, $attendanceNotes, $observations, $reflection, $actor) {
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
                    'theme' => $theme,
                    'period_count' => $periodCount,
                    'learning_objectives' => $learningObjectives,
                    'opening_activity' => $openingActivity,
                    'main_activity' => $mainActivity,
                    'closing_activity' => $closingActivity,
                    'notes' => $notes,
                    'follow_up' => $followUp,
                    'attendance_notes' => $attendanceNotes,
                    'observations' => $observations,
                    'reflection' => $reflection,
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
                    'theme' => $theme,
                    'lesson_session_id' => $session->id,
                    'period_count' => $periodCount,
                    'attendance_summary' => $summary,
                ],
                oldValues: null,
                actor: $actor,
                schoolId: $session->school_id
            );

            return $journal;
        });
    }

    public function deleteJournal(LessonSession $session, User $actor): void
    {
        $teacherId = Teacher::query()
            ->where('school_id', $actor->school_id)
            ->where('user_id', $actor->id)
            ->value('id');

        abort_unless($teacherId && (int) $session->teacher_id === (int) $teacherId, 403);

        DB::transaction(function () use ($session, $actor): void {
            $journal = TeachingJournal::query()
                ->where('lesson_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if (! $journal) {
                return;
            }

            $oldValues = [
                'topic' => $journal->topic,
                'lesson_session_id' => $session->id,
            ];
            $journal->delete();
            $session->update(['status' => 'scheduled']);

            $this->auditLogger->log(
                eventType: 'academic.journal.deleted',
                targetType: 'teaching_journal',
                targetId: (string) $journal->id,
                newValues: null,
                oldValues: $oldValues,
                actor: $actor,
                schoolId: $session->school_id
            );
        });
    }
}
