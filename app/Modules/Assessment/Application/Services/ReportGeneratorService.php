<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use Illuminate\Support\Facades\DB;

class ReportGeneratorService
{
    public function __construct(
        protected AcademicQueryService $academicQuery
    ) {}

    /**
     * Mempersiapkan data lengkap untuk tampilan cetak rapor siswa.
     *
     * @return array<string, mixed>
     */
    public function formatReportView(ReportSnapshot $snapshot): array
    {
        $school = DB::table('schools')->find($snapshot->school_id);
        $student = DB::table('students')->find($snapshot->student_id);
        $term = DB::table('terms')->find($snapshot->term_id);
        $classGroup = $this->academicQuery->getClassGroupById($snapshot->class_group_id);

        return [
            'id' => $snapshot->id,
            'report_id' => $snapshot->id,
            'status' => $snapshot->status->value,
            'scheme_version' => $snapshot->scheme_version,
            'checksum' => $snapshot->checksum,
            'published_at' => $snapshot->published_at?->translatedFormat('d F Y'),
            'school' => [
                'name' => $school?->name ?? 'Sekolah',
                'npsn' => $school?->npsn ?? '-',
                'address' => $school?->address ?? '-',
            ],
            'student' => [
                'id' => $student?->id ?? $snapshot->student_id,
                'name' => $student?->name ?? 'Siswa',
                'nis' => $student?->nis ?? '-',
                'nisn' => $student?->nisn ?? '-',
            ],
            'class_group' => [
                'id' => $classGroup?->id ?? $snapshot->class_group_id,
                'name' => $classGroup?->name ?? '-',
            ],
            'term' => [
                'id' => $term?->id ?? $snapshot->term_id,
                'name' => $term?->name ?? '-',
            ],
            'academic' => [
                'class_name' => $classGroup?->name ?? '-',
                'term_name' => $term?->name ?? '-',
                'homeroom_teacher' => $classGroup?->homeroomTeacher?->name ?? 'Wali Kelas',
            ],
            'scheme' => $snapshot->scheme_snapshot,
            'grades' => $snapshot->grades_snapshot,
            'attendance' => $snapshot->attendance_snapshot,
            'notes' => $snapshot->notes_snapshot,
        ];
    }

    /**
     * Menghasilkan data ledger nilai rombel untuk seluruh siswa pada semester berjalan.
     *
     * @return array{
     *     class_group_name: string,
     *     term_name: string,
     *     subjects: list<string>,
     *     students: list<array<string, mixed>>
     * }
     */
    public function exportClassLedger(int $schoolId, int $termId, int $classGroupId): array
    {
        $classGroup = $this->academicQuery->getClassGroupById($classGroupId);
        $term = DB::table('terms')->find($termId);
        $enrollments = $this->academicQuery->getClassEnrollmentsForAttendance($termId, $classGroupId);
        $teachingAssignments = $this->academicQuery->getTeachingAssignmentsForClass($classGroupId, $termId);

        $subjectNames = $teachingAssignments->pluck('subject_name')->unique()->values()->all();
        $subjectCols = $teachingAssignments->map(fn ($a) => [
            'id' => $a->subject_id,
            'name' => $a->subject_name,
            'code' => $a->subject_code,
        ])->unique('id')->values()->all();

        $studentRows = [];
        foreach ($enrollments as $enr) {
            $report = ReportSnapshot::where('enrollment_id', $enr->enrollment_id)
                ->where('term_id', $termId)
                ->first();

            $subjectScores = [];
            $gradeMap = [];
            $totalFinalScore = 0.0;
            $subjectCount = 0;

            if ($report && ! empty($report->grades_snapshot)) {
                foreach ($report->grades_snapshot as $grade) {
                    $subjectScores[$grade['subject_name']] = $grade['final_score'];
                    $gradeMap[$grade['subject_id']] = $grade['final_score'];
                    $totalFinalScore += (float) $grade['final_score'];
                    $subjectCount++;
                }
            }

            $averageScore = $subjectCount > 0 ? round($totalFinalScore / $subjectCount, 2) : 0.0;

            $studentRows[] = [
                'enrollment_id' => $enr->enrollment_id,
                'student_id' => $enr->student_id,
                'nis' => $enr->nis,
                'name' => $enr->name,
                'roll_number' => $enr->roll_number,
                'grades' => $gradeMap,
                'subject_scores' => $subjectScores,
                'average' => $averageScore,
                'average_score' => $averageScore,
                'report_status' => $report?->status->value ?? 'not_generated',
                'attendance' => $report?->attendance_snapshot ?? ['sick' => 0, 'permitted' => 0, 'absent' => 0],
            ];
        }

        return [
            'class_group_name' => $classGroup?->name ?? '-',
            'class_group' => $classGroup ? ['id' => $classGroup->id, 'name' => $classGroup->name] : null,
            'term_name' => $term?->name ?? '-',
            'term' => $term ? ['id' => $term->id, 'name' => $term->name] : null,
            'subjects' => $subjectCols,
            'students' => $studentRows,
        ];
    }
}
