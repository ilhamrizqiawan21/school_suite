<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\JournalService;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\TeachingJournal;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class JournalController extends Controller
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $schoolId = (int) $request->user()->school_id;
        $classGroupId = $request->query('class_group_id');

        $activeTerm = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $classes = $activeTerm
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeTerm->academic_year_id)
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level'])
            : collect();

        $journals = TeachingJournal::whereHas('lessonSession', function ($q) use ($schoolId, $classGroupId, $activeTerm) {
            $q->where('school_id', $schoolId)
                ->when($classGroupId, fn ($sq) => $sq->where('class_group_id', $classGroupId))
                ->when($activeTerm, fn ($sq) => $sq->where('term_id', $activeTerm->id));
        })
            ->with(['lessonSession.classGroup', 'lessonSession.subject', 'lessonSession.teacher'])
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Academic/Journals/Index', [
            'classes' => $classes,
            'journals' => $journals,
            'filters' => [
                'class_group_id' => $classGroupId,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lesson_session_id' => ['required', 'exists:lesson_sessions,id'],
            'topic' => ['required', 'string', 'max:255'],
            'learning_objectives' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->journalService->saveJournal(
            lessonSessionId: (int) $validated['lesson_session_id'],
            topic: $validated['topic'],
            learningObjectives: $validated['learning_objectives'] ?? null,
            notes: $validated['notes'] ?? null,
            actor: $request->user()
        );

        return back()->with('message', 'Jurnal pembelajaran berhasil disimpan.');
    }
}
