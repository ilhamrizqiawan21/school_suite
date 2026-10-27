<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\StaffTask;
use App\Modules\Administration\Infrastructure\Models\StaffTaskActivity;
use App\Shared\Audit\AuditLogger;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StaffTaskService
{
    public function __construct(private readonly AuditLogger $audit, private readonly PrivateStorageService $privateStorage) {}

    /** @param array<string, mixed> $data */
    public function create(int $schoolId, User $actor, array $data): StaffTask
    {
        return DB::transaction(function () use ($schoolId, $actor, $data): StaffTask {
            abort_unless((int) $actor->school_id === $schoolId && $actor->is_active && $actor->hasRole(['admin', 'operator']), 403);
            if (! empty($data['assignee_id'])) {
                $this->assertValidAssignee($schoolId, (int) $data['assignee_id']);
            }
            $task = StaffTask::create([
                ...$data,
                'school_id' => $schoolId,
                'created_by' => $actor->id,
                'status' => 'assigned',
                'progress' => 0,
            ]);

            $this->record($task, $actor, 'created', ['title' => $task->title, 'assignee_id' => $task->assignee_id]);
            $this->audit->log('administration.staff_task.created', 'staff_task', (string) $task->id, Arr::only($task->getAttributes(), ['title', 'assignee_id', 'priority', 'due_at', 'attachment_name']), actor: $actor, schoolId: $schoolId);

            return $task;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(StaffTask $task, User $actor, array $data): void
    {
        DB::transaction(function () use ($task, $actor, $data): void {
            abort_unless((int) $actor->school_id === (int) $task->school_id && $actor->is_active && $actor->hasRole(['admin', 'operator']), 403);
            if (array_key_exists('assignee_id', $data) && $data['assignee_id'] !== null) {
                $this->assertValidAssignee((int) $task->school_id, (int) $data['assignee_id']);
            }
            $before = Arr::only($task->getAttributes(), array_keys($data));
            $task->update($data);
            $after = Arr::only($task->fresh()->getAttributes(), array_keys($data));

            $this->record($task, $actor, 'updated', ['before' => $before, 'after' => $after]);
            $this->audit->log('administration.staff_task.updated', 'staff_task', (string) $task->id, $after, $before, $actor, (int) $task->school_id);
        });
    }

    public function updateProgress(StaffTask $task, User $actor, int $progress, string $status): void
    {
        DB::transaction(function () use ($task, $actor, $progress, $status): void {
            abort_unless((int) $actor->school_id === (int) $task->school_id && (int) $task->assignee_id === (int) $actor->id && $actor->is_active && $actor->hasRole(['principal', 'teacher', 'homeroom', 'counselor', 'librarian']), 403);
            $before = ['progress' => $task->progress, 'status' => $task->status];
            $completedAt = $status === 'completed' ? ($task->completed_at ?? now()) : null;
            $task->update(['progress' => $progress, 'status' => $status, 'completed_at' => $completedAt]);
            $after = ['progress' => $task->progress, 'status' => $task->status];

            $this->record($task, $actor, 'progress_updated', ['before' => $before, 'after' => $after]);
            $this->audit->log('administration.staff_task.progress_updated', 'staff_task', (string) $task->id, $after, $before, $actor, (int) $task->school_id);
        });
    }

    public function delete(StaffTask $task, User $actor): void
    {
        $attachmentPath = $task->attachment_path;
        DB::transaction(function () use ($task, $actor): void {
            abort_unless((int) $actor->school_id === (int) $task->school_id && $actor->is_active && $actor->hasRole(['admin', 'operator']), 403);
            $this->audit->log('administration.staff_task.deleted', 'staff_task', (string) $task->id, ['title' => $task->title], actor: $actor, schoolId: (int) $task->school_id);
            $task->delete();
        });
        if ($attachmentPath) {
            $this->privateStorage->delete($attachmentPath);
        }
    }

    public function logAttachmentDownload(StaffTask $task, User $actor): void
    {
        abort_unless($actor->is_active && (int) $actor->school_id === (int) $task->school_id && $task->attachment_path, 404);
        abort_unless($actor->hasRole(['admin', 'operator'])
            || ((int) $task->assignee_id === (int) $actor->id && $actor->hasRole(['principal', 'teacher', 'homeroom', 'counselor', 'librarian'])), 404);

        $this->audit->log('administration.staff_task.attachment_downloaded', 'staff_task', (string) $task->id, ['attachment_name' => $task->attachment_name], actor: $actor, schoolId: (int) $task->school_id);
    }

    /** @param array<string, mixed> $changes */
    private function record(StaffTask $task, User $actor, string $action, array $changes): void
    {
        StaffTaskActivity::create([
            'school_id' => $task->school_id,
            'task_id' => $task->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'changes' => $changes,
        ]);
    }

    private function assertValidAssignee(int $schoolId, int $userId): void
    {
        $assignee = User::query()->with('role')->where('school_id', $schoolId)->where('is_active', true)->findOrFail($userId);
        abort_if($assignee->hasRole(['student', 'parent']), 422, 'Penanggung jawab harus merupakan staf sekolah yang aktif.');
    }
}
