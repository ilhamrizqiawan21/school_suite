<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\EnrollmentService;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\StudentLifecycleEvent;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentLifecycleController extends Controller
{
    public function __construct(private readonly EnrollmentService $service) {}

    public function index(Request $request): Response
    {
        $this->authorizeManage($request);
        $schoolId = (int) $request->user()->school_id;
        $terms = Term::with('academicYear:id,name,school_id')->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->latest('start_date')->get();
        $activeTerm = $terms->firstWhere('is_active', true);
        $classes = ClassGroup::where('school_id', $schoolId)->with('academicYear:id,name')->orderBy('grade_level')->orderBy('name')->get(['id', 'academic_year_id', 'name', 'grade_level']);
        $enrollments = $activeTerm ? Enrollment::with(['student:id,nis,name,status', 'classGroup:id,name'])->where('term_id', $activeTerm->id)->where('status', 'active')->orderBy('class_group_id')->orderBy('roll_number')->get() : collect();
        $events = StudentLifecycleEvent::with(['student:id,nis,name', 'sourceEnrollment.classGroup:id,name', 'targetEnrollment.classGroup:id,name'])->where('school_id', $schoolId)->latest('effective_date')->limit(100)->get();

        return Inertia::render('Academic/Lifecycle/Index', compact('terms', 'activeTerm', 'classes', 'enrollments', 'events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $request->validate(['enrollment_id' => ['required', 'exists:enrollments,id'], 'action' => ['required', 'in:promoted,retained,graduated,withdrawn'], 'target_term_id' => ['required_if:action,promoted,retained', 'nullable', 'exists:terms,id'], 'target_class_group_id' => ['required_if:action,promoted,retained', 'nullable', 'exists:class_groups,id'], 'effective_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:500']]);
        try {
            $this->service->processLifecycle($data, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['action' => $exception->getMessage()]);
        }

        return back()->with('message', 'Siklus siswa berhasil diproses dan dicatat.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'principal', 'operator']), 403);
    }
}
