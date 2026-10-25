<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Homeroom\Application\Services\HomeroomAttendanceService;
use App\Modules\Homeroom\Infrastructure\Models\AttendanceDispensation;
use App\Modules\Homeroom\Infrastructure\Models\StudentAchievement;
use App\Modules\Homeroom\Infrastructure\Models\StudentIncident;
use App\Modules\Homeroom\Infrastructure\Models\StudentNote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HomeroomDashboardController extends Controller
{
    public function __construct(
        protected HomeroomAttendanceService $attendanceService,
        protected AcademicQueryService $academicQueryService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;

        $activeTerm = $this->academicQueryService->getActiveTerm($schoolId);

        // Cari rombel perwalian untuk user yang sedang login jika seorang guru
        $teacher = $this->academicQueryService->getTeacherByUserId($user->id);
        $myClass = null;

        if ($teacher && $activeTerm) {
            $myClass = $this->academicQueryService->getHomeroomClassGroup(
                $schoolId,
                $teacher->id,
                $activeTerm->academic_year_id
            );
        }

        // Jika bukan wali kelas atau admin/principal yang memilih kelas lain
        $classGroupId = $request->query('class_group_id', $myClass?->id ? (string) $myClass->id : null);

        $classes = $activeTerm
            ? $this->academicQueryService->getClassGroups($schoolId, $activeTerm->academic_year_id)
            : collect();

        $selectedClass = $classGroupId
            ? $classes->firstWhere('id', (int) $classGroupId)
            : ($myClass ?? $classes->first() ?? null);

        $summary = null;
        $incidentsCount = 0;
        $achievementsCount = 0;
        $notesCount = 0;
        $dispensationsCount = 0;

        if ($selectedClass && $activeTerm) {
            $summary = $this->attendanceService->getClassAttendanceSummary($activeTerm->id, $selectedClass->id);

            $studentIds = collect($summary['students'])->pluck('student_id')->toArray();

            $incidentsCount = StudentIncident::whereIn('student_id', $studentIds)
                ->where('term_id', $activeTerm->id)
                ->count();

            $achievementsCount = StudentAchievement::whereIn('student_id', $studentIds)
                ->where('term_id', $activeTerm->id)
                ->count();

            $notesCount = StudentNote::where('class_group_id', $selectedClass->id)
                ->where('term_id', $activeTerm->id)
                ->count();

            $dispensationsCount = AttendanceDispensation::whereIn('student_id', $studentIds)
                ->where('term_id', $activeTerm->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', now()->toDateString())
                ->where('end_date', '>=', now()->toDateString())
                ->count();
        }

        return Inertia::render('Homeroom/Dashboard/Index', [
            'active_term' => $activeTerm,
            'classes' => $classes,
            'selected_class' => $selectedClass,
            'summary' => $summary,
            'stats' => [
                'incidents_count' => $incidentsCount,
                'achievements_count' => $achievementsCount,
                'notes_count' => $notesCount,
                'dispensations_count' => $dispensationsCount,
            ],
        ]);
    }
}
