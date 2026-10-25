<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Assessment\Domain\Enums\ReportStatusEnum;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Files\PrivateStorageService;
use App\Shared\Jobs\GenerateReportPdfJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueueJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        GenerateReportPdfJob::dispatch(123);

        Queue::assertPushed(GenerateReportPdfJob::class, function ($job) {
            return $job->reportSnapshotId === 123;
        });
    }

    public function test_job_processes_and_saves_report_to_private_storage(): void
    {
        Storage::fake('local');
        $storage = new PrivateStorageService;

        $school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan',
            'status' => 'active',
        ]);

        $ay = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $ay->id,
            'name' => 'Ganjil',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $class = ClassGroup::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'name' => 'VII-A',
            'grade_level' => 7,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'nis' => '1001',
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'term_id' => $term->id,
            'student_id' => $student->id,
            'class_group_id' => $class->id,
            'status' => 'active',
        ]);

        $snapshot = ReportSnapshot::create([
            'school_id' => $school->id,
            'enrollment_id' => $enrollment->id,
            'term_id' => $term->id,
            'class_group_id' => $class->id,
            'student_id' => $student->id,
            'scheme_snapshot' => ['name' => 'KM 2026'],
            'grades_snapshot' => [],
            'attendance_snapshot' => [],
            'notes_snapshot' => [],
            'scheme_version' => 1,
            'status' => ReportStatusEnum::PUBLISHED,
            'checksum' => 'testchecksum1234567890',
            'published_at' => now(),
        ]);

        $job = new GenerateReportPdfJob($snapshot->id);
        $job->handle($storage);

        $expectedFile = "reports/generated/rapor-snapshot-{$snapshot->id}-{$snapshot->checksum}.pdf";
        $this->assertTrue($storage->exists($expectedFile));
        $this->assertStringContainsString('School Suite Digital Report Card', (string) $storage->get($expectedFile));
    }

    public function test_job_handles_missing_snapshot_gracefully(): void
    {
        Storage::fake('local');
        $storage = new PrivateStorageService;

        $job = new GenerateReportPdfJob(99999);
        // Menjalankan handle tidak boleh melempar exception
        $job->handle($storage);

        $this->assertTrue(true);
    }
}
