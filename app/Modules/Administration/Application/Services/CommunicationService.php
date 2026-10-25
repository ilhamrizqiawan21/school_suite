<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommunicationService
{
    /** @param array<string, mixed> $data */
    public function publishAnnouncement(User $author, array $data): Announcement
    {
        return DB::transaction(function () use ($author, $data) {
            $announcement = Announcement::create([
                ...$data,
                'school_id' => $author->school_id,
                'author_id' => $author->id,
                'published_at' => now(),
            ]);

            $this->audience($author->school_id, $data['audience_role'] ?? null, $data['class_group_id'] ?? null)
                ->each(fn (int $userId) => $announcement->notifications()->create([
                    'user_id' => $userId,
                    'title' => $announcement->title,
                    'body' => mb_strimwidth(strip_tags($announcement->body), 0, 240, '…'),
                    'action_url' => '/komunikasi?tab=announcements',
                ]));

            return $announcement;
        });
    }

    /** @return Collection<int, int> */
    private function audience(int $schoolId, ?string $role, ?int $classGroupId): Collection
    {
        $query = User::query()->where('school_id', $schoolId)->where('is_active', true);

        if ($role) {
            $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $role));
        }

        $userIds = $query->pluck('id');

        if (! $classGroupId) {
            return $userIds->map(fn ($id) => (int) $id);
        }

        $studentUserIds = Enrollment::query()
            ->where('class_group_id', $classGroupId)
            ->where('status', 'active')
            ->whereHas('student', fn ($student) => $student->whereNotNull('user_id'))
            ->with('student:id,user_id')
            ->get()
            ->pluck('student.user_id');

        $teacherUserIds = TeachingAssignment::query()
            ->where('class_group_id', $classGroupId)
            ->whereHas('teacher', fn ($teacher) => $teacher->whereNotNull('user_id'))
            ->with('teacher:id,user_id')
            ->get()
            ->pluck('teacher.user_id');

        return $userIds->intersect($studentUserIds->merge($teacherUserIds))->map(fn ($id) => (int) $id)->values();
    }
}
