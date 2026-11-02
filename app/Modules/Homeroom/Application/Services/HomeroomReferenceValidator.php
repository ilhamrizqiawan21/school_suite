<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HomeroomReferenceValidator
{
    public function validateStudentAndTerm(int $schoolId, int $studentId, int $termId): void
    {
        if (! DB::table('students')
            ->where('id', $studentId)
            ->where('school_id', $schoolId)
            ->exists()) {
            throw ValidationException::withMessages([
                'student_id' => 'Siswa tidak berada di sekolah akun Anda.',
            ]);
        }

        if (! $this->termAcademicYearId($schoolId, $termId)) {
            throw ValidationException::withMessages([
                'term_id' => 'Semester tidak berada di sekolah akun Anda.',
            ]);
        }
    }

    public function validateStudentTermAndClass(int $schoolId, int $studentId, int $termId, ?int $classGroupId): void
    {
        $termAcademicYearId = $this->termAcademicYearId($schoolId, $termId);

        $this->validateStudentAndTerm($schoolId, $studentId, $termId);

        if ($classGroupId === null) {
            return;
        }

        $classAcademicYearId = DB::table('class_groups')
            ->where('id', $classGroupId)
            ->where('school_id', $schoolId)
            ->value('academic_year_id');

        if ($classAcademicYearId === null || (int) $classAcademicYearId !== (int) $termAcademicYearId) {
            throw ValidationException::withMessages([
                'class_group_id' => 'Kelas tidak sesuai dengan semester sekolah Anda.',
            ]);
        }

        if (! DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('class_group_id', $classGroupId)
            ->exists()) {
            throw ValidationException::withMessages([
                'student_id' => 'Siswa tidak terdaftar pada kelas dan semester tersebut.',
            ]);
        }
    }

    private function termAcademicYearId(int $schoolId, int $termId): ?int
    {
        $academicYearId = DB::table('terms')
            ->join('academic_years', 'academic_years.id', '=', 'terms.academic_year_id')
            ->where('terms.id', $termId)
            ->where('academic_years.school_id', $schoolId)
            ->value('terms.academic_year_id');

        return $academicYearId === null ? null : (int) $academicYearId;
    }
}
