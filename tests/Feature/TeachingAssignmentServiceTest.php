<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Application\Services\TeachingAssignmentService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TeachingAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private TeachingAssignmentService $assignmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $this->assignmentService = app(TeachingAssignmentService::class);
    }

    public function test_can_assign_subject_teacher_and_logs_audit(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'IPA',
            'name' => 'Ilmu Pengetahuan Alam',
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198001012005011002',
            'name' => 'Sri Wahyuni, S.Pd (Sintetis)',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $assignment = $this->assignmentService->assignSubjectTeacher(
            termId: $term->id,
            classGroupId: $class7A->id,
            subjectId: $subject->id,
            teacherId: $teacher->id,
            hoursPerWeek: 4
        );

        $this->assertDatabaseHas('teaching_assignments', [
            'id' => $assignment->id,
            'term_id' => $term->id,
            'class_group_id' => $class7A->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 4,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.teaching_assignment.created',
            'target_type' => 'teaching_assignment',
            'target_id' => (string) $assignment->id,
        ]);
    }

    public function test_cannot_assign_duplicate_subject_teacher(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'IPA',
            'name' => 'Ilmu Pengetahuan Alam',
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198001012005011002',
            'name' => 'Sri Wahyuni, S.Pd (Sintetis)',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $this->assignmentService->assignSubjectTeacher(
            termId: $term->id,
            classGroupId: $class7A->id,
            subjectId: $subject->id,
            teacherId: $teacher->id
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penugasan guru untuk mata pelajaran dan kelas ini sudah ada');

        $this->assignmentService->assignSubjectTeacher(
            termId: $term->id,
            classGroupId: $class7A->id,
            subjectId: $subject->id,
            teacherId: $teacher->id
        );
    }

    public function test_can_assign_homeroom_teacher_and_enforces_uniqueness_per_year(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $class7B = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7B',
            'capacity' => 32,
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'nip' => '198001012005011002',
            'name' => 'Sri Wahyuni, S.Pd (Sintetis)',
            'gender' => 'P',
            'is_active' => true,
        ]);

        // Berhasil menetapkan di 7A
        $this->assignmentService->assignHomeroomTeacher($class7A->id, $teacher->id);

        $this->assertDatabaseHas('class_groups', [
            'id' => $class7A->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.homeroom_teacher.assigned',
            'target_type' => 'class_group',
            'target_id' => (string) $class7A->id,
        ]);

        // Ditolak jika guru yang sama ditetapkan sebagai wali kelas di 7B pada tahun ajaran yang sama
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah ditugaskan sebagai wali kelas di rombel [7A]');

        $this->assignmentService->assignHomeroomTeacher($class7B->id, $teacher->id);
    }
}
