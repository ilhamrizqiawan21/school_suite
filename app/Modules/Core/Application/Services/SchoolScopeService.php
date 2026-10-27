<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Infrastructure\Models\School;

class SchoolScopeService
{
    /** Lock a school row to serialize school-scoped sequences inside a transaction. */
    public function lockSchool(int $schoolId): void
    {
        abort_unless(School::query()->whereKey($schoolId)->lockForUpdate()->exists(), 404);
    }

    /** @return array{id:int, name:string, legal_name:?string, npsn:?string, address:?string, principal_name:?string} */
    public function getSchoolDetails(int $schoolId): array
    {
        $school = School::query()->findOrFail($schoolId);

        return [
            'id' => (int) $school->id,
            'name' => (string) $school->name,
            'legal_name' => $school->legal_name,
            'npsn' => $school->npsn,
            'address' => $school->address,
            'principal_name' => $school->principal_name,
        ];
    }

    /** @return array{name:?string, legal_name:?string, npsn:?string, address:?string, phone:?string, email:?string, website:?string} */
    public function getSchoolLegalSnapshot(int $schoolId): array
    {
        $school = School::query()->findOrFail($schoolId);

        return $school->only(['name', 'legal_name', 'npsn', 'address', 'phone', 'email', 'website']);
    }
}
