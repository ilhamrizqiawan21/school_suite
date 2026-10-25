<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

class AcademicQueryService
{
    /**
     * Ambil semester aktif untuk sekolah tertentu.
     */
    public function getActiveTerm(int $schoolId): ?Term
    {
        return Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Ambil data guru berdasarkan user ID.
     */
    public function getTeacherByUserId(int $userId): ?Teacher
    {
        return Teacher::where('user_id', $userId)->first();
    }

    /**
     * Ambil rombel perwalian untuk seorang guru pada tahun ajaran tertentu.
     */
    public function getHomeroomClassGroup(int $schoolId, int $teacherId, int $academicYearId): ?ClassGroup
    {
        return ClassGroup::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('homeroom_teacher_id', $teacherId)
            ->first();
    }

    /**
     * Ambil daftar rombel dalam tahun ajaran tertentu.
     */
    public function getClassGroups(int $schoolId, ?int $academicYearId = null): Collection
    {
        return ClassGroup::where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->with('homeroomTeacher')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ambil rombel berdasarkan ID.
     */
    public function getClassGroupById(int $classGroupId): ?ClassGroup
    {
        return ClassGroup::with('homeroomTeacher')->find($classGroupId);
    }

    /**
     * Ambil daftar siswa (berdasarkan kelas atau limit untuk sekolah).
     */
    public function getStudents(int $schoolId, ?int $classGroupId = null, int $limit = 50): Collection
    {
        return $classGroupId
            ? Student::where('school_id', $schoolId)
                ->whereHas('enrollments', fn ($q) => $q->where('class_group_id', $classGroupId))
                ->orderBy('name')
                ->get(['id', 'name', 'nis'])
            : Student::where('school_id', $schoolId)
                ->orderBy('name')
                ->take($limit)
                ->get(['id', 'name', 'nis']);
    }

    /**
     * Ambil data enrollment untuk rekap presensi kelas pada semester tertentu.
     *
     * @return BaseCollection<int, object>
     */
    public function getClassEnrollmentsForAttendance(int $termId, int $classGroupId): BaseCollection
    {
        return DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->where('enrollments.term_id', $termId)
            ->where('enrollments.class_group_id', $classGroupId)
            ->select([
                'enrollments.id as enrollment_id',
                'students.id as student_id',
                'students.nis',
                'students.name',
                'students.gender',
                'enrollments.roll_number',
            ])
            ->orderBy('enrollments.roll_number')
            ->get();
    }

    /**
     * Ambil rekap status presensi berdasarkan ID enrollment.
     *
     * @param  list<int>  $enrollmentIds
     * @return BaseCollection<int, object>
     */
    public function getAttendanceAggregates(array $enrollmentIds): BaseCollection
    {
        return DB::table('attendance_records')
            ->whereIn('enrollment_id', $enrollmentIds)
            ->select('enrollment_id', 'status', DB::raw('count(*) as count'))
            ->groupBy('enrollment_id', 'status')
            ->get();
    }

    /**
     * Ambil penugasan mengajar / daftar mapel untuk kelas tertentu pada semester aktif.
     *
     * @return BaseCollection<int, object>
     */
    public function getTeachingAssignmentsForClass(int $classGroupId, int $termId): BaseCollection
    {
        return DB::table('teaching_assignments')
            ->join('subjects', 'teaching_assignments.subject_id', '=', 'subjects.id')
            ->join('teachers', 'teaching_assignments.teacher_id', '=', 'teachers.id')
            ->where('teaching_assignments.class_group_id', $classGroupId)
            ->where('teaching_assignments.term_id', $termId)
            ->select([
                'teaching_assignments.id as teaching_assignment_id',
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'teachers.name as teacher_name',
            ])
            ->orderBy('subjects.name')
            ->get();
    }
}
