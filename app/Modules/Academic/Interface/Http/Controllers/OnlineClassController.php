<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\OnlineAttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\OnlineClassSession;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnlineClassController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);
        $schoolId = (int) $request->user()->school_id;
        $term = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->first();
        $isStudent = $request->user()->hasRole('student');
        $teacherId = $request->user()->hasRole('teacher') ? Teacher::where('user_id', $request->user()->id)->value('id') : null;

        $studentEnrollment = $isStudent
            ? Enrollment::where('status', 'active')
                ->whereHas('student', fn ($q) => $q->where('user_id', $request->user()->id)->where('school_id', $schoolId))
                ->when($term, fn ($q) => $q->where('term_id', $term->id))
                ->first(['id', 'class_group_id'])
            : null;
        $studentClassId = $studentEnrollment?->class_group_id;
        $classes = $term ? ClassGroup::where('school_id', $schoolId)->where('academic_year_id', $term->academic_year_id)->when($isStudent, fn ($q) => $q->whereKey($studentClassId ?? 0))->orderBy('name')->get(['id', 'name']) : collect();
        $assignments = $term && ! $isStudent
            ? TeachingAssignment::with(['subject:id,name', 'teacher:id,name', 'classGroup:id,name'])->where('term_id', $term->id)->whereHas('classGroup', fn ($q) => $q->where('school_id', $schoolId))->when($request->user()->hasRole('teacher'), fn ($q) => $q->where('teacher_id', $teacherId ?? 0))->get()
            : collect();

        $sessions = OnlineClassSession::with(['classGroup:id,name', 'teachingAssignment.subject:id,name'])->withCount('attendances')
            ->where('school_id', $schoolId)
            ->when($term, fn ($q) => $q->where('term_id', $term->id), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($request->user()->hasRole('teacher'), fn ($q) => $q->whereHas('teachingAssignment', fn ($assignment) => $assignment->where('teacher_id', $teacherId ?? 0)))
            ->when($isStudent, fn ($q) => $q->where('class_group_id', $studentClassId ?? 0))
            ->latest('starts_at')->limit(100)->get();

        return Inertia::render('Academic/OnlineClasses/Index', [
            'term' => $term,
            'classes' => $classes,
            'assignments' => $assignments,
            'sessions' => $sessions,
            'can_manage' => ! $isStudent,
            'is_student' => $isStudent,
            'student_class' => $studentClassId ? $classes->firstWhere('id', $studentClassId) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)],
            'teaching_assignment_id' => ['nullable', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:255'], 'provider' => ['required', 'in:zoom,google_meet,microsoft_teams,other'],
            'meeting_url' => ['required', 'url:http,https', 'max:2000'], 'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);
        $term = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->where('is_active', true)->firstOrFail();
        if (! empty($data['teaching_assignment_id'])) {
            $assignment = TeachingAssignment::whereKey($data['teaching_assignment_id'])->where('term_id', $term->id)->where('class_group_id', $data['class_group_id'])->firstOrFail();
            if ($request->user()->hasRole('teacher')) {
                $teacherId = Teacher::where('user_id', $request->user()->id)->value('id');
                abort_unless((int) $assignment->teacher_id === (int) $teacherId, 403);
            }
        } elseif ($request->user()->hasRole('teacher')) {
            abort(422, 'Guru harus memilih penugasan mengajar.');
        }
        $session = OnlineClassSession::create([...$data, 'school_id' => $schoolId, 'term_id' => $term->id, 'status' => 'scheduled', 'created_by' => $request->user()->id]);
        $this->audit->log('academic.online_session.created', 'online_class_session', (string) $session->id, $session->only(['class_group_id', 'title', 'starts_at', 'status']), actor: $request->user(), schoolId: $schoolId);

        return back()->with('message', 'Pertemuan daring berhasil dijadwalkan.');
    }

    public function updateStatus(Request $request, OnlineClassSession $onlineClassSession): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->authorizeSession($request, $onlineClassSession);
        $data = $request->validate(['status' => ['required', 'in:scheduled,live,completed,cancelled']]);
        $old = $onlineClassSession->status;
        $onlineClassSession->update($data);
        $this->audit->log('academic.online_session.status_changed', 'online_class_session', (string) $onlineClassSession->id, $data, ['status' => $old], $request->user(), (int) $onlineClassSession->school_id);

        return back()->with('message', 'Status pertemuan diperbarui.');
    }

    public function attendance(Request $request, OnlineClassSession $onlineClassSession): Response
    {
        $this->authorizeManage($request);
        $this->authorizeSession($request, $onlineClassSession);
        $enrollments = Enrollment::with('student:id,nis,name')->where('term_id', $onlineClassSession->term_id)->where('class_group_id', $onlineClassSession->class_group_id)->where('status', 'active')->orderBy('roll_number')->get();
        $records = OnlineAttendanceRecord::where('online_class_session_id', $onlineClassSession->id)->get()->keyBy('enrollment_id');
        $students = $enrollments->map(fn ($e) => ['enrollment_id' => $e->id, 'nis' => $e->student->nis, 'name' => $e->student->name, 'status' => $records->get($e->id)?->status ?? 'present', 'notes' => $records->get($e->id)?->notes]);

        return Inertia::render('Academic/OnlineClasses/Attendance', ['session' => $onlineClassSession->load('classGroup:id,name'), 'students' => $students]);
    }

    public function recordAttendance(Request $request, OnlineClassSession $onlineClassSession): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->authorizeSession($request, $onlineClassSession);
        $data = $request->validate(['records' => ['required', 'array', 'min:1'], 'records.*.enrollment_id' => ['required', 'integer'], 'records.*.status' => ['required', 'in:present,late,excused,absent'], 'records.*.notes' => ['nullable', 'string', 'max:500']]);
        $validIds = Enrollment::where('term_id', $onlineClassSession->term_id)->where('class_group_id', $onlineClassSession->class_group_id)->pluck('id')->all();
        DB::transaction(function () use ($data, $validIds, $onlineClassSession, $request): void {
            foreach ($data['records'] as $record) {
                abort_unless(in_array((int) $record['enrollment_id'], $validIds, true), 422, 'Siswa bukan anggota kelas ini.');
                OnlineAttendanceRecord::updateOrCreate(['online_class_session_id' => $onlineClassSession->id, 'enrollment_id' => $record['enrollment_id']], ['status' => $record['status'], 'notes' => $record['notes'] ?? null, 'recorded_by' => $request->user()->id]);
            }
        });
        $this->audit->log('academic.online_attendance.recorded', 'online_class_session', (string) $onlineClassSession->id, ['record_count' => count($data['records'])], actor: $request->user(), schoolId: (int) $onlineClassSession->school_id);

        return back()->with('message', 'Presensi daring berhasil disimpan.');
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'principal', 'operator', 'teacher', 'homeroom', 'student']), 403);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'principal', 'operator', 'teacher', 'homeroom']), 403);
    }

    private function authorizeSession(Request $request, OnlineClassSession $session): void
    {
        $this->authorizeAccess($request);
        abort_unless((int) $session->school_id === (int) $request->user()->school_id, 404);
        if ($request->user()->hasRole('teacher')) {
            $teacherId = Teacher::where('user_id', $request->user()->id)->value('id');
            abort_unless((int) $session->teachingAssignment?->teacher_id === (int) $teacherId, 403);
        }
    }
}
