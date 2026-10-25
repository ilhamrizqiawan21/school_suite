<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Assessment\Domain\Enums\RoundingRuleEnum;
use App\Modules\Assessment\Domain\Services\GradeCalculationEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GradeCalculationEngineTest extends TestCase
{
    private GradeCalculationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new GradeCalculationEngine;
    }

    /**
     * Uji aturan remedial: nilai remedial dibatasi maksimal sebesar nilai KKTP (passing grade).
     */
    public function test_remedial_caps_at_passing_grade_when_remedial_score_is_higher(): void
    {
        // Nilai awal 50, nilai remedial 95, KKTP 75 -> Nilai efektif adalah 75.0
        $result = $this->engine->calculateRemedialScore(50.0, 95.0, 75.0);
        $this->assertEquals(75.0, $result);
    }

    /**
     * Uji aturan remedial: jika nilai remedial lebih rendah dari nilai awal, pertahankan nilai awal.
     */
    public function test_remedial_retains_initial_if_remedial_is_lower(): void
    {
        // Nilai awal 60, remedial 45, KKTP 75 -> Nilai tetap 60.0
        $result = $this->engine->calculateRemedialScore(60.0, 45.0, 75.0);
        $this->assertEquals(60.0, $result);
    }

    /**
     * Uji aturan remedial: jika nilai awal sudah mencapai/melampaui KKTP, nilai tidak boleh berkurang.
     */
    public function test_remedial_does_not_affect_already_passed_initial_score(): void
    {
        // Nilai awal 88, KKTP 75 -> Nilai tetap 88.0
        $result = $this->engine->calculateRemedialScore(88.0, 90.0, 75.0);
        $this->assertEquals(88.0, $result);
    }

    /**
     * Uji aturan pembulatan: half_up, floor, dan ceil.
     */
    public function test_rounding_rules(): void
    {
        // Half up
        $this->assertEquals(82.50, $this->engine->applyRounding(82.495, RoundingRuleEnum::HALF_UP, 2));
        $this->assertEquals(82.49, $this->engine->applyRounding(82.494, RoundingRuleEnum::HALF_UP, 2));

        // Pecahan .5 pembulatan integer
        $this->assertEquals(83.0, $this->engine->applyRounding(82.5, RoundingRuleEnum::HALF_UP, 0));
        $this->assertEquals(82.0, $this->engine->applyRounding(82.4, RoundingRuleEnum::HALF_UP, 0));

        // Floor
        $this->assertEquals(82.49, $this->engine->applyRounding(82.499, RoundingRuleEnum::FLOOR, 2));

        // Ceil
        $this->assertEquals(82.50, $this->engine->applyRounding(82.491, RoundingRuleEnum::CEIL, 2));
    }

    /**
     * AI_RULES Aturan 7: Uji nilai nol (0.0).
     */
    public function test_grade_calculation_with_zero_scores(): void
    {
        $components = [
            ['code' => 'TUGAS', 'weight_percentage' => 40.0, 'score' => 0.0, 'passing_grade' => 75.0],
            ['code' => 'STS', 'weight_percentage' => 30.0, 'score' => 0.0, 'passing_grade' => 75.0],
            ['code' => 'SAS', 'weight_percentage' => 30.0, 'score' => 0.0, 'passing_grade' => 75.0],
        ];

        $result = $this->engine->calculateFinalGrade($components, [
            'passing_grade' => 75.0,
        ]);

        $this->assertEquals(0.0, $result['final_score']);
        $this->assertEquals('D', $result['predicate']);
        $this->assertFalse($result['is_passed']);
    }

    /**
     * AI_RULES Aturan 7: Uji nilai kosong / null (siswa tidak mengikuti ujian).
     */
    public function test_grade_calculation_with_null_scores(): void
    {
        $components = [
            ['code' => 'TUGAS', 'weight_percentage' => 50.0, 'score' => 80.0],
            ['code' => 'SAS', 'weight_percentage' => 50.0, 'score' => null], // kosong / tidak hadir
        ];

        // Jika treat_null_as_zero = true: (80 * 50 + 0 * 50) / 100 = 40.0
        $result = $this->engine->calculateFinalGrade($components, [
            'treat_null_as_zero' => true,
            'passing_grade' => 75.0,
        ]);

        $this->assertEquals(40.0, $result['final_score']);
        $this->assertFalse($result['is_passed']);
        $this->assertEquals('D', $result['predicate']);

        // Jika treat_null_as_zero = false (diberikan dispensasi): 80.0
        $dispensedResult = $this->engine->calculateFinalGrade($components, [
            'treat_null_as_zero' => false,
            'passing_grade' => 75.0,
        ]);

        $this->assertEquals(80.0, $dispensedResult['final_score']);
        $this->assertTrue($dispensedResult['is_passed']);
        $this->assertEquals('B', $dispensedResult['predicate']);
    }

    /**
     * AI_RULES Aturan 7: Uji nilai batas maksimum (100.0).
     */
    public function test_grade_calculation_with_perfect_scores(): void
    {
        $components = [
            ['code' => 'TUGAS', 'weight_percentage' => 40.0, 'score' => 100.0],
            ['code' => 'STS', 'weight_percentage' => 30.0, 'score' => 100.0],
            ['code' => 'SAS', 'weight_percentage' => 30.0, 'score' => 100.0],
        ];

        $result = $this->engine->calculateFinalGrade($components, [
            'passing_grade' => 75.0,
        ]);

        $this->assertEquals(100.0, $result['final_score']);
        $this->assertEquals('A', $result['predicate']);
        $this->assertTrue($result['is_passed']);
    }

    /**
     * Table-driven testing untuk mencocokkan formula dengan contoh hitung manual sekolah pilot.
     */
    #[DataProvider('pilotCalculationCases')]
    public function test_pilot_manual_calculation_matches_formula(
        array $components,
        float $expectedFinalScore,
        string $expectedPredicate,
        bool $expectedPassed
    ): void {
        $result = $this->engine->calculateFinalGrade($components, [
            'rounding_rule' => RoundingRuleEnum::HALF_UP,
            'decimal_places' => 2,
            'passing_grade' => 75.0,
        ]);

        $this->assertEquals($expectedFinalScore, $result['final_score']);
        $this->assertEquals($expectedPredicate, $result['predicate']);
        $this->assertEquals($expectedPassed, $result['is_passed']);
    }

    public static function pilotCalculationCases(): array
    {
        return [
            'Kasus 1: Siswa Tuntas Murni (Bobot 30/30/40)' => [
                'components' => [
                    ['code' => 'TP', 'weight_percentage' => 30.0, 'score' => 85.0],
                    ['code' => 'STS', 'weight_percentage' => 30.0, 'score' => 80.0],
                    ['code' => 'SAS', 'weight_percentage' => 40.0, 'score' => 90.0],
                ],
                'expectedFinalScore' => 85.50, // 25.5 + 24.0 + 36.0 = 85.50
                'expectedPredicate' => 'B',
                'expectedPassed' => true,
            ],
            'Kasus 2: Siswa Remedial SAS (Awal 40, Remedial 90, Cap di KKTP 75)' => [
                'components' => [
                    ['code' => 'TP', 'weight_percentage' => 50.0, 'score' => 80.0],
                    ['code' => 'SAS', 'weight_percentage' => 50.0, 'score' => 40.0, 'remedial_score' => 90.0, 'passing_grade' => 75.0],
                ],
                // SAS efektif = min(90, 75) = 75.0
                // Final = (80 * 0.5) + (75 * 0.5) = 40 + 37.5 = 77.50
                'expectedFinalScore' => 77.50,
                'expectedPredicate' => 'C',
                'expectedPassed' => true,
            ],
            'Kasus 3: Siswa Belum Tuntas Pasca Remedial (Remedial Tetap Rendah)' => [
                'components' => [
                    ['code' => 'TP', 'weight_percentage' => 50.0, 'score' => 60.0],
                    ['code' => 'SAS', 'weight_percentage' => 50.0, 'score' => 50.0, 'remedial_score' => 65.0, 'passing_grade' => 75.0],
                ],
                // SAS efektif = min(65, 75) = 65.0
                // Final = (60 * 0.5) + (65 * 0.5) = 30 + 32.5 = 62.50
                'expectedFinalScore' => 62.50,
                'expectedPredicate' => 'D',
                'expectedPassed' => false,
            ],
            'Kasus 4: Predikat Sempurna (A >= 90)' => [
                'components' => [
                    ['code' => 'TP', 'weight_percentage' => 50.0, 'score' => 95.0],
                    ['code' => 'SAS', 'weight_percentage' => 50.0, 'score' => 92.0],
                ],
                // Final = 47.5 + 46 = 93.50
                'expectedFinalScore' => 93.50,
                'expectedPredicate' => 'A',
                'expectedPassed' => true,
            ],
        ];
    }
}
