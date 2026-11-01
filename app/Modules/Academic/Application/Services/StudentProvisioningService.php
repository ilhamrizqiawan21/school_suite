<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Modules\Academic\Infrastructure\Models\Student;

final class StudentProvisioningService
{
    /**
     * Create the initial Academic record for a confirmed admission.
     * Admissions communicates with Academic through this application boundary.
     *
     * @param  array{school_id:int,nis:string,nisn:?string,name:string,gender:string,birth_date:mixed,parent_phone:?string,parent_name:?string}  $data
     */
    public function createFromAdmission(array $data): Student
    {
        return Student::create([
            ...$data,
            'status' => 'active',
        ]);
    }

    public function findBySchoolAndNis(int $schoolId, string $nis): ?Student
    {
        return Student::query()->where('school_id', $schoolId)->where('nis', $nis)->first();
    }
}
