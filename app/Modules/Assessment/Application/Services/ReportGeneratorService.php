<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Domain\Services\GradeCalculationEngine;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ReportGeneratorService
{
    public function __construct(
        protected AcademicQueryService $academicQuery,
        protected AssessmentAccessService $access,
        protected GradeCalculationEngine $calculationEngine,
    ) {}

    /**
     * Mempersiapkan data lengkap untuk tampilan cetak rapor siswa.
     *
     * @return array<string, mixed>
     */
    public function formatReportView(ReportSnapshot $snapshot, ?string $requestedMode = null): array
    {
        $school = DB::table('schools')->find($snapshot->school_id);
        $schoolSettings = is_string($school?->settings ?? null) ? (json_decode($school->settings, true) ?: []) : (array) ($school?->settings ?? []);
        $student = DB::table('students')->find($snapshot->student_id);
        $ledgerDetail = $student && Schema::hasTable('student_ledger_details')
            ? DB::table('student_ledger_details')->where('school_id', $snapshot->school_id)->where('student_id', $student->id)->first()
            : null;
        $term = DB::table('terms')->find($snapshot->term_id);
        $classGroup = $this->academicQuery->getClassGroupById($snapshot->class_group_id);
        $academicYear = $term ? DB::table('academic_years')->find($term->academic_year_id) : null;
        $homeroomNip = $classGroup?->homeroom_teacher_id
            ? DB::table('teachers')->where('id', $classGroup->homeroom_teacher_id)->value('nip')
            : null;
        $notes = $snapshot->notes_snapshot ?? [];
        $publicHomeroomNotes = is_array($notes['homeroom_notes'] ?? null)
            ? collect($notes['homeroom_notes'])->pluck('content')->filter()->implode("\n")
            : (string) ($notes['homeroom_notes'] ?? '');
        $reportHomeroomNote = trim((string) ($notes['homeroom_report_note'] ?? ''));
        $notes['homeroom_notes'] = $reportHomeroomNote !== '' ? $reportHomeroomNote : $publicHomeroomNotes;
        $notes['homeroom_report_note'] = $reportHomeroomNote;
        $notes['promotion_decision'] = $notes['promotion_decision'] ?? null;
        $notes['report_place'] = $notes['report_place'] ?? ($schoolSettings['city'] ?? null);
        $notes['report_date'] = $notes['report_date'] ?? null;

        $savedMode = (string) data_get($snapshot->scheme_snapshot, 'report_mode', 'sas');
        $reportMode = ($requestedMode !== null && in_array($requestedMode, ['sas', 'sts'], true))
            ? $requestedMode
            : (in_array($savedMode, ['sas', 'sts'], true) ? $savedMode : 'sas');

        $modeGrades = data_get($snapshot->scheme_snapshot, "grades_by_mode.{$reportMode}");
        $rawGrades = is_array($modeGrades) && $modeGrades !== []
            ? $modeGrades
            : ($snapshot->grades_snapshot ?? []);

        return [
            'id' => $snapshot->id,
            'report_id' => $snapshot->id,
            'status' => $snapshot->status->value,
            'report_mode' => $reportMode,
            'scheme_version' => data_get($snapshot->scheme_snapshot, 'version'),
            'checksum' => $snapshot->checksum,
            'published_at' => $snapshot->published_at?->locale('id')->translatedFormat('d F Y'),
            'school' => [
                'name' => $school?->name ?? 'Sekolah',
                'legal_name' => $school?->legal_name ?? $school?->name ?? 'Sekolah',
                'npsn' => $school?->npsn ?? '-',
                'level' => $school?->level ?? 'SMA',
                'school_status' => $school?->school_status ?? 'Swasta',
                'accreditation' => $school?->accreditation ?? '-',
                'address' => $school?->address ?? '-',
                'phone' => $school?->phone ?? '-',
                'email' => $school?->email ?? '-',
                'website' => $school?->website ?? '-',
                'logo_data_uri' => $this->logoDataUri($school?->logo_path),
                'principal_name' => $school?->principal_name,
                'principal_nip' => $schoolSettings['principal_nip'] ?? null,
            ],
            'student' => [
                'id' => $student?->id ?? $snapshot->student_id,
                'name' => $student?->name ?? 'Siswa',
                'nis' => $student?->nis ?? '-',
                'nisn' => $student?->nisn ?? '-',
                'gender' => $student?->gender ?? null,
                'birth_place' => $student?->birth_place ?? null,
                'birth_date' => $student?->birth_date ?? null,
                'religion' => $student?->religion ?? null,
                'address' => $student?->address ?? null,
                'parent_name' => $student?->parent_name ?? null,
                'parent_phone' => $student?->parent_phone ?? null,
                'photo_data_uri' => $this->logoDataUri($ledgerDetail?->photo_path),
                'father_name' => $ledgerDetail?->father_name ?? null,
                'mother_name' => $ledgerDetail?->mother_name ?? null,
                'guardian_name' => $ledgerDetail?->guardian_name ?? null,
                'guardian_address' => $ledgerDetail?->guardian_address ?? null,
                'previous_school' => $ledgerDetail?->previous_school ?? null,
                'accepted_date' => $ledgerDetail?->accepted_date ?? null,
                'accepted_grade' => $ledgerDetail?->accepted_grade ?? null,
            ],
            'class_group' => [
                'id' => $classGroup?->id ?? $snapshot->class_group_id,
                'name' => $classGroup?->name ?? '-',
                'grade_level' => $classGroup?->grade_level ?? null,
                'phase' => $this->resolvePhase($classGroup?->grade_level),
            ],
            'term' => [
                'id' => $term?->id ?? $snapshot->term_id,
                'name' => $term?->name ?? '-',
                'academic_year_id' => $term?->academic_year_id ?? null,
                'academic_year_name' => $academicYear?->name ?? '-',
            ],
            'academic' => [
                'class_name' => $classGroup?->name ?? '-',
                'term_name' => $term?->name ?? '-',
                'homeroom_teacher' => $classGroup?->homeroomTeacher?->name ?? 'Wali Kelas',
                'homeroom_nip' => $homeroomNip,
            ],
            'scheme' => $snapshot->scheme_snapshot,
            'grades' => array_map(fn (array $grade) => array_replace($grade, [
                'predicate' => $grade['predicate'] ?? $grade['letter_grade'] ?? null,
                'is_passed' => $grade['is_passed'] ?? $grade['is_passing'] ?? null,
                'competency_description' => $grade['competency_description'] ?? null,
                'components' => $grade['components'] ?? [],
            ]), $rawGrades),
            'attendance' => $snapshot->attendance_snapshot,
            'notes' => $notes,
            'extracurricular' => $snapshot->extracurricular_snapshot ?? [],
        ];
    }

    /**
     * Menghasilkan data ledger nilai rombel untuk seluruh siswa pada semester dan mode tertentu (SAS / STS).
     *
     * @return array<string, mixed>
     */
    public function exportClassLedger(int $schoolId, int $termId, int $classGroupId, string $reportMode = 'sas'): array
    {
        abort_unless($this->access->classBelongsToSchool($schoolId, $termId, $classGroupId), 404);
        $reportMode = in_array($reportMode, ['sas', 'sts'], true) ? $reportMode : 'sas';

        $school = DB::table('schools')->find($schoolId);
        $schoolSettings = is_string($school?->settings ?? null) ? (json_decode($school->settings, true) ?: []) : (array) ($school?->settings ?? []);
        $classGroup = $this->academicQuery->getClassGroupById($classGroupId);
        $term = DB::table('terms')->find($termId);
        $academicYear = $term ? DB::table('academic_years')->find($term->academic_year_id) : null;
        $homeroomNip = $classGroup?->homeroom_teacher_id
            ? DB::table('teachers')->where('id', $classGroup->homeroom_teacher_id)->value('nip')
            : null;

        $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($termId, $classGroupId);
        $enrollmentIds = $enrollments->pluck('enrollment_id')->map(fn ($id) => (int) $id)->all();
        $teachingAssignments = $this->academicQuery->getTeachingAssignmentsForClass($classGroupId, $termId);

        $subjectCols = $teachingAssignments->map(fn ($a) => [
            'id' => (int) $a->subject_id,
            'teaching_assignment_id' => (int) $a->teaching_assignment_id,
            'name' => (string) $a->subject_name,
            'code' => (string) $a->subject_code,
        ])->unique('id')->values()->all();

        $snapshotsByEnrollment = ReportSnapshot::where('school_id', $schoolId)
            ->where('class_group_id', $classGroupId)
            ->where('term_id', $termId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->get()
            ->keyBy('enrollment_id');

        $scheme = AssessmentScheme::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('components')
            ->first();

        $allScoresByEnrollment = ($scheme && $enrollmentIds !== [])
            ? DB::table('scores')
                ->where('school_id', $schoolId)
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereIn('assessment_component_id', $scheme->components->pluck('id'))
                ->get()
                ->groupBy('enrollment_id')
            : collect();

        $rawAttendanceByEnrollment = $enrollmentIds !== []
            ? $this->academicQuery->getAttendanceAggregates($enrollmentIds)->groupBy('enrollment_id')
            : collect();

        $studentRows = [];
        foreach ($enrollments as $enr) {
            $enrollmentId = (int) $enr->enrollment_id;
            /** @var ?ReportSnapshot $report */
            $report = $snapshotsByEnrollment->get($enrollmentId);

            $subjectScores = [];
            $gradeMap = [];
            $totalFinalScore = 0.0;
            $subjectCount = 0;

            $snapshotGrades = null;
            if ($report) {
                $modeGrades = data_get($report->scheme_snapshot, "grades_by_mode.{$reportMode}");
                if (is_array($modeGrades) && $modeGrades !== []) {
                    $snapshotGrades = $modeGrades;
                } elseif (! empty($report->grades_snapshot) && data_get($report->scheme_snapshot, 'report_mode', 'sas') === $reportMode) {
                    $snapshotGrades = $report->grades_snapshot;
                } elseif (! empty($report->grades_snapshot) && ! $scheme) {
                    $snapshotGrades = $report->grades_snapshot;
                }
            }

            if (is_array($snapshotGrades) && $snapshotGrades !== []) {
                foreach ($snapshotGrades as $grade) {
                    if (isset($grade['final_score']) && $grade['final_score'] !== null) {
                        $scoreVal = round((float) $grade['final_score'], 2);
                        $subjectScores[$grade['subject_name']] = $scoreVal;
                        $gradeMap[(int) $grade['subject_id']] = $scoreVal;
                        $totalFinalScore += $scoreVal;
                        $subjectCount++;
                    }
                }
            } elseif ($scheme) {
                $studentScoreRows = $allScoresByEnrollment->get($enrollmentId, collect())->keyBy('assessment_component_id');
                foreach ($teachingAssignments as $ta) {
                    $subjectComponents = $scheme->components->filter(
                        fn ($c) => $c->teaching_assignment_id === null || (int) $c->teaching_assignment_id === (int) $ta->teaching_assignment_id
                    );
                    $relevantComponents = $this->filterComponentsForMode($subjectComponents, $reportMode);
                    $inputs = [];
                    $hasAnyScore = false;
                    foreach ($relevantComponents as $comp) {
                        $sc = $studentScoreRows->get($comp->id);
                        if ($sc && $sc->raw_score !== null) {
                            $hasAnyScore = true;
                        }
                        $inputs[] = [
                            'code' => $comp->code,
                            'name' => $comp->name,
                            'weight_percentage' => $comp->weight_percentage,
                            'score' => $sc?->raw_score,
                            'remedial_score' => $sc?->remedial_score,
                            'passing_grade' => $comp->passing_grade,
                        ];
                    }

                    if ($hasAnyScore) {
                        $calc = $this->calculationEngine->calculateFinalGrade($inputs, [
                            'rounding_rule' => $scheme->rounding_rule ?? RoundingRuleEnum::HALF_UP,
                            'decimal_places' => $scheme->decimal_places ?? 2,
                            'passing_grade' => $scheme->passing_grade_default ?? 75.0,
                        ]);
                        $scoreVal = round((float) $calc['final_score'], 2);
                        $subjectScores[$ta->subject_name] = $scoreVal;
                        $gradeMap[(int) $ta->subject_id] = $scoreVal;
                        $totalFinalScore += $scoreVal;
                        $subjectCount++;
                    }
                }
            }

            $totalScore = $subjectCount > 0 ? round($totalFinalScore, 2) : 0.0;
            $averageScore = $subjectCount > 0 ? round($totalFinalScore / $subjectCount, 2) : 0.0;

            $attendance = $report?->attendance_snapshot;
            if (! is_array($attendance)) {
                $attCounts = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];
                foreach ($rawAttendanceByEnrollment->get($enrollmentId, collect()) as $attRow) {
                    $attCounts[$attRow->status] = (int) $attRow->count;
                }
                $attendance = $attCounts;
            }

            $studentRows[] = [
                'enrollment_id' => $enrollmentId,
                'student_id' => (int) $enr->student_id,
                'nis' => $enr->nis,
                'name' => $enr->name,
                'roll_number' => $enr->roll_number,
                'grades' => $gradeMap,
                'subject_scores' => $subjectScores,
                'total_score' => $totalScore,
                'average' => $averageScore,
                'average_score' => $averageScore,
                'scored_subjects_count' => $subjectCount,
                'rank' => null,
                'report_status' => $report?->status->value ?? 'not_generated',
                'attendance' => [
                    'present' => (int) ($attendance['present'] ?? 0),
                    'sick' => (int) ($attendance['sick'] ?? 0),
                    'permitted' => (int) ($attendance['permitted'] ?? 0),
                    'absent' => (int) ($attendance['absent'] ?? 0),
                ],
            ];
        }

        // Hitung peringkat (rank) berdasarkan total_score tertinggi bagi siswa yang sudah memiliki nilai
        $rankedIndices = collect($studentRows)
            ->map(fn ($row, $idx) => ['idx' => $idx, 'total' => $row['total_score'], 'count' => $row['scored_subjects_count']])
            ->filter(fn ($item) => $item['count'] > 0 && $item['total'] > 0)
            ->sortByDesc('total')
            ->values();

        foreach ($rankedIndices as $rankPos => $item) {
            $studentRows[$item['idx']]['rank'] = $rankPos + 1;
        }

        return [
            'report_mode' => $reportMode,
            'school' => [
                'name' => $school?->name ?? 'Sekolah',
                'legal_name' => $school?->legal_name ?? $school?->name ?? 'Sekolah',
                'npsn' => $school?->npsn ?? '-',
                'address' => $school?->address ?? '-',
                'city' => $schoolSettings['city'] ?? null,
                'logo_data_uri' => $this->logoDataUri($school?->logo_path),
                'principal_name' => $school?->principal_name ?? null,
                'principal_nip' => $schoolSettings['principal_nip'] ?? null,
            ],
            'class_group_name' => $classGroup?->name ?? '-',
            'class_group' => $classGroup ? [
                'id' => $classGroup->id,
                'name' => $classGroup->name,
                'grade_level' => $classGroup->grade_level,
                'homeroom_teacher' => $classGroup->homeroomTeacher?->name ?? 'Wali Kelas',
                'homeroom_nip' => $homeroomNip,
            ] : null,
            'term_name' => $term?->name ?? '-',
            'term' => $term ? [
                'id' => $term->id,
                'name' => $term->name,
                'academic_year_id' => $term->academic_year_id,
                'academic_year_name' => $academicYear?->name ?? '-',
            ] : null,
            'subjects' => $subjectCols,
            'students' => $studentRows,
        ];
    }

    /**
     * Menghitung kesiapan & progres kelengkapan nilai per mata pelajaran pada satu rombel sesuai mode evaluasi (SAS / STS).
     *
     * @return array{
     *     total_students: int,
     *     overall_completion_percent: int,
     *     subjects: list<array<string, mixed>>
     * }
     */
    public function getClassReadiness(int $schoolId, int $termId, int $classGroupId, string $reportMode = 'sas'): array
    {
        $reportMode = in_array($reportMode, ['sas', 'sts'], true) ? $reportMode : 'sas';
        $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($termId, $classGroupId);
        $enrollmentIds = $enrollments->pluck('enrollment_id')->map(fn ($id) => (int) $id)->all();
        $totalStudents = count($enrollmentIds);
        $assignments = $this->academicQuery->getTeachingAssignmentsForClass($classGroupId, $termId);

        $schemeId = DB::table('assessment_schemes')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->value('id');

        $componentsByTa = $schemeId
            ? DB::table('assessment_components')
                ->where('scheme_id', $schemeId)
                ->get(['id', 'code', 'name', 'teaching_assignment_id'])
                ->groupBy('teaching_assignment_id')
            : collect();

        $subjectProgress = [];
        $sumPercent = 0;

        foreach ($assignments as $ta) {
            $rawComps = $componentsByTa->get((int) $ta->teaching_assignment_id)
                ?? $componentsByTa->get(null)
                ?? collect();
            $filteredComps = $this->filterComponentsForMode($rawComps, $reportMode);
            $componentIds = $filteredComps->pluck('id')->all();

            $scoredStudents = 0;
            if ($totalStudents > 0 && $componentIds !== []) {
                $scoredStudents = DB::table('scores')
                    ->where('school_id', $schoolId)
                    ->whereIn('enrollment_id', $enrollmentIds)
                    ->whereIn('assessment_component_id', $componentIds)
                    ->whereNotNull('raw_score')
                    ->distinct('enrollment_id')
                    ->count('enrollment_id');
            }

            $pct = $totalStudents > 0 ? (int) round(($scoredStudents / $totalStudents) * 100) : 0;
            $sumPercent += $pct;

            $subjectProgress[] = [
                'teaching_assignment_id' => (int) $ta->teaching_assignment_id,
                'subject_id' => (int) $ta->subject_id,
                'subject_name' => (string) $ta->subject_name,
                'teacher_name' => (string) ($ta->teacher_name ?? '—'),
                'scored_students' => $scoredStudents,
                'total_students' => $totalStudents,
                'completion_percent' => $pct,
            ];
        }

        return [
            'total_students' => $totalStudents,
            'overall_completion_percent' => count($subjectProgress) > 0 ? (int) round($sumPercent / count($subjectProgress)) : 0,
            'subjects' => $subjectProgress,
        ];
    }

    /**
     * @param  iterable<int, mixed>  $components
     * @return Collection<int, mixed>
     */
    private function filterComponentsForMode(iterable $components, string $mode): Collection
    {
        $collection = collect($components)->values();
        if ($mode !== 'sts' || $collection->count() <= 1) {
            return $collection;
        }

        $filtered = $collection->reject(function ($component): bool {
            $code = strtoupper(trim((string) ($component->code ?? '')));
            $name = strtoupper(trim((string) ($component->name ?? '')));

            return in_array($code, ['SAS', 'PAS', 'UAS', 'PAT', 'UKK'], true)
                || str_starts_with($code, 'SAS-')
                || str_starts_with($code, 'PAS-')
                || str_contains($name, 'AKHIR SEMESTER')
                || str_contains($name, 'AKHIR TAHUN');
        })->values();

        return $filtered->isNotEmpty() ? $filtered : $collection;
    }

    private function resolvePhase(?int $gradeLevel): string
    {
        return match (true) {
            $gradeLevel === null => '—',
            $gradeLevel <= 2 => 'A',
            $gradeLevel <= 4 => 'B',
            $gradeLevel <= 6 => 'C',
            $gradeLevel <= 9 => 'D',
            $gradeLevel === 10 => 'E',
            default => 'F',
        };
    }

    private function logoDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:'.Storage::disk('local')->mimeType($path).';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
