<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\ScheduleService;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        $activeTerm = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $classes = $activeTerm
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();

        $teachers = Teacher::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $subjects = Subject::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $schedules = $activeTerm
            ? Schedule::where('school_id', $schoolId)
                ->where('term_id', $activeTerm->id)
                ->when($classGroupId, fn ($q) => $q->where('class_group_id', $classGroupId))
                ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
                ->with(['classGroup', 'subject', 'teacher'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
            : collect();

        $draftCount = $activeTerm
            ? Schedule::where('term_id', $activeTerm->id)->where('status', 'draft')->count()
            : 0;

        return Inertia::render('Academic/Schedules/Index', [
            'active_term' => $activeTerm,
            'classes' => $classes,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'schedules' => $schedules,
            'can_manage' => Gate::allows('create', Schedule::class),
            'filters' => [
                'class_group_id' => $classGroupId,
                'teacher_id' => $teacherId,
            ],
            'draft_count' => $draftCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Schedule::class);

        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'class_group_id' => ['required', 'exists:class_groups,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $this->scheduleService->createSchedule($validated, $request->user());

            return back()->with('message', 'Jadwal pelajaran berhasil ditambahkan.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['start_time' => $e->getMessage()]);
        }
    }

    public function publish(Request $request): RedirectResponse
    {
        Gate::authorize('create', Schedule::class);

        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $published = $this->scheduleService->publishSchedules((int) $validated['term_id'], $request->user());

        return back()->with('message', "Sebanyak {$published} jadwal pelajaran draf berhasil diterbitkan.");
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $this->scheduleService->deleteSchedule($schedule, $request->user());

        return back()->with('message', 'Jadwal pelajaran berhasil dihapus.');
    }
}
