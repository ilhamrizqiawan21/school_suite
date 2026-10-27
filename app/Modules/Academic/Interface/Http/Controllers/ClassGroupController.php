<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
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
        protected TeachingAssignmentService $teachingAssignmentService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', ClassGroup::class);

        $schoolId = (int) $request->user()->school_id;
        $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'term_id' => ['nullable', 'integer'],
        ]);
        $academicYears = AcademicYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();
        $activeYear = $request->filled('academic_year_id')
            ? $academicYears->firstWhere('id', (int) $request->query('academic_year_id'))
            : $academicYears->sortByDesc('is_active')->first();
        $terms = $activeYear ? Term::where('academic_year_id', $activeYear->id)->orderByDesc('start_date')->get() : collect();
        $activeTerm = $request->filled('term_id') ? $terms->firstWhere('id', (int) $request->query('term_id'))
            : ($terms->firstWhere('is_active', true) ?? $terms->first());
        if ($request->filled('term_id') && ! $activeTerm) {
            abort(422, 'Semester tidak sesuai dengan tahun ajaran yang dipilih.');
        }
        $classes = $activeYear
            ? ClassGroup::where('school_id', $schoolId)->where('academic_year_id', $activeYear->id)
                ->with('homeroomTeacher:id,name,is_active')
                ->withCount(['enrollments' => fn ($query) => $query->where('term_id', $activeTerm?->id ?? 0)->where('status', 'active')])
                ->orderBy('grade_level')->orderBy('name')->get()
            : collect();
        $teachers = Teacher::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'nip']);

        return Inertia::render('Academic/Classes/Index', [
            'academic_years' => $academicYears,
            'active_year' => $activeYear,
            'active_term' => $activeTerm,
            'terms' => $terms,
            'stats' => ['classes' => $classes->count(), 'students' => (int) $classes->sum('enrollments_count'), 'without_homeroom' => $classes->whereNull('homeroom_teacher_id')->count()],
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
        abort_unless((int) $classGroup->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('update', $classGroup);

        $validated = $request->validate([
            'teacher_id' => [
                'present', 'nullable',
                Rule::exists('teachers', 'id')->where(fn ($query) => $query->where('school_id', $classGroup->school_id)),
            ],
        ]);

        try {
            if ($validated['teacher_id'] !== null) {
                $this->teachingAssignmentService->assignHomeroomTeacher((int) $classGroup->id, (int) $validated['teacher_id'], $request->user());
            } else {
                DB::transaction(function () use ($classGroup, $request) {
                    $oldTeacher = $classGroup->homeroom_teacher_id;
                    $classGroup->update(['homeroom_teacher_id' => null]);
                    $this->auditLogger->log('academic.homeroom_teacher.unassigned', 'class_group', (string) $classGroup->id,
                        ['homeroom_teacher_id' => null], ['homeroom_teacher_id' => $oldTeacher], $request->user(), (int) $classGroup->school_id);
                });
            }

            return back()->with('message', 'Wali kelas berhasil ditetapkan.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['teacher_id' => $e->getMessage()]);
        }
    }

    public function update(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        abort_unless((int) $classGroup->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('update', $classGroup);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('class_groups')->where('academic_year_id', $classGroup->academic_year_id)->ignore($classGroup->id)],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'capacity' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        return DB::transaction(function () use ($request, $classGroup, $validated) {
            $classGroup = ClassGroup::whereKey($classGroup->id)->lockForUpdate()->firstOrFail();
            $peak = (int) $classGroup->enrollments()->where('status', 'active')->selectRaw('count(*) as total')->groupBy('term_id')->pluck('total')->max();
            if ($validated['capacity'] < $peak) {
                return back()->withErrors(['capacity' => "Kapasitas minimal {$peak} sesuai jumlah siswa aktif per semester."]);
            }
            if ((int) $validated['grade_level'] !== $classGroup->grade_level && ($classGroup->enrollments()->exists() || $classGroup->teachingAssignments()->exists())) {
                return back()->withErrors(['grade_level' => 'Tingkat kelas yang memiliki siswa atau penugasan tidak dapat diubah. Buat rombel baru untuk tingkat lain.']);
            }
            $old = $classGroup->toArray();
            $classGroup->update($validated);
            $this->auditLogger->log('academic.class_group.updated', 'class_group', (string) $classGroup->id,
                $classGroup->toArray(), $old, $request->user(), (int) $classGroup->school_id);

            return back()->with('message', 'Rombongan belajar berhasil diperbarui.');
        });
    }

    public function destroy(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        abort_unless((int) $classGroup->school_id === (int) $request->user()->school_id, 403);
        Gate::authorize('delete', $classGroup);

        return DB::transaction(function () use ($request, $classGroup) {
            $classGroup = ClassGroup::whereKey($classGroup->id)->lockForUpdate()->firstOrFail();
            $inUse = $classGroup->homeroom_teacher_id !== null || $classGroup->enrollments()->exists() || $classGroup->teachingAssignments()->exists();
            foreach (['schedules', 'lesson_sessions', 'online_class_sessions', 'report_snapshots', 'student_notes', 'student_incidents', 'announcements', 'academic_calendar_events'] as $table) {
                $inUse = $inUse || DB::table($table)->where('class_group_id', $classGroup->id)->exists();
            }
            if ($inUse) {
                return back()->withErrors(['class_group' => 'Rombel masih memiliki wali kelas, penugasan, atau riwayat akademik dan tidak dapat dihapus.']);
            }
            $snapshot = $classGroup->toArray();
            $classGroup->delete();
            $this->auditLogger->log('academic.class_group.deleted', 'class_group', (string) $classGroup->id,
                oldValues: $snapshot, actor: $request->user(), schoolId: (int) $classGroup->school_id);

            return back()->with('message', 'Rombongan belajar berhasil dihapus.');
        });
    }
}
