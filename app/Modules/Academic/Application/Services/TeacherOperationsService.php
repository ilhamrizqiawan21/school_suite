<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeacherAttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\TeacherLeaveRequest;
use App\Modules\Academic\Infrastructure\Models\TeacherSubstituteAssignment;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherOperationsService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function teacherFor(User $actor): Teacher
    {
        $this->assertActive($actor);

        if (! $actor->hasRole(['teacher', 'homeroom'])) {
            throw new AuthorizationException('Akun guru tidak ditemukan.');
        }

        return Teacher::query()
            ->where('school_id', $actor->school_id)
            ->where('user_id', $actor->id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function auditRecapExport(User $actor, string $month, int $rowCount): void
    {
        $schoolId = $this->schoolId($actor);
        $this->audit->log(
            'academic.teacher_attendance_recap.exported',
            'teacher_attendance_recap',
            $month,
            ['month' => $month, 'teacher_count' => $rowCount],
            actor: $actor,
            schoolId: $schoolId,
        );
    }

    /** @param list<array{teacher_id: int, status: string, notes?: ?string}> $rows */
    public function saveAttendance(User $actor, string $date, array $rows): int
    {
        $schoolId = $this->schoolId($actor);
        $dateValue = CarbonImmutable::parse($date)->toDateString();

        if ($dateValue > today()->toDateString()) {
            throw ValidationException::withMessages(['date' => 'Absensi tidak dapat dicatat untuk tanggal mendatang.']);
        }

        $teacher = $actor->hasRole(['teacher', 'homeroom']) ? $this->teacherFor($actor) : null;
        if (! $actor->hasRole(['admin', 'operator']) && $teacher === null) {
            throw new AuthorizationException('Hanya admin, operator, atau guru yang dapat mengelola absensi guru.');
        }

        $teacherIds = collect($rows)->pluck('teacher_id')->map(fn ($id) => (int) $id)->unique();
        if ($teacher && ($teacherIds->count() !== 1 || (int) $teacherIds->first() !== (int) $teacher->id)) {
            throw new AuthorizationException('Guru hanya dapat mengisi absensinya sendiri.');
        }

        $validTeachers = Teacher::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereIn('id', $teacherIds)
            ->get(['id']);
        if ($validTeachers->count() !== $teacherIds->count()) {
            throw ValidationException::withMessages(['records' => 'Daftar guru berisi data tidak aktif atau dari sekolah lain.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $dateValue, $rows): int {
            $saved = 0;
            foreach ($rows as $row) {
                $record = TeacherAttendanceRecord::query()
                    ->where('school_id', $schoolId)
                    ->where('teacher_id', (int) $row['teacher_id'])
                    ->whereDate('date', $dateValue)
                    ->lockForUpdate()
                    ->first();
                $before = $record ? Arr::only($record->getAttributes(), ['status', 'notes']) : null;
                $values = [
                    'status' => $row['status'],
                    'notes' => $row['notes'] ?? null,
                    'recorded_by_user_id' => $actor->id,
                ];

                if ($record) {
                    if ($before === Arr::only($values, ['status', 'notes'])) {
                        continue;
                    }
                    $record->update($values);
                    $event = 'academic.teacher_attendance.corrected';
                } else {
                    $record = TeacherAttendanceRecord::create($values + [
                        'school_id' => $schoolId,
                        'teacher_id' => (int) $row['teacher_id'],
                        'date' => $dateValue,
                    ]);
                    $event = 'academic.teacher_attendance.recorded';
                }

                $this->audit->log(
                    $event,
                    'teacher_attendance_record',
                    (string) $record->id,
                    ['date' => $dateValue, 'teacher_id' => $record->teacher_id, 'status' => $record->status, 'notes' => $record->notes],
                    $before,
                    $actor,
                    $schoolId,
                );
                $saved++;
            }

            return $saved;
        });
    }

    /** @param array{type: string, start_date: string, end_date: string, reason: string} $data */
    public function submitLeave(User $actor, array $data): TeacherLeaveRequest
    {
        $schoolId = $this->schoolId($actor);
        $teacher = $this->teacherFor($actor);
        $start = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $end = CarbonImmutable::parse($data['end_date'])->startOfDay();

        if ($start->lt(today()->subDays(7)->startOfDay())) {
            throw ValidationException::withMessages(['start_date' => 'Pengajuan tidak dapat dimulai lebih dari 7 hari yang lalu.']);
        }
        if ($end->gt($start->addDays(TeacherLeaveRequest::MAX_DAYS - 1))) {
            throw ValidationException::withMessages(['end_date' => 'Rentang pengajuan maksimal 60 hari.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $teacher, $data, $start, $end): TeacherLeaveRequest {
            Teacher::query()->whereKey($teacher->id)->lockForUpdate()->firstOrFail();
            $overlap = TeacherLeaveRequest::query()
                ->where('school_id', $schoolId)
                ->where('teacher_id', $teacher->id)
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString())
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages(['start_date' => 'Tanggal bertumpuk dengan pengajuan yang masih menunggu atau sudah disetujui.']);
            }

            $request = TeacherLeaveRequest::create([
                'school_id' => $schoolId,
                'teacher_id' => $teacher->id,
                'type' => $data['type'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => $data['reason'],
                'status' => 'pending',
            ]);
            $this->audit->log(
                'academic.teacher_leave.submitted',
                'teacher_leave_request',
                (string) $request->id,
                Arr::only($request->getAttributes(), ['teacher_id', 'type', 'start_date', 'end_date', 'status']),
                actor: $actor,
                schoolId: $schoolId,
            );

            return $request;
        });
    }

    public function cancelLeave(TeacherLeaveRequest $leave, User $actor): void
    {
        $this->assertScoped($actor, (int) $leave->school_id);
        $teacher = $this->teacherFor($actor);
        abort_unless((int) $leave->teacher_id === (int) $teacher->id, 404);

        DB::transaction(function () use ($leave, $actor): void {
            $locked = TeacherLeaveRequest::query()
                ->where('school_id', $actor->school_id)
                ->whereKey($leave->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Pengajuan yang sudah diputuskan tidak dapat dibatalkan.']);
            }

            $this->audit->log(
                'academic.teacher_leave.cancelled',
                'teacher_leave_request',
                (string) $locked->id,
                ['status' => 'cancelled'],
                ['status' => $locked->status],
                $actor,
                (int) $locked->school_id,
            );
            $locked->delete();
        });
    }

    public function reviewLeave(TeacherLeaveRequest $leave, User $actor, string $decision, ?string $note): void
    {
        $schoolId = $this->schoolId($actor);
        if (! $actor->hasRole(['admin', 'operator'])) {
            throw new AuthorizationException('Hanya admin atau operator yang dapat memutuskan pengajuan.');
        }

        DB::transaction(function () use ($leave, $actor, $decision, $note, $schoolId): void {
            $locked = TeacherLeaveRequest::query()
                ->where('school_id', $schoolId)
                ->whereKey($leave->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diputuskan.']);
            }

            $status = $decision === 'approve' ? 'approved' : 'rejected';
            $before = ['status' => $locked->status, 'review_note' => $locked->review_note];
            $locked->update([
                'status' => $status,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
            if ($status === 'approved') {
                $this->fillApprovedLeaveAttendance($locked, $actor);
            }
            $this->audit->log(
                'academic.teacher_leave.reviewed',
                'teacher_leave_request',
                (string) $locked->id,
                ['status' => $status, 'review_note' => $note],
                $before,
                $actor,
                $schoolId,
            );
        });
    }

    /** @return Collection<int, Schedule> */
    public function schedulesForAttendance(TeacherAttendanceRecord $attendance): Collection
    {
        $schoolId = (int) $attendance->school_id;

        return Schedule::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $attendance->teacher_id)
            ->where('day_of_week', $attendance->date->dayOfWeekIso)
            ->where('status', 'published')
            ->whereHas('term', fn ($query) => $query->whereDate('start_date', '<=', $attendance->date)->whereDate('end_date', '>=', $attendance->date))
            ->with(['classGroup:id,name', 'subject:id,name'])
            ->with(['substituteAssignments' => fn ($query) => $query->whereDate('date', $attendance->date)->with('substituteTeacher:id,name')])
            ->orderBy('start_time')
            ->get();
    }

    /** @return Collection<int, Teacher> */
    public function availableSubstitutes(TeacherAttendanceRecord $attendance, Schedule $schedule): Collection
    {
        $this->assertScheduleMatchesAttendance($attendance, $schedule);
        $excludedTeacherIds = [$attendance->teacher_id];
        $regularScheduleTeacherIds = Schedule::query()
            ->where('school_id', $attendance->school_id)
            ->where('day_of_week', $attendance->date->dayOfWeekIso)
            ->where('status', 'published')
            ->where('start_time', '<', $schedule->end_time)
            ->where('end_time', '>', $schedule->start_time)
            ->whereHas('term', fn ($query) => $query->whereDate('start_date', '<=', $attendance->date)->whereDate('end_date', '>=', $attendance->date))
            ->pluck('teacher_id')
            ->all();
        $substitutedTeacherIds = TeacherSubstituteAssignment::query()
            ->where('school_id', $attendance->school_id)
            ->whereDate('date', $attendance->date)
            ->whereHas('schedule', fn ($query) => $query->where('start_time', '<', $schedule->end_time)->where('end_time', '>', $schedule->start_time))
            ->pluck('substitute_teacher_id')
            ->all();

        $excludedTeacherIds = array_unique([...$excludedTeacherIds, ...$regularScheduleTeacherIds, ...$substitutedTeacherIds]);

        return Teacher::query()
            ->where('school_id', $attendance->school_id)
            ->where('is_active', true)
            ->whereNotIn('id', $excludedTeacherIds)
            ->orderBy('name')
            ->get(['id', 'name', 'nip']);
    }

    public function assignSubstitute(
        TeacherAttendanceRecord $attendance,
        int $scheduleId,
        int $substituteTeacherId,
        User $actor,
        ?string $notes,
    ): TeacherSubstituteAssignment {
        $schoolId = $this->schoolId($actor);
        $this->assertScoped($actor, (int) $attendance->school_id);
        $this->assertCanManageAttendance($actor, $attendance);

        if ($attendance->status === 'present') {
            throw ValidationException::withMessages(['attendance' => 'Guru pengganti hanya diperlukan jika guru berhalangan.']);
        }

        $schedule = Schedule::query()->where('school_id', $schoolId)->findOrFail($scheduleId);
        $this->assertScheduleMatchesAttendance($attendance, $schedule);

        return DB::transaction(function () use ($attendance, $schedule, $substituteTeacherId, $actor, $notes, $schoolId): TeacherSubstituteAssignment {
            $substitute = Teacher::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($substituteTeacherId);
            if ((int) $substitute->id === (int) $attendance->teacher_id) {
                throw ValidationException::withMessages(['substitute_teacher_id' => 'Guru pengganti harus berbeda dari guru yang berhalangan.']);
            }

            if (TeacherSubstituteAssignment::query()->where('school_id', $schoolId)->where('schedule_id', $schedule->id)->whereDate('date', $attendance->date)->exists()) {
                throw ValidationException::withMessages(['schedule_id' => 'Guru pengganti untuk sesi ini sudah ditetapkan.']);
            }

            if ($this->hasScheduleConflict($substitute, $schedule, CarbonImmutable::parse($attendance->date))) {
                throw ValidationException::withMessages(['substitute_teacher_id' => 'Guru pengganti memiliki jadwal lain pada waktu yang sama.']);
            }

            $assignment = TeacherSubstituteAssignment::create([
                'school_id' => $schoolId,
                'attendance_record_id' => $attendance->id,
                'schedule_id' => $schedule->id,
                'substitute_teacher_id' => $substitute->id,
                'assigned_by_user_id' => $actor->id,
                'date' => $attendance->date->toDateString(),
                'notes' => $notes,
            ]);
            $this->audit->log(
                'academic.teacher_substitute.assigned',
                'teacher_substitute_assignment',
                (string) $assignment->id,
                ['teacher_id' => $attendance->teacher_id, 'substitute_teacher_id' => $substitute->id, 'schedule_id' => $schedule->id, 'date' => $attendance->date->toDateString()],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $assignment;
        });
    }

    private function fillApprovedLeaveAttendance(TeacherLeaveRequest $leave, User $actor): void
    {
        $status = $leave->type === 'sick' ? 'sick' : 'permitted';
        $note = TeacherLeaveRequest::TYPES[$leave->type].' (disetujui)';

        for ($date = CarbonImmutable::parse($leave->start_date); $date->lte($leave->end_date); $date = $date->addDay()) {
            if ($date->isSunday()) {
                continue;
            }
            $record = TeacherAttendanceRecord::query()
                ->where('school_id', $leave->school_id)
                ->where('teacher_id', $leave->teacher_id)
                ->whereDate('date', $date->toDateString())
                ->lockForUpdate()
                ->first();
            if ($record && $record->status !== 'absent') {
                continue;
            }

            $before = $record ? ['status' => $record->status, 'notes' => $record->notes] : null;
            if ($record) {
                $record->update(['status' => $status, 'notes' => $note, 'recorded_by_user_id' => $actor->id]);
            } else {
                $record = TeacherAttendanceRecord::create([
                    'school_id' => $leave->school_id,
                    'teacher_id' => $leave->teacher_id,
                    'date' => $date->toDateString(),
                    'status' => $status,
                    'notes' => $note,
                    'recorded_by_user_id' => $actor->id,
                ]);
            }
            $this->audit->log(
                $before ? 'academic.teacher_attendance.corrected' : 'academic.teacher_attendance.recorded',
                'teacher_attendance_record',
                (string) $record->id,
                ['date' => $date->toDateString(), 'teacher_id' => $record->teacher_id, 'status' => $status, 'notes' => $note, 'leave_request_id' => $leave->id],
                $before,
                $actor,
                (int) $leave->school_id,
            );
        }
    }

    private function hasScheduleConflict(Teacher $teacher, Schedule $slot, CarbonImmutable $date): bool
    {
        $regularConflict = Schedule::query()
            ->where('school_id', $teacher->school_id)
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('status', 'published')
            ->where('start_time', '<', $slot->end_time)
            ->where('end_time', '>', $slot->start_time)
            ->whereHas('term', fn ($query) => $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date))
            ->exists();
        if ($regularConflict) {
            return true;
        }

        return TeacherSubstituteAssignment::query()
            ->where('school_id', $teacher->school_id)
            ->where('substitute_teacher_id', $teacher->id)
            ->whereDate('date', $date)
            ->whereHas('schedule', fn ($query) => $query->where('start_time', '<', $slot->end_time)->where('end_time', '>', $slot->start_time))
            ->exists();
    }

    private function assertScheduleMatchesAttendance(TeacherAttendanceRecord $attendance, Schedule $schedule): void
    {
        $matches = (int) $schedule->school_id === (int) $attendance->school_id
            && (int) $schedule->teacher_id === (int) $attendance->teacher_id
            && (int) $schedule->day_of_week === $attendance->date->dayOfWeekIso
            && $schedule->status === 'published'
            && $schedule->term()->whereDate('start_date', '<=', $attendance->date)->whereDate('end_date', '>=', $attendance->date)->exists();
        abort_unless($matches, 404);
    }

    private function assertCanManageAttendance(User $actor, TeacherAttendanceRecord $attendance): void
    {
        if ($actor->hasRole(['admin', 'operator'])) {
            return;
        }
        $teacher = $this->teacherFor($actor);
        abort_unless((int) $teacher->id === (int) $attendance->teacher_id, 404);
    }

    private function assertScoped(User $actor, int $schoolId): void
    {
        abort_unless((int) $actor->school_id === $schoolId, 404);
    }

    private function schoolId(User $actor): int
    {
        $this->assertActive($actor);

        return (int) $actor->school_id;
    }

    private function assertActive(User $actor): void
    {
        if (! $actor->is_active || ! $actor->school_id) {
            throw new AuthorizationException('Akun aktif dalam sekolah diperlukan.');
        }
    }
}
