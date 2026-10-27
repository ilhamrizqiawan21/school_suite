<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Domain\Enums\ScheduleStatusEnum;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Memeriksa benturan jadwal guru, rombel, dan ruangan.
     *
     * @return array{has_conflict: bool, errors: list<string>}
     */
    public function detectConflicts(
        int $termId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        int $teacherId,
        int $classGroupId,
        ?string $room = null,
        ?int $excludeScheduleId = null
    ): array {
        $startTime = strlen($startTime) === 5 ? $startTime.':00' : $startTime;
        $endTime = strlen($endTime) === 5 ? $endTime.':00' : $endTime;
        if ($startTime >= $endTime) {
            return [
                'has_conflict' => true,
                'errors' => ['Waktu mulai jadwal harus lebih awal dari waktu selesai.'],
            ];
        }

        $errors = [];

        // Query dasar pencocokan waktu overlap: (start_time < $endTime) AND (end_time > $startTime)
        $overlapQuery = function ($query) use ($startTime, $endTime) {
            $query->whereTime('start_time', '<', $endTime)
                ->whereTime('end_time', '>', $startTime);
        };

        // 1. Benturan Guru
        $teacherConflict = Schedule::where('term_id', $termId)
            ->where('day_of_week', $dayOfWeek)
            ->where('teacher_id', $teacherId)
            ->when($excludeScheduleId, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
            ->where($overlapQuery)
            ->with(['classGroup', 'subject', 'teacher'])
            ->first();

        if ($teacherConflict !== null) {
            $teacherName = $teacherConflict->teacher->name;
            $className = $teacherConflict->classGroup->name;
            $timeSlot = "{$teacherConflict->start_time} - {$teacherConflict->end_time}";
            $errors[] = "Benturan Guru: Guru [{$teacherName}] sudah dijadwalkan mengajar di kelas [{$className}] pada slot ({$timeSlot}).";
        }

        // 2. Benturan Rombongan Belajar (Kelas)
        $classConflict = Schedule::where('term_id', $termId)
            ->where('day_of_week', $dayOfWeek)
            ->where('class_group_id', $classGroupId)
            ->when($excludeScheduleId, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
            ->where($overlapQuery)
            ->with(['subject', 'classGroup'])
            ->first();

        if ($classConflict !== null) {
            $className = $classConflict->classGroup->name;
            $subjectName = $classConflict->subject->name;
            $timeSlot = "{$classConflict->start_time} - {$classConflict->end_time}";
            $errors[] = "Benturan Rombel: Kelas [{$className}] sudah memiliki jadwal mata pelajaran [{$subjectName}] pada slot ({$timeSlot}).";
        }

        // 3. Benturan Ruangan (jika ruangan ditentukan)
        if ($room !== null && trim($room) !== '') {
            $trimmedRoom = trim($room);
            $roomConflict = Schedule::where('term_id', $termId)
                ->where('day_of_week', $dayOfWeek)
                ->whereRaw('LOWER(TRIM(room)) = ?', [mb_strtolower($trimmedRoom)])
                ->when($excludeScheduleId, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
                ->where($overlapQuery)
                ->with(['classGroup', 'subject'])
                ->first();

            if ($roomConflict !== null) {
                $className = $roomConflict->classGroup->name;
                $timeSlot = "{$roomConflict->start_time} - {$roomConflict->end_time}";
                $errors[] = "Benturan Ruangan: Ruang [{$trimmedRoom}] sudah digunakan oleh kelas [{$className}] pada slot ({$timeSlot}).";
            }
        }

        return [
            'has_conflict' => ! empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Membuat jadwal baru setelah validasi benturan lolos.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSchedule(array $data, ?User $actor = null): Schedule
    {
        return DB::transaction(function () use ($data, $actor) {
            Term::whereKey($data['term_id'])->lockForUpdate()->firstOrFail();
            $this->validateContext($data, $actor);
            $data['room'] = isset($data['room']) && trim($data['room']) !== '' ? trim($data['room']) : null;

            return $this->createWithinTransaction($data, $actor);
        });
    }

    private function validateContext(array $data, ?User $actor): void
    {
        $term = Term::with('academicYear')->findOrFail($data['term_id']);
        $class = ClassGroup::findOrFail($data['class_group_id']);
        $teacher = Teacher::findOrFail($data['teacher_id']);
        $subject = Subject::findOrFail($data['subject_id']);
        if ((int) $term->academicYear->school_id !== $class->school_id || $teacher->school_id !== $class->school_id
            || (int) $subject->school_id !== $class->school_id || ($actor && (int) $actor->school_id !== $class->school_id)) {
            throw new InvalidArgumentException('Semester, guru, mapel, dan rombel harus berasal dari sekolah yang sama.');
        }
        if ($class->academic_year_id !== (int) $term->academic_year_id) {
            throw new InvalidArgumentException('Rombel dan semester harus berasal dari tahun ajaran yang sama.');
        }
        if (! $teacher->is_active || ! $subject->is_active) {
            throw new InvalidArgumentException('Guru dan mata pelajaran harus aktif.');
        }
    }

    private function createWithinTransaction(array $data, ?User $actor = null): Schedule
    {
        $termId = (int) $data['term_id'];
        $dayOfWeek = (int) $data['day_of_week'];
        $startTime = (string) $data['start_time'];
        $endTime = (string) $data['end_time'];
        $teacherId = (int) $data['teacher_id'];
        $classGroupId = (int) $data['class_group_id'];
        $room = isset($data['room']) ? (string) $data['room'] : null;

        $conflicts = $this->detectConflicts(
            termId: $termId,
            dayOfWeek: $dayOfWeek,
            startTime: $startTime,
            endTime: $endTime,
            teacherId: $teacherId,
            classGroupId: $classGroupId,
            room: $room
        );

        if ($conflicts['has_conflict']) {
            throw new InvalidArgumentException(implode(' ', $conflicts['errors']));
        }

        $teacher = Teacher::findOrFail($teacherId);
        $subject = Subject::findOrFail((int) $data['subject_id']);
        $classGroup = ClassGroup::findOrFail($classGroupId);

        if ($teacher->school_id !== $classGroup->school_id || $subject->school_id !== $classGroup->school_id) {
            throw new InvalidArgumentException('Guru, mapel, dan rombel harus berada di sekolah yang sama.');
        }

        $data['school_id'] = $classGroup->school_id;
        $data['status'] = $data['status'] ?? ScheduleStatusEnum::DRAFT->value;

        $schedule = Schedule::create($data);

        $this->auditLogger->log(
            eventType: 'academic.schedule.created',
            targetType: 'schedule',
            targetId: (string) $schedule->id,
            newValues: $schedule->toArray(),
            oldValues: null,
            actor: $actor,
            schoolId: $classGroup->school_id
        );

        return $schedule;
    }

    /**
     * Memperbarui jadwal pelajaran dengan deteksi benturan.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSchedule(Schedule $schedule, array $data, ?User $actor = null): Schedule
    {
        return DB::transaction(function () use ($schedule, $data, $actor) {
            Term::whereKey($schedule->term_id)->lockForUpdate()->firstOrFail();
            $schedule = Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($actor && (int) $actor->school_id !== $schedule->school_id) {
                throw new InvalidArgumentException('Jadwal tidak berasal dari sekolah ini.');
            }
            if (isset($data['term_id']) && (int) $data['term_id'] !== $schedule->term_id) {
                throw new InvalidArgumentException('Semester jadwal tidak dapat diubah. Buat slot baru untuk semester lain.');
            }
            if ($schedule->lessonSessions()->exists() || $schedule->substituteAssignments()->exists()) {
                throw new InvalidArgumentException('Jadwal sudah memiliki pertemuan atau penugasan guru pengganti dan tidak dapat diubah.');
            }
            $this->validateContext(array_merge($schedule->toArray(), $data), $actor);
            if (array_key_exists('room', $data)) {
                $data['room'] = $data['room'] !== null && trim($data['room']) !== '' ? trim($data['room']) : null;
            }
            $data['status'] = ScheduleStatusEnum::DRAFT->value;

            return $this->updateWithinTransaction($schedule, $data, $actor);
        });
    }

    private function updateWithinTransaction(Schedule $schedule, array $data, ?User $actor = null): Schedule
    {
        $termId = (int) ($data['term_id'] ?? $schedule->term_id);
        $dayOfWeek = (int) ($data['day_of_week'] ?? $schedule->day_of_week);
        $startTime = (string) ($data['start_time'] ?? $schedule->start_time);
        $endTime = (string) ($data['end_time'] ?? $schedule->end_time);
        $teacherId = (int) ($data['teacher_id'] ?? $schedule->teacher_id);
        $classGroupId = (int) ($data['class_group_id'] ?? $schedule->class_group_id);
        $room = array_key_exists('room', $data) ? $data['room'] : $schedule->room;

        $conflicts = $this->detectConflicts(
            termId: $termId,
            dayOfWeek: $dayOfWeek,
            startTime: $startTime,
            endTime: $endTime,
            teacherId: $teacherId,
            classGroupId: $classGroupId,
            room: $room,
            excludeScheduleId: $schedule->id
        );

        if ($conflicts['has_conflict']) {
            throw new InvalidArgumentException(implode(' ', $conflicts['errors']));
        }

        $oldValues = $schedule->toArray();
        $schedule->update($data);

        $this->auditLogger->log(
            eventType: 'academic.schedule.updated',
            targetType: 'schedule',
            targetId: (string) $schedule->id,
            newValues: $schedule->toArray(),
            oldValues: $oldValues,
            actor: $actor,
            schoolId: $schedule->school_id
        );

        return $schedule;
    }

    /**
     * Menerbitkan seluruh jadwal berstatus draf pada semester tertentu.
     */
    public function publishSchedules(int $termId, ?User $actor = null): int
    {
        return DB::transaction(function () use ($termId, $actor) {
            $term = Term::with('academicYear')->whereKey($termId)->lockForUpdate()->firstOrFail();
            if ($actor && (int) $actor->school_id !== (int) $term->academicYear->school_id) {
                throw new InvalidArgumentException('Semester tidak berasal dari sekolah ini.');
            }
            foreach (Schedule::where('term_id', $term->id)->where('status', ScheduleStatusEnum::DRAFT->value)->get() as $draft) {
                $this->validateContext($draft->toArray(), $actor);
                $conflicts = $this->detectConflicts($draft->term_id, $draft->day_of_week, $draft->start_time, $draft->end_time, $draft->teacher_id, $draft->class_group_id, $draft->room, $draft->id);
                if ($conflicts['has_conflict']) {
                    throw new InvalidArgumentException(implode(' ', $conflicts['errors']));
                }
            }
            $draftCount = Schedule::where('term_id', $term->id)
                ->where('status', ScheduleStatusEnum::DRAFT->value)
                ->count();

            if ($draftCount === 0) {
                return 0;
            }

            Schedule::where('term_id', $term->id)
                ->where('status', ScheduleStatusEnum::DRAFT->value)
                ->update(['status' => ScheduleStatusEnum::PUBLISHED->value]);

            $this->auditLogger->log(
                eventType: 'academic.schedule.published',
                targetType: 'term_schedule',
                targetId: (string) $term->id,
                newValues: ['published_count' => $draftCount],
                oldValues: ['status' => 'draft'],
                actor: $actor,
                schoolId: $term->academicYear->school_id
            );

            return $draftCount;
        });
    }

    /**
     * Menghapus jadwal pelajaran.
     */
    public function deleteSchedule(Schedule $schedule, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($schedule, $actor) {
            Term::whereKey($schedule->term_id)->lockForUpdate()->firstOrFail();
            $schedule = Schedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($actor && (int) $actor->school_id !== $schedule->school_id) {
                throw new InvalidArgumentException('Jadwal tidak berasal dari sekolah ini.');
            }
            if ($schedule->lessonSessions()->exists() || $schedule->substituteAssignments()->exists()) {
                throw new InvalidArgumentException('Jadwal sudah memiliki pertemuan atau penugasan guru pengganti dan tidak dapat dihapus.');
            }

            return $this->deleteWithinTransaction($schedule, $actor);
        });
    }

    private function deleteWithinTransaction(Schedule $schedule, ?User $actor = null): bool
    {
        $oldValues = $schedule->toArray();
        $id = $schedule->id;
        $schoolId = $schedule->school_id;

        $deleted = (bool) $schedule->delete();

        if ($deleted) {
            $this->auditLogger->log(
                eventType: 'academic.schedule.deleted',
                targetType: 'schedule',
                targetId: (string) $id,
                newValues: null,
                oldValues: $oldValues,
                actor: $actor,
                schoolId: $schoolId
            );
        }

        return $deleted;
    }
}
