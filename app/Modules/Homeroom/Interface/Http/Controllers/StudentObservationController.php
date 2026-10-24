<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Homeroom\Application\Services\StudentObservationService;
use App\Modules\Homeroom\Infrastructure\Models\StudentNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StudentObservationController extends Controller
{
    public function __construct(
        protected StudentObservationService $observationService,
        protected AcademicQueryService $academicQueryService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', StudentNote::class);

        $schoolId = (int) $request->user()->school_id;
        $classGroupId = $request->query('class_group_id');
        $studentId = $request->query('student_id');

        $activeTerm = $this->academicQueryService->getActiveTerm($schoolId);

        $classes = $activeTerm
            ? $this->academicQueryService->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();

        $students = $this->academicQueryService->getStudents(
            $schoolId,
            $classGroupId ? (int) $classGroupId : null,
            50
        );

        // Ambil catatan berdasarkan filter siswa atau rombel dengan filter akses ketat
        $notes = collect();
        if ($studentId) {
            $notes = $this->observationService->getNotesForStudent((int) $studentId, $request->user());
        } elseif ($classGroupId && $activeTerm) {
            $notes = StudentNote::where('class_group_id', $classGroupId)
                ->where('term_id', $activeTerm->id)
                ->when(! $request->user()->hasRole('admin') && ! $request->user()->hasRole('principal'), function ($q) {
                    $q->where('access_level', '!=', 'confidential');
                })
                ->with('author')
                ->latest('id')
                ->get();
        }

        return Inertia::render('Homeroom/Observations/Index', [
            'classes' => $classes,
            'students' => $students,
            'notes' => $notes,
            'active_term' => $activeTerm,
            'filters' => [
                'class_group_id' => $classGroupId,
                'student_id' => $studentId,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', StudentNote::class);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'class_group_id' => ['nullable', 'exists:class_groups,id'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'category' => ['required', 'in:academic,behavior,counseling,general'],
            'access_level' => ['required', 'in:public,internal,confidential'],
        ]);

        $this->observationService->createNote($validated, $request->user());

        return back()->with('message', 'Catatan observasi siswa berhasil disimpan.');
    }

    public function destroy(Request $request, StudentNote $studentNote): RedirectResponse
    {
        Gate::authorize('delete', $studentNote);

        $this->observationService->deleteNote($studentNote, $request->user());

        return back()->with('message', 'Catatan observasi berhasil dihapus.');
    }
}
