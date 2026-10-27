<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommunicationService
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
    ) {}

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

            $classGroupId = isset($data['class_group_id']) ? (int) $data['class_group_id'] : null;
            $this->audience((int) $author->school_id, $data['audience_role'] ?? null, $classGroupId)
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

        $studentUserIds = collect($this->academicQuery->getClassGroupStudentUserIds($classGroupId));
        $teacherUserIds = collect($this->academicQuery->getClassGroupTeacherUserIds($classGroupId));

        return $userIds->intersect($studentUserIds->merge($teacherUserIds))->map(fn ($id) => (int) $id)->values();
    }
}
