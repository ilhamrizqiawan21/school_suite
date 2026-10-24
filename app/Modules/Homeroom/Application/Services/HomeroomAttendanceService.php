<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Modules\Academic\Application\Services\AcademicQueryService;

class HomeroomAttendanceService
{
    public function __construct(
        protected AcademicQueryService $academicQueryService
    ) {}

    /**
     * Menghitung rekapitulasi kehadiran seluruh siswa di rombel beserta flag peringatan dini (Early Warning).
     *
     * @return array{
     *     class_group_id: int,
     *     term_id: int,
     *     total_students: int,
     *     students_needing_attention_count: int,
     *     average_attendance_percentage: float,
     *     students: list<array<string, mixed>>
     * }
     */
    public function getClassAttendanceSummary(int $termId, int $classGroupId): array
    {
        // Ambil data siswa dan enrollment untuk kelas & semester ini melalui AcademicQueryService
        $enrollments = $this->academicQueryService->getClassEnrollmentsForAttendance($termId, $classGroupId);

        if ($enrollments->isEmpty()) {
            return [
                'class_group_id' => $classGroupId,
                'term_id' => $termId,
                'total_students' => 0,
                'students_needing_attention_count' => 0,
                'average_attendance_percentage' => 100.0,
                'students' => [],
            ];
        }

        $enrollmentIds = $enrollments->pluck('enrollment_id')->map(fn ($id) => (int) $id)->all();

        // Ambil rekap status presensi per enrollment melalui AcademicQueryService
        $attendanceData = $this->academicQueryService->getAttendanceAggregates($enrollmentIds);

        // Susun pivot per enrollment
        $grouped = [];
        foreach ($attendanceData as $row) {
            $grouped[$row->enrollment_id][$row->status] = (int) $row->count;
        }

        $studentSummaries = [];
        $totalPercentage = 0.0;
        $attentionCount = 0;

        foreach ($enrollments as $enr) {
            $enrId = $enr->enrollment_id;
            $present = $grouped[$enrId]['present'] ?? 0;
            $sick = $grouped[$enrId]['sick'] ?? 0;
            $permitted = $grouped[$enrId]['permitted'] ?? 0;
            $absent = $grouped[$enrId]['absent'] ?? 0;

            $totalDays = $present + $sick + $permitted + $absent;
            $percentage = $totalDays > 0 ? round(($present / $totalDays) * 100, 1) : 100.0;

            // Indikator Peringatan Dini (Early Warning Threshold):
            // 1. Alpa >= 3 hari
            // 2. Persentase kehadiran < 85% jika total hari sudah tercatat minimal 5 hari
            $needsAttention = false;
            $reasons = [];

            if ($absent >= 3) {
                $needsAttention = true;
                $reasons[] = "Alpa telah mencapai {$absent} hari";
            }

            if ($totalDays >= 5 && $percentage < 85.0) {
                $needsAttention = true;
                $reasons[] = "Kehadiran rendah ({$percentage}%)";
            }

            if ($needsAttention) {
                $attentionCount++;
            }

            $totalPercentage += $percentage;

            $studentSummaries[] = [
                'enrollment_id' => $enrId,
                'student_id' => $enr->student_id,
                'nis' => $enr->nis,
                'name' => $enr->name,
                'gender' => $enr->gender,
                'roll_number' => $enr->roll_number,
                'present' => $present,
                'sick' => $sick,
                'permitted' => $permitted,
                'absent' => $absent,
                'total_recorded' => $totalDays,
                'attendance_percentage' => $percentage,
                'needs_attention' => $needsAttention,
                'attention_reason' => implode(', ', $reasons),
            ];
        }

        $studentCount = count($enrollments);
        $classAverage = $studentCount > 0 ? round($totalPercentage / $studentCount, 1) : 100.0;

        return [
            'class_group_id' => $classGroupId,
            'term_id' => $termId,
            'total_students' => $studentCount,
            'students_needing_attention_count' => $attentionCount,
            'average_attendance_percentage' => $classAverage,
            'students' => $studentSummaries,
        ];
    }
}
