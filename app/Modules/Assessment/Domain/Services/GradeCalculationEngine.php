<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Services;

use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use InvalidArgumentException;

class GradeCalculationEngine
{
    /**
     * Hitung nilai hasil remedial sesuai regulasi ketuntasan belajar (KKM/KKTP).
     * Nilai hasil remedial dibatasi maksimal sebesar nilai batas ketuntasan (KKTP).
     * Jika nilai remedial lebih rendah dari nilai awal, maka nilai awal tetap dipertahankan.
     */
    public function calculateRemedialScore(float $initialScore, float $remedialScore, float $passingGrade): float
    {
        // Validasi rentang nilai 0 - 100
        $initialScore = max(0.0, min(100.0, $initialScore));
        $remedialScore = max(0.0, min(100.0, $remedialScore));
        $passingGrade = max(0.0, min(100.0, $passingGrade));

        // Jika nilai awal sudah tuntas, remedial tidak mengubah nilai menjadi lebih rendah
        if ($initialScore >= $passingGrade) {
            return $initialScore;
        }

        // Cap nilai remedial setinggi-tingginya nilai KKTP
        $cappedRemedial = min($remedialScore, $passingGrade);

        // Pertahankan nilai terbaik
        return max($initialScore, $cappedRemedial);
    }

    /**
     * Menerapkan aturan pembulatan angka (half_up, floor, ceil) dengan presisi desimal tertentu.
     */
    public function applyRounding(
        float $value,
        string|RoundingRuleEnum $roundingRule = RoundingRuleEnum::HALF_UP,
        int $decimalPlaces = 2
    ): float {
        $rule = $roundingRule instanceof RoundingRuleEnum
            ? $roundingRule
            : RoundingRuleEnum::tryFrom($roundingRule) ?? RoundingRuleEnum::HALF_UP;

        $factor = 10 ** $decimalPlaces;

        return match ($rule) {
            RoundingRuleEnum::HALF_UP => round($value, $decimalPlaces, PHP_ROUND_HALF_UP),
            RoundingRuleEnum::FLOOR => floor($value * $factor + 0.00000001) / $factor,
            RoundingRuleEnum::CEIL => ceil($value * $factor - 0.00000001) / $factor,
        };
    }

    /**
     * Menghitung nilai akhir mata pelajaran dari kumpulan komponen berbobot persentase.
     *
     * @param  list<array{
     *     code?: string,
     *     weight_percentage: float|int,
     *     score: float|int|null,
     *     remedial_score?: float|int|null,
     *     passing_grade?: float|int
     * }>  $components
     * @param  array{
     *     rounding_rule?: string|RoundingRuleEnum,
     *     decimal_places?: int,
     *     treat_null_as_zero?: bool,
     *     passing_grade?: float|int
     * }  $options
     * @return array{
     *     final_score: float,
     *     predicate: string,
     *     is_passed: bool,
     *     total_weight: float,
     *     components: list<array<string, mixed>>
     * }
     */
    public function calculateFinalGrade(array $components, array $options = []): array
    {
        $roundingRule = $options['rounding_rule'] ?? RoundingRuleEnum::HALF_UP;
        $decimalPlaces = (int) ($options['decimal_places'] ?? 2);
        $treatNullAsZero = (bool) ($options['treat_null_as_zero'] ?? true);
        $passingGrade = (float) ($options['passing_grade'] ?? 75.0);

        if (empty($components)) {
            return [
                'final_score' => 0.0,
                'predicate' => 'D',
                'is_passed' => false,
                'total_weight' => 0.0,
                'components' => [],
            ];
        }

        $totalWeight = 0.0;
        $weightedSum = 0.0;
        $processedComponents = [];

        foreach ($components as $comp) {
            $weight = (float) $comp['weight_percentage'];
            if ($weight < 0.0) {
                throw new InvalidArgumentException("Bobot komponen [{$weight}] tidak boleh bernilai negatif.");
            }

            $rawScore = isset($comp['score']) && $comp['score'] !== null ? (float) $comp['score'] : null;
            $remedialScore = isset($comp['remedial_score']) && $comp['remedial_score'] !== null ? (float) $comp['remedial_score'] : null;
            $compPassingGrade = (float) ($comp['passing_grade'] ?? $passingGrade);

            // Tentukan skor efektif komponen
            $effectiveScore = null;
            if ($rawScore !== null) {
                if ($remedialScore !== null && $rawScore < $compPassingGrade) {
                    $effectiveScore = $this->calculateRemedialScore($rawScore, $remedialScore, $compPassingGrade);
                } else {
                    $effectiveScore = max(0.0, min(100.0, $rawScore));
                }
            } elseif ($treatNullAsZero) {
                $effectiveScore = 0.0;
            }

            if ($effectiveScore !== null) {
                $weightedSum += $effectiveScore * $weight;
                $totalWeight += $weight;
            }

            $processedComponents[] = [
                'code' => $comp['code'] ?? 'COMP',
                'weight_percentage' => $weight,
                'raw_score' => $rawScore,
                'remedial_score' => $remedialScore,
                'effective_score' => $effectiveScore,
                'passing_grade' => $compPassingGrade,
            ];
        }

        $rawFinal = $totalWeight > 0.0 ? ($weightedSum / $totalWeight) : 0.0;
        $finalScore = $this->applyRounding($rawFinal, $roundingRule, $decimalPlaces);

        $predicate = match (true) {
            $finalScore >= 90.0 => 'A',
            $finalScore >= 80.0 => 'B',
            $finalScore >= $passingGrade => 'C',
            default => 'D',
        };

        return [
            'final_score' => $finalScore,
            'predicate' => $predicate,
            'is_passed' => $finalScore >= $passingGrade,
            'total_weight' => $totalWeight,
            'components' => $processedComponents,
        ];
    }
}
