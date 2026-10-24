<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AttendanceService;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = $request->query('class_group_id');
        $date = $request->query('date', date('Y-m-d'));

        $activeTerm = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $classes = $activeTerm
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();

        $selectedClass = $classGroupId
            ? ClassGroup::where('school_id', $schoolId)->find($classGroupId)
            : ($classes->first() ?? null);

        $studentsWithAttendance = [];
        $sessions = [];

        if ($selectedClass && $activeTerm) {
            $enrollments = Enrollment::where('term_id', $activeTerm->id)
                ->where('class_group_id', $selectedClass->id)
                ->with(['student'])
                ->orderBy('roll_number')
                ->get();

            // Sesi pelajaran pada tanggal ini
            $sessions = LessonSession::where('school_id', $schoolId)
                ->where('class_group_id', $selectedClass->id)
                ->where('date', $date)
                ->with(['subject', 'teacher'])
                ->get();

            // Presensi yang sudah tercatat pada tanggal ini
            $attendances = AttendanceRecord::where('school_id', $schoolId)
                ->whereIn('enrollment_id', $enrollments->pluck('id'))
                ->where('date', $date)
                ->get()
                ->keyBy('enrollment_id');

            foreach ($enrollments as $enr) {
                $att = $attendances->get($enr->id);
                $studentsWithAttendance[] = [
                    'enrollment_id' => $enr->id,
                    'student_id' => $enr->student_id,
                    'nis' => $enr->student->nis,
                    'name' => $enr->student->name,
                    'gender' => $enr->student->gender,
                    'roll_number' => $enr->roll_number,
                    'status' => $att?->status ?? 'present',
                    'notes' => $att?->notes ?? '',
                    'attendance_id' => $att?->id,
                ];
            }
        }

        return Inertia::render('Academic/Attendance/Index', [
            'classes' => $classes,
            'selected_class' => $selectedClass,
            'date' => $date,
            'students' => $studentsWithAttendance,
            'sessions' => $sessions,
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
}
