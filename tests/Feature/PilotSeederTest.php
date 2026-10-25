<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Core\Infrastructure\Models\School;
use Database\Seeders\PilotSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pilot_seeder_populates_complete_school_dataset(): void
    {
        $this->seed(PilotSchoolSeeder::class);

        // 1. Verifikasi Sekolah & User
        $school = School::where('npsn', '20109988')->first();
        $this->assertNotNull($school);
        $this->assertSame('SMP Negeri 1 Percontohan Nusantara', $school->name);

        $this->assertDatabaseHas('users', ['email' => 'admin@pilot.sch.id']);
        $this->assertDatabaseHas('users', ['email' => 'kepsek@pilot.sch.id']);
        $this->assertDatabaseHas('users', ['email' => 'operator@pilot.sch.id']);
        $this->assertDatabaseHas('users', ['email' => 'budi@pilot.sch.id']);

        // 2. Verifikasi Data Akademik
        $this->assertDatabaseCount('subjects', 10);
        $this->assertDatabaseCount('teachers', 7);
        $this->assertDatabaseCount('class_groups', 6);
        $this->assertDatabaseCount('students', 30);
        $this->assertDatabaseCount('enrollments', 30);

        // 3. Verifikasi Skema & Rapor Snapshot
        $scheme = AssessmentScheme::where('code', 'KM-SMP-2026')->first();
        $this->assertNotNull($scheme);
        $this->assertDatabaseCount('assessment_components', 4);

        $snapshot = ReportSnapshot::where('school_id', $school->id)
            ->where('status', ReportStatusEnum::PUBLISHED)
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertNotEmpty($snapshot->checksum);
        $this->assertNotEmpty($snapshot->grades_snapshot);
    }
}
