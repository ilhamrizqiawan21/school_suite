<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\ScheduleService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\SchoolScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', Schedule::class);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = $request->query('class_group_id');
        $teacherId = $request->query('teacher_id');

        $isStudent = $request->user()->hasRole('student');
        $request->validate(['term_id' => ['nullable', 'integer'], 'class_group_id' => ['nullable', 'integer'], 'teacher_id' => ['nullable', 'integer']]);
        $termQuery = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId));
        $terms = $isStudent ? collect() : (clone $termQuery)->with('academicYear:id,name')->orderByDesc('start_date')->get();
        $activeTerm = ! $isStudent && $request->filled('term_id')
            ? (clone $termQuery)->with('academicYear:id,name')->whereKey($request->query('term_id'))->firstOrFail()
            : (clone $termQuery)->with('academicYear:id,name')->where('is_active', true)->first();

        $studentClassId = $isStudent && $activeTerm
            ? Enrollment::query()
                ->where('term_id', $activeTerm->id)
                ->where('status', 'active')
                ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()->id)->where('school_id', $schoolId))
                ->value('class_group_id')
            : null;

        $classes = $activeTerm
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->when($isStudent, fn ($query) => $query->whereKey($studentClassId ?? 0))
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();

        $teachers = $isStudent ? collect() : Teacher::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $subjects = $isStudent
            ? collect()
            : Subject::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $schedules = $activeTerm
            ? Schedule::where('school_id', $schoolId)
                ->where('term_id', $activeTerm->id)
                ->when($isStudent, fn ($q) => $q->where('class_group_id', $studentClassId ?? 0)->where('status', 'published'))
                ->when(! $isStudent && $classGroupId, fn ($q) => $q->where('class_group_id', $classGroupId))
                ->when(! $isStudent && $teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
                ->with(['classGroup:id,name,grade_level', 'subject:id,name,code', 'teacher:id,name'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
            : collect();

        $draftCount = $activeTerm
            ? Schedule::where('school_id', $schoolId)->where('term_id', $activeTerm->id)->where('status', 'draft')->count()
            : 0;

        return Inertia::render('Academic/Schedules/Index', [
            'active_term' => $activeTerm,
            'terms' => $terms,
            'classes' => $classes,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'schedules' => $schedules,
            'can_manage' => Gate::allows('create', Schedule::class),
            'filters' => [
                'class_group_id' => $isStudent ? $studentClassId : $classGroupId,
                'teacher_id' => $teacherId,
            ],
            'draft_count' => $isStudent ? 0 : $draftCount,
            'is_student' => $isStudent,
        ]);
    }

    public function exportPdf(Request $request, SchoolScopeService $schoolScope): Response
    {
        Gate::authorize('viewAny', Schedule::class);

        $schoolId = (int) $request->user()->school_id;
        $isStudent = $request->user()->hasRole('student');

        $validated = $request->validate([
            'term_id' => ['nullable', 'integer'],
            'class_group_id' => ['nullable', 'integer'],
            'teacher_id' => ['nullable', 'integer'],
            'day_of_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'status' => ['nullable', 'in:published,draft'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $termQuery = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->with('academicYear:id,name');

        $activeTerm = ! $isStudent && ! empty($validated['term_id'])
            ? (clone $termQuery)->whereKey($validated['term_id'])->firstOrFail()
            : (clone $termQuery)->where('is_active', true)->first();

        $studentClassId = $isStudent && $activeTerm
            ? Enrollment::query()
                ->where('term_id', $activeTerm->id)
                ->where('status', 'active')
                ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()->id)->where('school_id', $schoolId))
                ->value('class_group_id')
            : null;

        $classGroupId = $isStudent ? $studentClassId : ($validated['class_group_id'] ?? null);
        $teacherId = $isStudent ? null : ($validated['teacher_id'] ?? null);
        $dayOfWeek = $validated['day_of_week'] ?? null;
        $statusFilter = $isStudent ? 'published' : ($validated['status'] ?? null);
        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';

        $schedules = $activeTerm
            ? Schedule::where('school_id', $schoolId)
                ->where('term_id', $activeTerm->id)
                ->when($isStudent, fn ($q) => $q->where('class_group_id', $studentClassId ?? 0)->where('status', 'published'))
                ->when(! $isStudent && $classGroupId, fn ($q) => $q->where('class_group_id', $classGroupId))
                ->when(! $isStudent && $teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
                ->when($dayOfWeek, fn ($q) => $q->where('day_of_week', (int) $dayOfWeek))
                ->when(! $isStudent && $statusFilter, fn ($q) => $q->where('status', $statusFilter))
                ->with(['classGroup:id,name,grade_level', 'subject:id,name,code', 'teacher:id,name'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
            : collect();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $dayNames = [
                1 => 'senin', 2 => 'selasa', 3 => 'rabu',
                4 => 'kamis', 5 => 'jumat', 6 => 'sabtu', 7 => 'minggu',
            ];
            $schedules = $schedules->filter(function (Schedule $slot) use ($needle, $dayNames): bool {
                $haystack = mb_strtolower(implode(' ', [
                    $slot->subject?->name ?? '',
                    $slot->subject?->code ?? '',
                    $slot->teacher?->name ?? '',
                    $slot->classGroup?->name ? 'kelas '.$slot->classGroup->name : '',
                    $slot->room ?? '',
                    $dayNames[$slot->day_of_week] ?? '',
                ]));

                return str_contains($haystack, $needle);
            })->values();
        }

        $selectedClass = $classGroupId
            ? ClassGroup::where('school_id', $schoolId)->find($classGroupId)
            : null;
        $selectedTeacher = $teacherId
            ? Teacher::where('school_id', $schoolId)->find($teacherId)
            : null;

        $school = (object) $schoolScope->getSchoolDetails($schoolId);

        return Pdf::loadView('academic.schedules-export', [
            'schedules' => $schedules,
            'school' => $school,
            'term' => $activeTerm,
            'selectedClass' => $selectedClass,
            'selectedTeacher' => $selectedTeacher,
            'selectedDay' => $dayOfWeek ? (int) $dayOfWeek : null,
            'selectedStatus' => $statusFilter,
            'search' => $search,
            'generatedAt' => now('Asia/Jakarta'),
        ])->setPaper('a4', 'landscape')->download('jadwal-pelajaran.pdf');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Schedule::class);

        $validated = $request->validate($this->scheduleRules($request));

        try {
            $this->scheduleService->createSchedule($validated, $request->user());

            return back()->with('message', 'Jadwal pelajaran berhasil ditambahkan.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['start_time' => $e->getMessage()]);
        }
    }

    private function scheduleRules(Request $request): array
    {
        $schoolId = (int) $request->user()->school_id;

        return [
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')->whereIn('academic_year_id', AcademicYear::where('school_id', $schoolId)->select('id'))],
            'class_group_id' => ['required', 'integer', Rule::exists('class_groups', 'id')->where('school_id', $schoolId)],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        abort_unless((int) $request->user()->school_id === $schedule->school_id, 403);
        Gate::authorize('update', $schedule);
        $data = $request->validate($this->scheduleRules($request));
        try {
            $this->scheduleService->updateSchedule($schedule, $data, $request->user());

            return back()->with('message', 'Jadwal diperbarui sebagai draf. Terbitkan kembali setelah diperiksa.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['start_time' => $exception->getMessage()]);
        }
    }

    public function publish(Request $request): RedirectResponse
    {
        Gate::authorize('create', Schedule::class);

        $validated = $request->validate([
            'term_id' => $this->scheduleRules($request)['term_id'],
        ]);

        try {
            $published = $this->scheduleService->publishSchedules((int) $validated['term_id'], $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()]);
        }

        return back()->with('message', "Sebanyak {$published} jadwal pelajaran draf berhasil diterbitkan.");
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        abort_unless((int) $request->user()->school_id === $schedule->school_id, 403);
        Gate::authorize('delete', $schedule);

        try {
            $this->scheduleService->deleteSchedule($schedule, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()]);
        }

        return back()->with('message', 'Jadwal pelajaran berhasil dihapus.');
    }
}
