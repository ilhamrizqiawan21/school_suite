<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AttendanceService;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = (int) $request->query('class_group_id', 0);
        $subjectId = (int) $request->query('subject_id', 0);
        $month = $request->query('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month) === 1, 422);

        $activeTerm = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $teacher = Teacher::where('school_id', $schoolId)->where('user_id', $request->user()->id)->first();
        $assignments = $activeTerm && $teacher
            ? $teacher->teachingAssignments()->where('term_id', $activeTerm->id)->with('subject:id,name,code')->get()
            : collect();
        $classes = $activeTerm
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->whereIn('id', $assignments->pluck('class_group_id')->unique())
                ->orderBy('grade_level')->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();

        $selectedClass = $classGroupId
            ? $classes->firstWhere('id', $classGroupId)
            : ($classes->first() ?? null);

        $subjects = $selectedClass
            ? $assignments->where('class_group_id', $selectedClass->id)->pluck('subject')->filter()->unique('id')->values()
            : collect();
        $selectedSubject = $subjectId ? $subjects->firstWhere('id', $subjectId) : ($subjects->first() ?? null);
        $monthStart = now()->parse($month.'-01')->startOfMonth()->toDateString();
        $monthEnd = now()->parse($month.'-01')->endOfMonth()->toDateString();

        $studentsWithAttendance = [];
        $sessions = collect();
        $selectedSession = null;

        if ($selectedClass && $activeTerm) {
            $sessions = $selectedSubject
                ? LessonSession::where('school_id', $schoolId)
                    ->where('term_id', $activeTerm->id)
                    ->where('teacher_id', $teacher?->id)
                    ->where('class_group_id', $selectedClass->id)
                    ->where('subject_id', $selectedSubject->id)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->withCount('attendanceRecords')
                    ->orderByDesc('date')->orderByDesc('start_time')->get()
                : collect();

            $selectedSessionId = (int) $request->query('session_id', 0);
            $selectedSession = $sessions->firstWhere('id', $selectedSessionId)
                ?? $sessions->firstWhere('date', now()->toDateString())
                ?? $sessions->first();

            if ($selectedSession) {
                $enrollments = Enrollment::where('term_id', $activeTerm->id)
                    ->where('class_group_id', $selectedClass->id)
                    ->with('student')->orderBy('roll_number')->get();

                $attendances = AttendanceRecord::where('school_id', $schoolId)
                    ->where('lesson_session_id', $selectedSession->id)
                    ->whereIn('enrollment_id', $enrollments->pluck('id'))
                    ->get()->keyBy('enrollment_id');

                foreach ($enrollments as $enr) {
                    $att = $attendances->get($enr->id);
                    $studentsWithAttendance[] = [
                        'enrollment_id' => $enr->id, 'student_id' => $enr->student_id,
                        'nis' => $enr->student->nis, 'name' => $enr->student->name,
                        'gender' => $enr->student->gender, 'roll_number' => $enr->roll_number,
                        'status' => $att?->status ?? 'present', 'notes' => $att?->notes ?? '',
                        'attendance_id' => $att?->id,
                    ];
                }
            }
        }

        $studentCount = $selectedClass && $activeTerm
            ? Enrollment::where('term_id', $activeTerm->id)->where('class_group_id', $selectedClass->id)->count()
            : 0;

        return Inertia::render('Academic/Attendance/Index', [
            'classes' => $classes,
            'selected_class' => $selectedClass,
            'date' => $selectedSession?->date?->toDateString() ?? now()->toDateString(),
            'month' => $month,
            'subjects' => $subjects,
            'selected_subject' => $selectedSubject,
            'students' => $studentsWithAttendance,
            'sessions' => $sessions->map(fn (LessonSession $session) => [
                'id' => $session->id, 'date' => $session->date->toDateString(),
                'meeting_number' => $session->meeting_number, 'start_time' => substr((string) $session->start_time, 0, 5),
                'end_time' => substr((string) $session->end_time, 0, 5), 'status' => $session->status,
                'attendance_count' => $session->attendance_records_count,
                'student_count' => $studentCount,
            ])->values(),
            'selected_session_id' => $selectedSession?->id,
            'active_term' => $activeTerm,
        ]);
    }

    public function record(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['nullable', 'exists:lesson_sessions,id'],
            'date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.enrollment_id' => ['required', 'exists:enrollments,id'],
            'records.*.status' => ['required', 'in:present,sick,permitted,absent'],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $schoolId = (int) $request->user()->school_id;

        // Jika terdapat session_id, catat ke session
        if (! empty($validated['session_id'])) {
            $session = LessonSession::with('teacher')->findOrFail($validated['session_id']);
            Gate::authorize('record', $session);
            abort_unless((int) $session->school_id === $schoolId, 404);
            $validEnrollmentIds = Enrollment::where('term_id', $session->term_id)
                ->where('class_group_id', $session->class_group_id)->pluck('id')->all();
            validator($validated, [
                'records.*.enrollment_id' => ['required', 'distinct', Rule::in($validEnrollmentIds)],
            ])->validate();
            $submittedEnrollmentIds = collect($validated['records'])->pluck('enrollment_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $expectedEnrollmentIds = collect($validEnrollmentIds)->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($submittedEnrollmentIds !== $expectedEnrollmentIds) {
                throw ValidationException::withMessages([
                    'records' => 'Presensi harus memuat seluruh siswa pada kelas dan pertemuan ini.',
                ]);
            }

            $result = $this->attendanceService->recordSessionAttendance(
                lessonSessionId: (int) $session->id,
                records: $validated['records'],
                actor: $request->user()
            );

            $msg = "Presensi sesi berhasil disimpan. ({$result['recorded_count']} baru, {$result['corrected_count']} terkoreksi).";
        } else {
            Gate::authorize('record', AttendanceRecord::class);

            // Presensi harian
            $corrected = 0;
            $new = 0;
            foreach ($validated['records'] as $entry) {
                $existing = AttendanceRecord::where('school_id', $schoolId)
                    ->where('enrollment_id', $entry['enrollment_id'])
                    ->where('date', $validated['date'])
                    ->where('type', 'daily')
                    ->first();

                if ($existing) {
                    if ($existing->status !== $entry['status'] || $existing->notes !== ($entry['notes'] ?? null)) {
                        $this->attendanceService->correctAttendanceRecord(
                            attendanceRecordId: $existing->id,
                            newStatus: $entry['status'],
                            reason: $entry['notes'] ?? 'Koreksi formulir harian',
                            actor: $request->user()
                        );
                        $corrected++;
                    }
                } else {
                    AttendanceRecord::create([
                        'school_id' => $schoolId,
                        'enrollment_id' => $entry['enrollment_id'],
                        'date' => $validated['date'],
                        'type' => 'daily',
                        'status' => $entry['status'],
                        'notes' => $entry['notes'] ?? null,
                        'recorded_by_user_id' => $request->user()->id,
                    ]);
                    $new++;
                }
            }

            $msg = "Presensi harian berhasil disimpan. ({$new} baru, {$corrected} terkoreksi).";
        }

        return back()->with('message', $msg);
    }

    public function correct(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        Gate::authorize('record', $attendanceRecord);

        $validated = $request->validate([
            'status' => ['required', 'in:present,sick,permitted,absent'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->attendanceService->correctAttendanceRecord(
                attendanceRecordId: $attendanceRecord->id,
                newStatus: $validated['status'],
                reason: $validated['reason'],
                actor: $request->user()
            );

            return back()->with('message', 'Status presensi berhasil dikoreksi.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function recap(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);
        $schoolId = (int) $request->user()->school_id;
        $activeTerm = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->first();
        $classes = $activeTerm ? ClassGroup::where('school_id', $schoolId)->where('academic_year_id', $activeTerm->academic_year_id)->orderBy('name')->get(['id', 'name', 'grade_level']) : collect();
        $classId = (int) ($request->query('class_group_id') ?: $classes->first()?->id);
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        return Inertia::render('Academic/Attendance/Recap', [
            'classes' => $classes,
            'selected_class_id' => (string) $classId,
            'from' => $from,
            'to' => $to,
            'rows' => $this->recapRows($schoolId, $activeTerm?->id, $classId, $from, $to),
        ]);
    }

    public function exportRecap(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);
        $validated = $request->validate(['class_group_id' => ['required', 'integer'], 'from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);
        $schoolId = (int) $request->user()->school_id;
        $term = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->firstOrFail();
        $class = ClassGroup::where('school_id', $schoolId)->findOrFail($validated['class_group_id']);
        $rows = $this->recapRows($schoolId, $term->id, (int) $class->id, $validated['from'], $validated['to']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['NIS', 'Nama', 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Total', 'Persentase Hadir']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->nis, $row->name, $row->present, $row->sick, $row->permitted, $row->absent, $row->total, $row->attendance_rate.'%']);
            }
            fclose($out);
        }, 'rekap-presensi-'.str($class->name)->slug().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function recapRows(int $schoolId, ?int $termId, int $classId, string $from, string $to): array
    {
        if (! $termId || ! $classId) {
            return [];
        }

        return DB::table('enrollments')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->leftJoin('attendance_records', function ($join) use ($schoolId, $from, $to) {
                $join->on('attendance_records.enrollment_id', '=', 'enrollments.id')->where('attendance_records.school_id', $schoolId)->whereBetween('attendance_records.date', [$from, $to]);
            })
            ->where('enrollments.term_id', $termId)->where('enrollments.class_group_id', $classId)
            ->groupBy('enrollments.id', 'students.nis', 'students.name', 'enrollments.roll_number')
            ->orderBy('enrollments.roll_number')
            ->selectRaw("students.nis, students.name, SUM(CASE WHEN attendance_records.status='present' THEN 1 ELSE 0 END) present, SUM(CASE WHEN attendance_records.status='sick' THEN 1 ELSE 0 END) sick, SUM(CASE WHEN attendance_records.status='permitted' THEN 1 ELSE 0 END) permitted, SUM(CASE WHEN attendance_records.status='absent' THEN 1 ELSE 0 END) absent, COUNT(attendance_records.id) total")
            ->get()->map(function ($row) {
                $row->attendance_rate = $row->total ? round(($row->present / $row->total) * 100, 1) : 0;

                return $row;
            })->all();
    }
}
