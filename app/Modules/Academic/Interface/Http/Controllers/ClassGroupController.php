<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\EnrollmentService;
use App\Modules\Academic\Application\Services\TeachingAssignmentService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

class ClassGroupController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService,
        protected TeachingAssignmentService $teachingAssignmentService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', ClassGroup::class);

        $schoolId = (int) $request->user()->school_id;
        $academicYearId = $request->query('academic_year_id');

        // Cari active year jika tidak ada query param
        $activeYear = AcademicYear::where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('id', $academicYearId))
            ->orderByDesc('is_active')
            ->first();

        $academicYears = AcademicYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();

        $classes = $activeYear
            ? ClassGroup::where('school_id', $schoolId)
                ->where('academic_year_id', $activeYear->id)
                ->with(['homeroomTeacher', 'enrollments.student'])
                ->withCount('enrollments')
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get()
            : collect();

        $teachers = Teacher::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'nip']);

        $activeTerm = $activeYear
            ? Term::where('academic_year_id', $activeYear->id)->where('is_active', true)->first()
            : null;

        return Inertia::render('Academic/Classes/Index', [
            'academic_years' => $academicYears,
            'active_year' => $activeYear,
            'active_term' => $activeTerm,
            'classes' => $classes,
            'teachers' => $teachers,
            'can_manage' => Gate::allows('create', ClassGroup::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', ClassGroup::class);

        $schoolId = (int) $request->user()->school_id;

        $validated = $request->validate([
            'academic_year_id' => [
                'required',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('class_groups')->where('academic_year_id', $request->input('academic_year_id')),
            ],
            'homeroom_teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $validated['school_id'] = $schoolId;
        $teacherId = $validated['homeroom_teacher_id'] ?? null;
        unset($validated['homeroom_teacher_id']);

        try {
            $class = DB::transaction(function () use ($validated, $teacherId, $request, $schoolId): ClassGroup {
                $class = ClassGroup::create($validated);

                if ($teacherId) {
                    $this->teachingAssignmentService->assignHomeroomTeacher(
                        (int) $class->id,
                        (int) $teacherId,
                        $request->user()
                    );
                }

                $this->auditLogger->log(
                    eventType: 'academic.class_group.created',
                    targetType: 'class_group',
                    targetId: (string) $class->id,
                    newValues: $class->toArray(),
                    oldValues: null,
                    actor: $request->user(),
                    schoolId: $schoolId
                );

                return $class;
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['homeroom_teacher_id' => $e->getMessage()]);
        }

        return back()->with('message', "Rombongan belajar {$class->name} berhasil dibuat.");
    }

    public function assignHomeroom(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('update', $classGroup);

        $validated = $request->validate([
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where(fn ($query) => $query->where('school_id', $classGroup->school_id)),
            ],
        ]);

        try {
            $this->teachingAssignmentService->assignHomeroomTeacher(
                (int) $classGroup->id,
                (int) $validated['teacher_id'],
                $request->user()
            );

            return back()->with('message', 'Wali kelas berhasil ditetapkan.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['teacher_id' => $e->getMessage()]);
        }
    }
}
