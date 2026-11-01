<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Infrastructure\Models\SchoolOrganizationMember;

final class SchoolOrganizationTreeService
{
    /** @return list<array<string, mixed>> */
    public function rowsForSchool(int $schoolId, bool $publishedOnly = false): array
    {
        $members = SchoolOrganizationMember::query()
            ->where('school_id', $schoolId)
            ->when($publishedOnly, fn ($query) => $query->where('is_published', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $children = $members->groupBy(fn (SchoolOrganizationMember $member): int => (int) ($member->parent_id ?? 0));
        $visited = [];
        $rows = [];

        $visit = function (int $parentId, int $depth) use (&$visit, &$visited, &$rows, $children): void {
            foreach ($children->get($parentId, collect()) as $member) {
                if (isset($visited[$member->id])) {
                    continue;
                }

                $visited[$member->id] = true;
                $rows[] = $this->present($member, $depth);
                $visit((int) $member->id, $depth + 1);
            }
        };

        $visit(0, 0);
        foreach ($members as $member) {
            if (! isset($visited[$member->id])) {
                $visited[$member->id] = true;
                $rows[] = $this->present($member, 0);
            }
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function present(SchoolOrganizationMember $member, int $depth): array
    {
        return [
            'id' => $member->id,
            'parent_id' => $member->parent_id,
            'name' => $member->name,
            'position' => $member->position,
            'department' => $member->department,
            'photo_path' => $member->photo_path,
            'sort_order' => $member->sort_order,
            'is_published' => $member->is_published,
            'depth' => $depth,
        ];
    }
}
