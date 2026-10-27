<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Academic\Application\Services\TeachingPlanDocumentService;
use App\Modules\Academic\Infrastructure\Models\TeachingPlan;
use Tests\TestCase;

class TeachingPlanDocumentServiceTest extends TestCase
{
    public function test_allocation_and_promes_distribution_respect_semester_capacity(): void
    {
        $plan = new TeachingPlan(['data' => [
            'waktu' => ['jp_per_minggu' => 3, 'minggu_efektif' => ['1' => 2, '2' => 4]],
            'chapters' => [['id' => 'a', 'semester' => '1'], ['id' => 'b', 'semester' => '2']],
            'units' => [
                ['id' => 'u1', 'chapter_id' => 'a', 'code' => 'TP1', 'objective' => 'Bilangan', 'jp' => 4],
                ['id' => 'u2', 'chapter_id' => 'b', 'code' => 'TP2', 'objective' => 'Aljabar', 'jp' => 2],
            ],
        ]]);
        $service = new TeachingPlanDocumentService;
        $allocation = $service->allocation($plan);
        self::assertSame(6, $allocation['ganjil']['effective_jp']);
        self::assertSame(4, $allocation['ganjil']['planned_jp']);
        self::assertSame(12, $allocation['genap']['effective_jp']);

        $distribution = $service->promesDistribution($plan, '1');
        self::assertCount(30, $distribution);
        self::assertSame('u1', $distribution[0]['units'][0]['unit_id']);
        self::assertSame(3, $distribution[0]['units'][0]['jp']);
        self::assertSame(1, $distribution[1]['units'][0]['jp']);
        self::assertSame([], $distribution[2]['units']);
    }
}
