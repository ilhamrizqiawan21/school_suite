<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academic\Application\Services\TeacherOperationsService;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeacherAttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\TeacherLeaveRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class TeacherOperationsController extends Controller
{
    public function __construct(private readonly TeacherOperationsService $operations) {}

    public function attendanceIndex(Request $request): InertiaResponse
    {
        $actor = $request->user();
        $this->authorizeView($actor);
        $schoolId = (int) $actor->school_id;
        $date = $request->validate(['date' => ['nullable', 'date', 'before_or_equal:today']])['date'] ?? today()->toDateString();
        $teacher = $actor->hasRole(['teacher', 'homeroom']) ? $this->operations->teacherFor($actor) : null;

        $teachers = Teacher::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($teacher, fn ($query) => $query->whereKey($teacher->id))
            ->orderBy('name')
            ->get(['id', 'name', 'nip']);
        $records = TeacherAttendanceRecord::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->whereIn('teacher_id', $teachers->pluck('id'))
            ->get()->keyBy('teacher_id');

        $rows = $teachers->map(function (Teacher $item) use ($records): array {
            $record = $records->get($item->id);

            return [
                'teacher_id' => $item->id,
                'name' => $item->name,
                'nip' => $item->nip,
                'record_id' => $record?->id,
                'status' => $record?->status ?? 'present',
                'notes' => $record?->notes ?? '',
                'substitute_url' => $record && $record->status !== 'present' ? "/akademik/absensi-guru/{$record->id}/pengganti" : null,
            ];
        })->values();

        return Inertia::render('Academic/TeacherOperations/Attendance', [
            'date' => $date,
            'rows' => $rows,
            'can_manage_all' => $actor->hasRole(['admin', 'operator']),
            'summary' => [
                'teachers' => $teachers->count(),
                'present' => $rows->where('status', 'present')->count(),
                'permitted' => $rows->where('status', 'permitted')->count(),
                'sick' => $rows->where('status', 'sick')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
            ],
        ]);
    }

    public function saveAttendance(Request $request): RedirectResponse
    {
        $this->authorizeView($request->user());
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.teacher_id' => ['required', 'integer', 'distinct'],
            'records.*.status' => ['required', Rule::in(['present', 'permitted', 'sick', 'absent'])],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $count = $this->operations->saveAttendance($request->user(), $data['date'], $data['records']);

        return back()->with('message', "Absensi guru disimpan. {$count} catatan dibuat atau diperbarui.");
    }

    public function recap(Request $request): InertiaResponse
    {
        $actor = $request->user();
        $this->authorizeView($actor);
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $rows = $this->recapRows($actor, $month);

        return Inertia::render('Academic/TeacherOperations/Recap', [
            'month' => $month,
            'rows' => $rows,
            'summary' => [
                'teachers' => $rows->count(),
                'present' => $rows->sum('present'),
                'permitted' => $rows->sum('permitted'),
                'sick' => $rows->sum('sick'),
                'absent' => $rows->sum('absent'),
            ],
        ]);
    }

    public function recapPdf(Request $request): Response
    {
        $actor = $request->user();
        $this->authorizeView($actor);
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $rows = $this->recapRows($actor, $month);
        $period = CarbonImmutable::createFromFormat('Y-m', $month)->translatedFormat('F Y');
        $this->operations->auditRecapExport($actor, $month, $rows->count());

        return Pdf::loadView('academic.teacher-attendance-recap', compact('rows', 'month', 'period'))
            ->setPaper('a4', 'landscape')
            ->download('rekap-absensi-guru-'.$month.'.pdf');
    }

    public function leaveIndex(Request $request): InertiaResponse
    {
        $actor = $request->user();
        $this->authorizeView($actor);
        $filter = $request->validate(['status' => ['nullable', Rule::in(['all', 'pending', 'approved', 'rejected'])]])['status'] ?? 'all';
        $teacher = $actor->hasRole(['teacher', 'homeroom']) ? $this->operations->teacherFor($actor) : null;
        $leaves = TeacherLeaveRequest::query()
            ->where('school_id', $actor->school_id)
            ->when($teacher, fn ($query) => $query->where('teacher_id', $teacher->id))
            ->when($filter !== 'all', fn ($query) => $query->where('status', $filter))
            ->with(['teacher:id,name,nip', 'reviewedBy:id,name'])
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(15)->withQueryString()
            ->through(fn (TeacherLeaveRequest $leave): array => [
                'id' => $leave->id,
                'teacher_id' => $leave->teacher_id,
                'teacher_name' => $leave->teacher?->name,
                'teacher_nip' => $leave->teacher?->nip,
                'type' => $leave->type,
                'type_label' => $leave->label(),
                'start_date' => $leave->start_date->toDateString(),
                'end_date' => $leave->end_date->toDateString(),
                'date_range_label' => $leave->dateRangeLabel(),
                'reason' => $leave->reason,
                'status' => $leave->status,
                'review_note' => $leave->review_note,
                'reviewer_name' => $leave->reviewedBy?->name,
                'can_cancel' => $teacher && (int) $leave->teacher_id === (int) $teacher->id && $leave->status === 'pending',
                'can_review' => $actor->hasRole(['admin', 'operator']) && $leave->status === 'pending',
            ]);

        return Inertia::render('Academic/TeacherOperations/Leave', [
            'leaves' => $leaves,
            'filter' => $filter,
            'can_apply' => $teacher !== null,
            'can_review' => $actor->hasRole(['admin', 'operator']),
            'types' => TeacherLeaveRequest::TYPES,
            'max_days' => TeacherLeaveRequest::MAX_DAYS,
        ]);
    }

    public function submitLeave(Request $request): RedirectResponse
    {
        $this->authorizeTeacher($request->user());
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(TeacherLeaveRequest::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $this->operations->submitLeave($request->user(), $data);

        return back()->with('message', 'Pengajuan terkirim dan menunggu persetujuan.');
    }

    public function cancelLeave(Request $request, int $leaveId): RedirectResponse
    {
        $leave = TeacherLeaveRequest::query()->where('school_id', $request->user()->school_id)->findOrFail($leaveId);
        $this->operations->cancelLeave($leave, $request->user());

        return back()->with('message', 'Pengajuan dibatalkan.');
    }

    public function reviewLeave(Request $request, int $leaveId): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $leave = TeacherLeaveRequest::query()->where('school_id', $request->user()->school_id)->findOrFail($leaveId);
        $this->operations->reviewLeave($leave, $request->user(), $data['decision'], $data['note'] ?? null);

        return back()->with('message', $data['decision'] === 'approve' ? 'Pengajuan disetujui dan absensi diperbarui.' : 'Pengajuan ditolak.');
    }

    public function substitutes(Request $request, int $attendanceId): InertiaResponse
    {
        $actor = $request->user();
        $this->authorizeView($actor);
        $attendance = TeacherAttendanceRecord::query()
            ->where('school_id', $actor->school_id)
            ->with('teacher:id,name,nip')
            ->findOrFail($attendanceId);
        $this->authorizeAttendanceRecord($actor, $attendance);
        abort_unless($attendance->status !== 'present', 404);

        $slots = $this->operations->schedulesForAttendance($attendance)->map(function (Schedule $schedule) use ($attendance): array {
            $assigned = $schedule->substituteAssignments->first();

            return [
                'id' => $schedule->id,
                'subject' => $schedule->subject?->name,
                'class_name' => $schedule->classGroup?->name,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'substitute' => $assigned?->substituteTeacher ? ['id' => $assigned->substituteTeacher->id, 'name' => $assigned->substituteTeacher->name] : null,
                'candidates' => $assigned ? [] : $this->operations->availableSubstitutes($attendance, $schedule)->map(fn (Teacher $teacher): array => ['id' => $teacher->id, 'name' => $teacher->name, 'nip' => $teacher->nip])->values(),
            ];
        })->values();

        return Inertia::render('Academic/TeacherOperations/Substitutes', [
            'attendance' => [
                'id' => $attendance->id,
                'date' => $attendance->date->toDateString(),
                'status' => $attendance->status,
                'teacher_name' => $attendance->teacher?->name,
            ],
            'slots' => $slots,
        ]);
    }

    public function assignSubstitute(Request $request, int $attendanceId): RedirectResponse
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'integer'],
            'substitute_teacher_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $attendance = TeacherAttendanceRecord::query()->where('school_id', $request->user()->school_id)->findOrFail($attendanceId);
        $this->operations->assignSubstitute($attendance, (int) $data['schedule_id'], (int) $data['substitute_teacher_id'], $request->user(), $data['notes'] ?? null);

        return back()->with('message', 'Guru pengganti berhasil ditetapkan.');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function recapRows(User $actor, string $month): Collection
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
        $query = TeacherAttendanceRecord::query()
            ->where('school_id', $actor->school_id)
            ->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()]);
        if ($actor->hasRole(['teacher', 'homeroom'])) {
            $teacher = $this->operations->teacherFor($actor);
            $query->where('teacher_id', $teacher->id);
        }

        return $query->with('teacher:id,name,nip')->get()
            ->groupBy('teacher_id')
            ->map(function ($records): array {
                $teacher = $records->first()->teacher;

                return [
                    'teacher_id' => $teacher?->id,
                    'name' => $teacher?->name ?? 'Guru tidak tersedia',
                    'nip' => $teacher?->nip,
                    'present' => $records->where('status', 'present')->count(),
                    'permitted' => $records->where('status', 'permitted')->count(),
                    'sick' => $records->where('status', 'sick')->count(),
                    'absent' => $records->where('status', 'absent')->count(),
                ];
            })->values();
    }

    private function authorizeView(User $actor): void
    {
        abort_unless($actor->is_active && $actor->school_id && $actor->hasRole(['admin', 'operator', 'teacher', 'homeroom']), 403);
    }

    private function authorizeTeacher(User $actor): void
    {
        abort_unless($actor->is_active && $actor->hasRole(['teacher', 'homeroom']), 403);
        $this->operations->teacherFor($actor);
    }

    private function authorizeAttendanceRecord(User $actor, TeacherAttendanceRecord $attendance): void
    {
        if ($actor->hasRole(['admin', 'operator'])) {
            return;
        }
        $teacher = $this->operations->teacherFor($actor);
        abort_unless((int) $teacher->id === (int) $attendance->teacher_id, 404);
    }
}
