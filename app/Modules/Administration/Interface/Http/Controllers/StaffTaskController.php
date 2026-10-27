<?php

declare(strict_types=1);

namespace App\Modules\Administration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\Application\Policies\StaffTaskPolicy;
use App\Modules\Administration\Application\Services\StaffTaskService;
use App\Modules\Administration\Infrastructure\Models\StaffTask;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffTaskController extends Controller
{
    public function index(Request $request, StaffTaskPolicy $policy): Response
    {
        Gate::authorize('viewAny', [StaffTask::class]);
        $actor = $request->user();
        $schoolId = (int) $actor->school_id;
        $canManage = $policy->create($actor);

        $tasks = StaffTask::query()
            ->where('school_id', $schoolId)
            ->when(! $canManage, fn ($query) => $query->where('assignee_id', $actor->id))
            ->with(['creator:id,name', 'assignee:id,name', 'activities.actor:id,name'])
            ->latest()
            ->limit(100)
            ->get();

        $assignees = $canManage
            ? User::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->whereNotIn('name', ['student', 'parent']))
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return Inertia::render('Administration/StaffTasks/Index', [
            'tasks' => $tasks,
            'assignees' => $assignees,
            'can_manage' => $canManage,
            'current_user_id' => (int) $actor->id,
        ]);
    }

    public function store(Request $request, StaffTaskService $service, PrivateStorageService $privateStorage): RedirectResponse
    {
        Gate::authorize('create', StaffTask::class);
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:60'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'extensions:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
        $assignee = isset($data['assignee_id']) ? User::query()->where('school_id', $schoolId)->where('is_active', true)->with('role')->findOrFail($data['assignee_id']) : null;
        abort_if($assignee && $assignee->hasRole(['student', 'parent']), 422, 'Penanggung jawab harus merupakan staf sekolah yang aktif.');

        $path = $this->storeAttachment($request, $privateStorage);
        if ($path) {
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $this->safeOriginalName($request);
        }
        unset($data['attachment']);
        try {
            $service->create($schoolId, $request->user(), $data);
        } catch (\Throwable $exception) {
            if ($path) {
                $privateStorage->delete($path);
            }
            throw $exception;
        }

        return back()->with('message', 'Tugas staf berhasil dibuat.');
    }

    public function update(Request $request, int $task, StaffTaskService $service, PrivateStorageService $privateStorage): RedirectResponse
    {
        $staffTask = StaffTask::query()->where('school_id', $request->user()->school_id)->findOrFail($task);
        Gate::authorize('manage', $staffTask);
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:60'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('school_id', $schoolId)->where('is_active', true)],
            'status' => ['required', Rule::in(['assigned', 'in_progress', 'completed', 'cancelled'])],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'extensions:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        $oldAttachmentPath = null;
        $path = $this->storeAttachment($request, $privateStorage);
        if ($path) {
            $oldAttachmentPath = $staffTask->attachment_path;
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $this->safeOriginalName($request);
        }
        unset($data['attachment']);
        $assignee = isset($data['assignee_id']) ? User::query()->where('school_id', $schoolId)->where('is_active', true)->with('role')->findOrFail($data['assignee_id']) : null;
        abort_if($assignee && $assignee->hasRole(['student', 'parent']), 422, 'Penanggung jawab harus merupakan staf sekolah yang aktif.');
        $data['completed_at'] = $data['status'] === 'completed' ? ($staffTask->completed_at ?? now()) : null;
        $data['progress'] = match ($data['status']) {
            'assigned' => 0,
            'in_progress' => min(max((int) $staffTask->progress, 1), 99),
            'completed' => 100,
            default => min((int) $staffTask->progress, 99),
        };
        try {
            $service->update($staffTask, $request->user(), $data);
        } catch (\Throwable $exception) {
            if ($path) {
                $privateStorage->delete($path);
            }
            throw $exception;
        }
        if ($oldAttachmentPath) {
            $privateStorage->delete($oldAttachmentPath);
        }

        return back()->with('message', 'Tugas staf berhasil diperbarui.');
    }

    public function updateProgress(Request $request, int $task, StaffTaskService $service): RedirectResponse
    {
        $staffTask = StaffTask::query()->where('school_id', $request->user()->school_id)->findOrFail($task);
        Gate::authorize('updateProgress', $staffTask);
        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['assigned', 'in_progress', 'completed'])],
        ]);
        $consistentProgress = match ($data['status']) {
            'assigned' => $data['progress'] === 0,
            'in_progress' => $data['progress'] > 0 && $data['progress'] < 100,
            'completed' => $data['progress'] === 100,
        };
        abort_unless($consistentProgress, 422, 'Progres harus sesuai dengan status tugas: ditugaskan 0%, berjalan 1–99%, selesai 100%.');

        $service->updateProgress($staffTask, $request->user(), $data['progress'], $data['status']);

        return back()->with('message', 'Progres tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, int $task, StaffTaskService $service): RedirectResponse
    {
        $staffTask = StaffTask::query()->where('school_id', $request->user()->school_id)->findOrFail($task);
        Gate::authorize('manage', $staffTask);
        $service->delete($staffTask, $request->user());

        return back()->with('message', 'Tugas staf berhasil dihapus.');
    }

    public function downloadAttachment(Request $request, int $task, StaffTaskService $service): StreamedResponse
    {
        $staffTask = StaffTask::query()->where('school_id', $request->user()->school_id)->findOrFail($task);
        $actor = $request->user();
        abort_unless($actor->is_active && $staffTask->attachment_path && (
            $actor->hasRole(['admin', 'operator'])
            || ((int) $staffTask->assignee_id === (int) $actor->id && $actor->hasRole(['principal', 'teacher', 'homeroom', 'counselor', 'librarian']))
        ), 404);
        abort_unless(Storage::disk('local')->exists($staffTask->attachment_path), 404);
        $service->logAttachmentDownload($staffTask, $actor);

        return Storage::disk('local')->download($staffTask->attachment_path, $staffTask->attachment_name);
    }

    private function storeAttachment(Request $request, PrivateStorageService $privateStorage): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');
        $path = 'administration/staff-tasks/'.Str::uuid().'.'.$file->extension();
        abort_unless($privateStorage->put($path, $file->getContent()), 500, 'Lampiran tugas gagal disimpan.');

        return $path;
    }

    private function safeOriginalName(Request $request): string
    {
        $name = basename(str_replace('\\', '/', (string) $request->file('attachment')?->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return Str::limit($name !== '' ? $name : 'lampiran', 255, '');
    }
}
