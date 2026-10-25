<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Models\User;
use App\Modules\Assessment\Domain\Services\GradeCalculationEngine;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\RemedialRecord;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScoreService
{
    public function __construct(
        protected GradeCalculationEngine $calculationEngine,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Menyimpan input nilai massal siswa untuk suatu komponen penilaian.
     *
     * @param  list<array{
     *     enrollment_id: int,
     *     raw_score: float|int|null,
     *     remedial_score?: float|int|null,
     *     notes?: ?string
     * }>  $entries
     * @return array{saved_count: int, remedial_count: int}
     */
    public function saveBatchScores(
        int $schoolId,
        int $componentId,
        array $entries,
        User $actor
    ): array {
        $component = AssessmentComponent::findOrFail($componentId);

        return DB::transaction(function () use ($schoolId, $component, $entries, $actor) {
            $savedCount = 0;
            $remedialCount = 0;

            foreach ($entries as $entry) {
                $enrollmentId = (int) $entry['enrollment_id'];
                $rawScore = isset($entry['raw_score']) && $entry['raw_score'] !== '' && $entry['raw_score'] !== null
                    ? (float) $entry['raw_score']
                    : null;
                $remedialScore = isset($entry['remedial_score']) && $entry['remedial_score'] !== '' && $entry['remedial_score'] !== null
                    ? (float) $entry['remedial_score']
                    : null;
                $notes = $entry['notes'] ?? null;

                if ($rawScore !== null && ($rawScore < 0.0 || $rawScore > $component->max_score)) {
                    throw new InvalidArgumentException("Nilai [{$rawScore}] berada di luar rentang valid (0 - {$component->max_score}).");
                }

                // Hitung skor akhir komponen
                $finalScore = null;
                if ($rawScore !== null) {
                    if ($remedialScore !== null && $rawScore < $component->passing_grade) {
                        $finalScore = $this->calculationEngine->calculateRemedialScore(
                            $rawScore,
                            $remedialScore,
                            $component->passing_grade
                        );
                    } else {
                        $finalScore = $rawScore;
                    }
                }

                $score = Score::updateOrCreate(
                    [
                        'enrollment_id' => $enrollmentId,
                        'assessment_component_id' => $component->id,
                    ],
                    [
                        'school_id' => $schoolId,
                        'raw_score' => $rawScore,
                        'remedial_score' => $remedialScore,
                        'final_score' => $finalScore,
                        'notes' => $notes,
                        'recorded_by_user_id' => $actor->id,
                    ]
                );

                // Jika ada remedial yang dicatat, rekam ke riwayat remedial
                if ($remedialScore !== null && $rawScore !== null && $rawScore < $component->passing_grade) {
                    RemedialRecord::create([
                        'score_id' => $score->id,
                        'initial_score' => $rawScore,
                        'remedial_score' => $remedialScore,
                        'passing_grade' => $component->passing_grade,
                        'adjusted_score' => $finalScore ?? $remedialScore,
                        'conducted_at' => now()->toDateString(),
                        'notes' => $notes,
                        'recorded_by_user_id' => $actor->id,
                    ]);
                    $remedialCount++;
                }

                $savedCount++;
            }

            $this->auditLogger->log(
                eventType: 'assessment.scores.saved_batch',
                targetType: 'assessment_component',
                targetId: (string) $component->id,
                newValues: [
                    'saved_count' => $savedCount,
                    'remedial_count' => $remedialCount,
                ],
                oldValues: null,
                actor: $actor,
                schoolId: $schoolId
            );

            return [
                'saved_count' => $savedCount,
                'remedial_count' => $remedialCount,
            ];
        });
    }
}
