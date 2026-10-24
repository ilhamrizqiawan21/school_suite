<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Application\Services\ScheduleService;
use App\Modules\Academic\Domain\Enums\ScheduleStatusEnum;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private Term $term;

    private Teacher $teacher1;

    private Teacher $teacher2;

    private ClassGroup $class7A;

    private ClassGroup $class7B;

    private Subject $math;

    private Subject $science;

    private ScheduleService $scheduleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $this->teacher1 = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198101012005011001',
            'name' => 'Guru Matematika (Sintetis)',
            'gender' => 'L',
        ]);

        $this->teacher2 = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198202022006022002',
            'name' => 'Guru IPA (Sintetis)',
            'gender' => 'P',
        ]);

        $this->class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $this->class7B = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 7,
            'name' => '7B',
            'capacity' => 32,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MTK',
            'name' => 'Matematika',
        ]);

        $this->science = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'IPA',
            'name' => 'Ilmu Pengetahuan Alam',
        ]);

        $this->scheduleService = app(ScheduleService::class);
    }

    public function test_can_create_valid_draft_schedule(): void
    {
        $schedule = $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1, // Senin
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 7A',
        ]);

        $this->assertEquals(ScheduleStatusEnum::DRAFT->value, $schedule->status);
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'status' => 'draft',
            'room' => 'Ruang 7A',
        ]);
    }

    public function test_rejects_teacher_conflict(): void
    {
        // Jadwalkan Guru 1 di kelas 7A pada Senin 07:30 - 09:00
        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 7A',
        ]);

        // Coba jadwalkan Guru 1 di kelas 7B pada Senin 08:00 - 09:30 (tumpang tindih)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Benturan Guru');

        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7B->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:30',
            'room' => 'Ruang 7B',
        ]);
    }

    public function test_rejects_class_group_conflict(): void
    {
        // Jadwalkan Matematika di kelas 7A pada Senin 07:30 - 09:00
        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 7A',
        ]);

        // Coba jadwalkan IPA (Guru 2) di kelas 7A pada jam yang sama
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Benturan Rombel');

        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->science->id,
            'teacher_id' => $this->teacher2->id,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '10:00',
            'room' => 'Lab IPA',
        ]);
    }

    public function test_rejects_room_conflict(): void
    {
        // Gunakan Lab IPA untuk kelas 7A pada Senin 07:30 - 09:00
        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->science->id,
            'teacher_id' => $this->teacher2->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Lab IPA',
        ]);

        // Coba gunakan Lab IPA untuk kelas 7B dengan guru berbeda pada jam yang sama
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Benturan Ruangan');

        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7B->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Lab IPA',
        ]);
    }

    public function test_allows_adjacent_time_slots(): void
    {
        // Slot 1: 07:30 - 09:00
        $sched1 = $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 7A',
        ]);

        // Slot 2: 09:00 - 10:30 (Guru yang sama, kelas 7B, tidak bertabrakan)
        $sched2 = $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7B->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:30',
            'room' => 'Ruang 7B',
        ]);

        $this->assertNotNull($sched1->id);
        $this->assertNotNull($sched2->id);
    }

    public function test_can_publish_draft_schedules(): void
    {
        $this->scheduleService->createSchedule([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 1,
            'start_time' => '07:30',
            'end_time' => '09:00',
            'room' => 'Ruang 7A',
        ]);

        $publishedCount = $this->scheduleService->publishSchedules($this->term->id);

        $this->assertEquals(1, $publishedCount);
        $this->assertDatabaseHas('schedules', [
            'term_id' => $this->term->id,
            'status' => ScheduleStatusEnum::PUBLISHED->value,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.schedule.published',
            'target_type' => 'term_schedule',
        ]);
    }
}
