<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;
use Database\Seeders\PilotSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningControllersTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private TeachingAssignment $teachingAssignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PilotSchoolSeeder::class);
        $this->teacher = User::where('email', 'budi@pilot.sch.id')->firstOrFail();
        $this->student = User::where('email', 'siswa26001@pilot.sch.id')->firstOrFail();
        $this->teacher->forceFill(['must_change_password' => false])->save();
        $this->student->forceFill(['must_change_password' => false])->save();
        $this->teachingAssignment = TeachingAssignment::whereHas('teacher', fn ($query) => $query->where('user_id', $this->teacher->id))->firstOrFail();
    }

    public function test_teacher_can_publish_material_and_assignment(): void
    {
        Storage::fake('local');
        $this->actingAs($this->teacher);

        $materialResponse = $this->post(route('learning.materials.store'), [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'title' => 'Modul Pecahan',
            'description' => 'Materi pengantar pecahan.',
            'file' => UploadedFile::fake()->create('modul.pdf', 120, 'application/pdf'),
        ]);

        $materialResponse->assertRedirect()->assertSessionHasNoErrors();
        $material = LearningMaterial::firstOrFail();
        Storage::disk('local')->assertExists($material->file_path);

        $assignmentResponse = $this->post(route('learning.assignments.store'), [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'title' => 'Latihan Pecahan',
            'instructions' => 'Kerjakan soal berikut.',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_score' => 100,
            'allow_late' => false,
            'revision_limit' => 2,
        ]);

        $assignmentResponse->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('learning_assignments', [
            'title' => 'Latihan Pecahan',
            'revision_limit' => 2,
        ]);
    }

    public function test_student_can_read_material_submit_work_and_teacher_can_review_it(): void
    {
        Storage::fake('local');
        $this->actingAs($this->teacher);

        $material = LearningMaterial::create([
            'school_id' => $this->teacher->school_id,
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'created_by' => $this->teacher->id,
            'title' => 'Materi Uji',
            'published_at' => now(),
        ]);
        $assignment = LearningAssignment::create([
            'school_id' => $this->teacher->school_id,
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'created_by' => $this->teacher->id,
            'title' => 'Tugas Uji',
            'max_score' => 100,
            'allow_late' => true,
            'revision_limit' => 1,
            'published_at' => now(),
        ]);

        $this->actingAs($this->student);
        $this->get(route('learning.index'))->assertOk();
        $this->post(route('learning.materials.read', $material))->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('learning.assignments.submit', $assignment), [
            'content' => 'Jawaban siswa.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $submission = AssignmentSubmission::firstOrFail();
        $this->assertDatabaseHas('learning_material_reads', [
            'learning_material_id' => $material->id,
            'enrollment_id' => Enrollment::whereHas('student', fn ($query) => $query->where('user_id', $this->student->id))->firstOrFail()->id,
        ]);

        $this->actingAs($this->teacher);
        $this->post(route('learning.submissions.grade', $submission), [
            'review_status' => 'needs_revision',
            'feedback' => 'Tambahkan langkah perhitungan.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($this->student);
        $this->post(route('learning.assignments.submit', $assignment), [
            'content' => 'Jawaban siswa setelah diperbaiki.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assignment_submission_revisions', [
            'assignment_submission_id' => $submission->id,
            'revision_number' => 0,
        ]);

        $this->actingAs($this->teacher);
        $this->post(route('learning.submissions.grade', $submission), [
            'review_status' => 'approved',
            'score' => 88,
            'feedback' => 'Baik.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'status' => 'graded',
            'review_status' => 'approved',
            'score' => 88,
        ]);
        $this->get(route('learning.progress.index'))->assertOk();
    }

    public function test_school_admin_cannot_access_lms_authoring_flow_but_can_view_progress(): void
    {
        $admin = User::where('email', 'admin@pilot.sch.id')->firstOrFail();
        $admin->forceFill(['must_change_password' => false])->save();
        $this->actingAs($admin);

        $this->get(route('learning.index'))->assertForbidden();
        $this->get(route('learning.progress.index'))->assertOk();
        $this->post(route('learning.assignments.store'), [
            'teaching_assignment_id' => $this->teachingAssignment->id,
            'title' => 'Tugas Admin',
            'max_score' => 100,
            'allow_late' => true,
            'revision_limit' => 0,
        ])->assertForbidden();
    }
}
