<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Homeroom\Application\Services\DispensationService;
use App\Modules\Homeroom\Application\Services\StudentDisciplineService;
use App\Modules\Homeroom\Infrastructure\Models\AssistanceRecord;
use App\Modules\Homeroom\Infrastructure\Models\AttendanceDispensation;
use App\Modules\Homeroom\Infrastructure\Models\StudentAchievement;
use App\Modules\Homeroom\Infrastructure\Models\StudentIncident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StudentDisciplineController extends Controller
{
    public function __construct(
        protected StudentDisciplineService $disciplineService,
        protected DispensationService $dispensationService,
        protected AcademicQueryService $academicQueryService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $classGroupId = $request->query('class_group_id');

        $activeTerm = $this->academicQueryService->getActiveTerm($schoolId);

        $classes = $activeTerm
            ? $this->academicQueryService->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();

        $students = $this->academicQueryService->getStudents(
            $schoolId,
            $classGroupId ? (int) $classGroupId : null,
            50
        );

        $incidents = StudentIncident::where('school_id', $schoolId)
            ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
            ->when($classGroupId, fn ($q) => $q->where('class_group_id', $classGroupId))
            ->with('recordedByUser')
            ->latest('incident_date')
            ->paginate(15, ['*'], 'incidents_page');

        $achievements = StudentAchievement::where('school_id', $schoolId)
            ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
            ->latest('event_date')
            ->paginate(15, ['*'], 'achievements_page');

        $assistances = AssistanceRecord::where('school_id', $schoolId)
            ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
            ->with('facilitator')
            ->latest('date')
            ->paginate(15, ['*'], 'assistance_page');

        $dispensations = AttendanceDispensation::where('school_id', $schoolId)
            ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
            ->with('submittedByUser')
            ->latest('start_date')
            ->paginate(15, ['*'], 'dispensations_page');

        return Inertia::render('Homeroom/Discipline/Index', [
            'classes' => $classes,
            'students' => $students,
            'incidents' => $incidents,
            'achievements' => $achievements,
            'assistances' => $assistances,
            'dispensations' => $dispensations,
            'active_term' => $activeTerm,
            'filters' => [
                'class_group_id' => $classGroupId,
            ],
        ]);
    }

    public function storeIncident(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'class_group_id' => ['nullable', 'exists:class_groups,id'],
            'incident_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'penalty_points' => ['required', 'integer', 'min:0', 'max:100'],
            'follow_up_action' => ['nullable', 'string'],
        ]);

        $this->disciplineService->recordIncident($validated, $request->user());

        return back()->with('message', 'Insiden pelanggaran tata tertib berhasil dicatat.');
    }

    public function storeAchievement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'title' => ['required', 'string', 'max:200'],
            'achievement_type' => ['required', 'in:academic,sports,arts,religious,other'],
            'level' => ['required', 'in:school,district,city,province,national,international'],
            'rank' => ['required', 'string', 'max:50'],
            'event_date' => ['required', 'date'],
            'organizer' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->disciplineService->recordAchievement($validated, $request->user());

        return back()->with('message', 'Prestasi siswa berhasil dicatat.');
    }

    public function storeAssistance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:home_visit,counseling,parent_conference,financial_aid'],
            'summary' => ['required', 'string'],
            'agreed_solution' => ['nullable', 'string'],
            'access_level' => ['required', 'in:internal,confidential'],
        ]);

        $this->disciplineService->recordAssistance($validated, $request->user());

        return back()->with('message', 'Catatan tindak lanjut berhasil disimpan.');
    }

    public function storeDispensation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'in:sick,event,personal,other'],
            'title' => ['required', 'string', 'max:200'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->dispensationService->recordDispensation($validated, $request->user());

        return back()->with('message', 'Dispensasi atau surat izin berhasil dicatat.');
    }
}
