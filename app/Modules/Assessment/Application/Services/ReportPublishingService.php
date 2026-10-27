<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Domain\Services\GradeCalculationEngine;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReportPublishingService
{
    public function __construct(
        protected GradeCalculationEngine $calculationEngine,
        protected AcademicQueryService $academicQuery,
        protected AuditLogger $auditLogger,
        protected AssessmentAccessService $access
    ) {}

    /**
     * Menghasilkan atau memperbarui draf snapshot rapor untuk seorang siswa.
     */
    public function generateReportSnapshot(
        int $schoolId,
        int $enrollmentId,
        int $termId,
        int $classGroupId,
        int $studentId,
        ?User $actor = null
    ): ReportSnapshot {
        abort_unless($this->access->classBelongsToSchool($schoolId, $termId, $classGroupId), 404);
        abort_if($actor && ((int) $actor->school_id !== $schoolId || ! $this->access->canPublishClass($actor, $termId, $classGroupId)), 403);
        abort_unless(DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.id', $enrollmentId)->where('enrollments.term_id', $termId)
            ->where('enrollments.class_group_id', $classGroupId)->where('students.id', $studentId)
            ->where('students.school_id', $schoolId)->exists(), 404);

        // Cek apakah rapor untuk enrollment dan semester ini sudah terbit
        $existing = ReportSnapshot::where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->first();

        if ($existing && $existing->status === ReportStatusEnum::PUBLISHED) {
            throw new InvalidArgumentException('Rapor untuk siswa pada semester ini telah diterbitkan dan berstatus terkunci permanen (Immutable).');
        }

        // Ambil skema aktif sekolah
        $scheme = AssessmentScheme::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('components')
            ->first();

        if (! $scheme) {
            // Buat skema default kurikulum jika belum ada
            $scheme = AssessmentScheme::create([
                'school_id' => $schoolId,
                'name' => 'Skema Penilaian Baku Sekolah',
                'code' => 'DEFAULT',
                'version' => 1,
                'is_active' => true,
                'rounding_rule' => RoundingRuleEnum::HALF_UP,
                'decimal_places' => 2,
                'passing_grade_default' => 75.00,
            ]);

            // Default komponen: Tugas/Formatif (30%), STS (30%), SAS (40%)
            AssessmentComponent::create([
                'scheme_id' => $scheme->id,
                'name' => 'Formatif & Tugas',
                'code' => 'TP',
                'weight_percentage' => 30.00,
                'passing_grade' => 75.00,
                'sequence' => 1,
            ]);
            AssessmentComponent::create([
                'scheme_id' => $scheme->id,
                'name' => 'Sumatif Tengah Semester',
                'code' => 'STS',
                'weight_percentage' => 30.00,
                'passing_grade' => 75.00,
                'sequence' => 2,
            ]);
            AssessmentComponent::create([
                'scheme_id' => $scheme->id,
                'name' => 'Sumatif Akhir Semester',
                'code' => 'SAS',
                'weight_percentage' => 40.00,
                'passing_grade' => 75.00,
                'sequence' => 3,
            ]);
            $scheme->load('components');
        }

        // 1. Snapshot Skema Penilaian
        $schemeSnapshot = [
            'id' => $scheme->id,
            'name' => $scheme->name,
            'code' => $scheme->code,
            'version' => $scheme->version,
            'rounding_rule' => $scheme->rounding_rule->value,
            'decimal_places' => $scheme->decimal_places,
            'passing_grade_default' => $scheme->passing_grade_default,
        ];

        // 2. Snapshot Capaian Akademik (Grades)
        $subjects = $this->academicQuery->getTeachingAssignmentsForClass($classGroupId, $termId);
        $gradesSnapshot = [];
        if ($subjects->count() > 1 && $scheme->components->contains(fn ($component) => $component->teaching_assignment_id === null)) {
            throw ValidationException::withMessages(['class_group_id' => 'Komponen penilaian umum harus dipetakan ke penugasan mata pelajaran sebelum rapor kelas dengan beberapa mata pelajaran dapat disusun.']);
        }

        foreach ($subjects as $subj) {
            // Cari skor siswa untuk komponen-komponen yang relevan
            $componentInputs = [];
            foreach ($scheme->components->filter(fn ($component) => $component->teaching_assignment_id === null || (int) $component->teaching_assignment_id === (int) $subj->teaching_assignment_id) as $component) {
                $scoreRecord = Score::where('school_id', $schoolId)
                    ->where('enrollment_id', $enrollmentId)
                    ->where('assessment_component_id', $component->id)
                    ->first();

                $componentInputs[] = [
                    'code' => $component->code,
                    'weight_percentage' => $component->weight_percentage,
                    'score' => $scoreRecord?->raw_score,
                    'remedial_score' => $scoreRecord?->remedial_score,
                    'passing_grade' => $component->passing_grade,
                ];
            }

            $calculation = $this->calculationEngine->calculateFinalGrade($componentInputs, [
                'rounding_rule' => $scheme->rounding_rule,
                'decimal_places' => $scheme->decimal_places,
                'passing_grade' => $scheme->passing_grade_default,
            ]);

            $gradesSnapshot[] = [
                'subject_id' => $subj->subject_id,
                'subject_name' => $subj->subject_name,
                'subject_code' => $subj->subject_code,
                'teacher_name' => $subj->teacher_name,
                'passing_grade' => $scheme->passing_grade_default,
                'final_score' => $calculation['final_score'],
                'predicate' => $calculation['predicate'],
                'is_passed' => $calculation['is_passed'],
                'components' => $calculation['components'],
            ];
        }

        // 3. Snapshot Presensi (Attendance)
        $rawAttendance = $this->academicQuery->getAttendanceAggregates([$enrollmentId]);
        $counts = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];
        foreach ($rawAttendance as $row) {
            $counts[$row->status] = (int) $row->count;
        }

        $totalDays = array_sum($counts);
        $percentage = $totalDays > 0 ? round(($counts['present'] / $totalDays) * 100, 2) : 100.0;

        $attendanceSnapshot = [
            'present' => $counts['present'],
            'sick' => $counts['sick'],
            'permitted' => $counts['permitted'],
            'absent' => $counts['absent'],
            'total_days' => $totalDays,
            'attendance_percentage' => $percentage,
        ];

        // 4. Snapshot Catatan Kesiswaan & Prestasi (Catatan privat/BK disaring ketat - SS-F-006)
        $publicNotes = DB::table('student_notes')
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('access_level', 'public')
            ->get(['title', 'content', 'category'])
            ->map(fn ($n) => (array) $n)
            ->all();

        $achievements = DB::table('student_achievements')
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get(['title', 'achievement_type', 'level', 'rank', 'event_date'])
            ->map(fn ($a) => (array) $a)
            ->all();

        $attitudes = DB::table('student_attitudes')
            ->where('enrollment_id', $enrollmentId)
            ->where('term_id', $termId)
            ->get(['type', 'predicate', 'description'])
            ->keyBy('type')
            ->map(fn ($item) => ['predicate' => $item->predicate, 'description' => $item->description])
            ->all();

        $notesSnapshot = [
            'homeroom_notes' => $publicNotes,
            'achievements' => $achievements,
            'attitudes' => $attitudes,
        ];

        // 5. Cryptographic Checksum untuk membuktikan Immutability
        $checksum = $this->calculateChecksum($schemeSnapshot, $gradesSnapshot, $attendanceSnapshot, $notesSnapshot);

        return ReportSnapshot::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'term_id' => $termId,
            ],
            [
                'school_id' => $schoolId,
                'class_group_id' => $classGroupId,
                'student_id' => $studentId,
                'status' => ReportStatusEnum::DRAFT,
                'scheme_snapshot' => $schemeSnapshot,
                'grades_snapshot' => $gradesSnapshot,
                'attendance_snapshot' => $attendanceSnapshot,
                'extracurricular_snapshot' => [],
                'notes_snapshot' => $notesSnapshot,
                'checksum' => $checksum,
            ]
        );
    }

    /**
     * Menerbitkan dan mengunci rapor siswa (Status PUBLISHED - Immutable SS-D-005).
     */
    public function publishReport(ReportSnapshot $report, User $actor): ReportSnapshot
    {
        abort_unless((int) $actor->school_id === (int) $report->school_id
            && $this->access->canPublishClass($actor, (int) $report->term_id, (int) $report->class_group_id), 403);

        if ($report->status === ReportStatusEnum::PUBLISHED) {
            return $report;
        }

        $report->status = ReportStatusEnum::PUBLISHED;
        $report->published_at = now();
        $report->published_by_user_id = $actor->id;
        $report->save();

        $this->auditLogger->log(
            eventType: 'assessment.report.published',
            targetType: 'report_snapshot',
            targetId: (string) $report->id,
            newValues: [
                'enrollment_id' => $report->enrollment_id,
                'student_id' => $report->student_id,
                'checksum' => $report->checksum,
                'published_at' => $report->published_at->toIso8601String(),
            ],
            oldValues: null,
            actor: $actor,
            schoolId: $report->school_id
        );

        return $report;
    }

    /**
     * Memverifikasi integritas payload snapshot terhadap checksum SHA-256.
     */
    public function verifyReportIntegrity(ReportSnapshot $report): bool
    {
        $payload = json_encode([
            'scheme' => $report->scheme_snapshot,
            'grades' => $report->grades_snapshot,
            'attendance' => $report->attendance_snapshot,
            'notes' => $report->notes_snapshot,
        ], JSON_THROW_ON_ERROR);

        // Retain verification for older snapshots that used insertion-order JSON.
        return hash_equals($report->checksum, $this->calculateChecksum(
            $report->scheme_snapshot ?? [],
            $report->grades_snapshot ?? [],
            $report->attendance_snapshot ?? [],
            $report->notes_snapshot ?? [],
        )) || hash_equals($report->checksum, hash('sha256', $payload));
    }

    public function calculateChecksum(array $scheme, array $grades, array $attendance, array $notes): string
    {
        return hash('sha256', json_encode($this->canonicalize([
            'scheme' => $scheme, 'grades' => $grades, 'attendance' => $attendance, 'notes' => $notes,
        ]), JSON_THROW_ON_ERROR));
    }

    private function canonicalize(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(fn ($item) => is_array($item) ? $this->canonicalize($item) : $item, $value);
    }
}
