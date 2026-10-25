<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Infrastructure\Models\StudentAttitude;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentAttitudeController extends Controller
{
    public function __construct(private readonly AcademicQueryService $academicQuery, private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeManage($request);
        $schoolId = (int) $request->user()->school_id;
        $term = $this->academicQuery->getActiveTerm($schoolId);
        $classes = $term ? $this->academicQuery->getClassGroups($schoolId, $term->academic_year_id) : collect();
        if ($term && $request->user()->hasRole('homeroom')) {
            $teacher = $this->academicQuery->getTeacherByUserId((int) $request->user()->id);
            $homeroom = $teacher ? $this->academicQuery->getHomeroomClassGroup($schoolId, (int) $teacher->id, (int) $term->academic_year_id) : null;
            $classes = $classes->where('id', $homeroom?->id)->values();
        }
        $requestedClassId = (int) $request->query('class_group_id');
        $defaultClassId = $term ? DB::table('enrollments')->where('term_id', $term->id)->where('status', 'active')->whereIn('class_group_id', $classes->pluck('id'))->orderBy('class_group_id')->value('class_group_id') : null;
        $classId = $requestedClassId ?: (int) ($defaultClassId ?: $classes->first()?->id);
        $enrollments = $term && $classId ? $this->academicQuery->getClassEnrollmentsForAttendance($term->id, $classId) : collect();
        $records = $term ? StudentAttitude::where('school_id', $schoolId)->where('term_id', $term->id)->whereIn('enrollment_id', $enrollments->pluck('enrollment_id'))->get()->groupBy('enrollment_id') : collect();
        $students = $enrollments->map(function ($enrollment) use ($records): array {
            $items = $records->get($enrollment->enrollment_id, collect())->keyBy('type');

            return ['enrollment_id' => $enrollment->enrollment_id, 'nis' => $enrollment->nis, 'name' => $enrollment->name,
                'spiritual_predicate' => $items->get('spiritual')?->predicate ?? 'good', 'spiritual_description' => $items->get('spiritual')?->description ?? '',
                'social_predicate' => $items->get('social')?->predicate ?? 'good', 'social_description' => $items->get('social')?->description ?? ''];
        });

        return Inertia::render('Assessment/Attitudes/Index', ['active_term' => $term, 'classes' => $classes, 'selected_class_id' => (string) $classId, 'students' => $students]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $schoolId = (int) $request->user()->school_id;
        $term = $this->academicQuery->getActiveTerm($schoolId);
        abort_unless($term, 422, 'Semester aktif belum tersedia.');
        $allowedClassIds = $this->academicQuery->getClassGroups($schoolId, $term->academic_year_id)->pluck('id');
        if ($request->user()->hasRole('homeroom')) {
            $teacher = $this->academicQuery->getTeacherByUserId((int) $request->user()->id);
            $homeroom = $teacher ? $this->academicQuery->getHomeroomClassGroup($schoolId, (int) $teacher->id, (int) $term->academic_year_id) : null;
            $allowedClassIds = collect($homeroom ? [$homeroom->id] : []);
        }
        $allowedEnrollmentIds = DB::table('enrollments')->where('term_id', $term->id)->whereIn('class_group_id', $allowedClassIds)->pluck('id')->all();
        $data = $request->validate(['records' => ['required', 'array', 'min:1'], 'records.*.enrollment_id' => ['required', Rule::in($allowedEnrollmentIds)],
            'records.*.spiritual_predicate' => ['required', 'in:excellent,good,fair,needs_guidance'], 'records.*.spiritual_description' => ['required', 'string', 'max:1000'],
            'records.*.social_predicate' => ['required', 'in:excellent,good,fair,needs_guidance'], 'records.*.social_description' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($data, $schoolId, $term, $request): void {
            foreach ($data['records'] as $record) {
                foreach (['spiritual', 'social'] as $type) {
                    StudentAttitude::updateOrCreate(['term_id' => $term->id, 'enrollment_id' => $record['enrollment_id'], 'type' => $type], ['school_id' => $schoolId, 'predicate' => $record[$type.'_predicate'], 'description' => $record[$type.'_description'], 'recorded_by' => $request->user()->id]);
                }
            }
        });
        $this->audit->log('assessment.attitudes.batch_saved', 'student_attitude', null, ['term_id' => $term->id, 'record_count' => count($data['records'])], actor: $request->user(), schoolId: $schoolId);

        return back()->with('message', 'Penilaian sikap berhasil disimpan.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'principal', 'operator', 'homeroom']), 403);
    }
}
