<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\StaffTask;
use App\Modules\Core\Infrastructure\Models\School;
use Database\Seeders\PilotSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdministrationStaffTasksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotSchoolSeeder::class);
        $this->admin = User::where('email', 'admin@pilot.sch.id')->firstOrFail();
        $this->teacher = User::whereHas('role', fn ($query) => $query->where('name', 'teacher'))->firstOrFail();
        $this->admin->forceFill(['must_change_password' => false])->save();
        $this->teacher->forceFill(['must_change_password' => false])->save();
    }

    public function test_admin_creates_school_scoped_task_and_assignee_updates_progress(): void
    {
        $this->actingAs($this->admin)
            ->post(route('administration.staff-tasks.store'), [
                'title' => 'Siapkan dokumen rapat',
                'description' => 'Kumpulkan dokumen sebelum rapat.',
                'priority' => 'high',
                'assignee_id' => $this->teacher->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $task = StaffTask::firstOrFail();
        $this->assertSame($this->admin->school_id, $task->school_id);
        $this->assertDatabaseHas('administration_staff_task_activities', [
            'task_id' => $task->id,
            'action' => 'created',
        ]);

        $this->actingAs($this->teacher)
            ->get(route('administration.staff-tasks.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Administration/StaffTasks/Index')->has('tasks', 1)->where('can_manage', false));

        $this->actingAs($this->teacher)
            ->patch(route('administration.staff-tasks.progress', $task), ['progress' => 50, 'status' => 'in_progress'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('administration_staff_tasks', ['id' => $task->id, 'progress' => 50, 'status' => 'in_progress']);
        $this->assertDatabaseHas('administration_staff_task_activities', ['task_id' => $task->id, 'action' => 'progress_updated']);
    }

    public function test_task_assignee_must_belong_to_the_same_school(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '88888888', 'level' => 'SMP']);
        $otherTeacher = User::create([
            'school_id' => $otherSchool->id,
            'role_id' => $this->teacher->role_id,
            'name' => 'Guru Sekolah Lain',
            'username' => 'guru_lain_tugas',
            'email' => 'guru.lain.tugas@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('administration.staff-tasks.store'), [
                'title' => 'Tugas lintas sekolah',
                'priority' => 'normal',
                'assignee_id' => $otherTeacher->id,
            ])
            ->assertSessionHasErrors('assignee_id');

        $this->assertDatabaseCount('administration_staff_tasks', 0);
    }

    public function test_teacher_cannot_read_or_change_tasks_assigned_to_another_user_or_school(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Kedua', 'npsn' => '77777777', 'level' => 'SMP']);
        $task = StaffTask::create([
            'school_id' => $otherSchool->id,
            'created_by' => $this->admin->id,
            'assignee_id' => $this->teacher->id,
            'title' => 'Tugas privat',
            'priority' => 'normal',
            'status' => 'assigned',
            'progress' => 0,
        ]);

        $this->actingAs($this->teacher)
            ->get(route('administration.staff-tasks.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('tasks', 0));

        $this->actingAs($this->teacher)
            ->patch(route('administration.staff-tasks.progress', $task), ['progress' => 20, 'status' => 'in_progress'])
            ->assertNotFound();
    }

    public function test_progress_and_completion_state_must_agree(): void
    {
        $task = StaffTask::create([
            'school_id' => $this->admin->school_id,
            'created_by' => $this->admin->id,
            'assignee_id' => $this->teacher->id,
            'title' => 'Tugas progres',
            'priority' => 'normal',
            'status' => 'assigned',
            'progress' => 0,
        ]);

        $this->actingAs($this->teacher)
            ->patch(route('administration.staff-tasks.progress', $task), ['progress' => 100, 'status' => 'in_progress'])
            ->assertStatus(422);

        $this->assertDatabaseHas('administration_staff_tasks', ['id' => $task->id, 'progress' => 0, 'status' => 'assigned']);
    }

    public function test_task_attachment_is_private_and_download_is_audited(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin)
            ->post(route('administration.staff-tasks.store'), [
                'title' => 'Surat rapat',
                'priority' => 'normal',
                'assignee_id' => $this->teacher->id,
                'attachment' => UploadedFile::fake()->create('agenda.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $task = StaffTask::firstOrFail();
        $this->assertStringStartsWith('administration/staff-tasks/', $task->attachment_path);
        Storage::disk('local')->assertExists($task->attachment_path);

        $this->actingAs($this->teacher)
            ->get(route('administration.staff-tasks.attachment', $task))
            ->assertOk();
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->teacher->school_id,
            'event_type' => 'administration.staff_task.attachment_downloaded',
        ]);
    }
}
