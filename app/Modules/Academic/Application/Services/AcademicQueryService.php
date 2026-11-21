<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\GraduationRecord;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentCard;
use App\Modules\Academic\Infrastructure\Models\StudentGuardian;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

class AcademicQueryService
{
    public function isGraduationDocumentReleased(int $schoolId, int $documentId, ?int $revokedForStudentId, array $snapshot): bool
    {
        $records = GraduationRecord::query()->with('batch')->where('school_id', $schoolId);
        $record = (clone $records)->where('skl_document_id', $documentId)->first();

        if ($record === null && $revokedForStudentId !== null) {
            $records->where('student_id', $revokedForStudentId);

            // Legacy reissues only stored the original SKL number in the ticket snapshot.
            if (isset($snapshot['graduation_record_id'])) {
                $record = $records->find($snapshot['graduation_record_id']);
            } elseif (isset($snapshot['skl_number'])) {
                $matches = $records->where('skl_number', $snapshot['skl_number'])->limit(2)->get();
                $record = $matches->count() === 1 ? $matches->first() : null;
            }
        }

        return $record?->batch?->isReleased() === true;
    }

    public function getLearningTeachingAssignments(int $termId, ?int $teacherUserId = null): Collection
    {
        return TeachingAssignment::with(['subject:id,name,code', 'classGroup:id,name', 'teacher:id,name,user_id'])
            ->where('term_id', $termId)
            ->when($teacherUserId, fn ($query) => $query->whereHas('teacher', fn ($teacher) => $teacher->where('user_id', $teacherUserId)))
            ->get();
    }

    /** @param list<int> $classIds */
    public function getLearningEnrollments(int $termId, array $classIds, ?int $studentUserId = null): Collection
    {
        return Enrollment::with('student:id,user_id,nis,name')->where('term_id', $termId)->where('status', 'active')->whereIn('class_group_id', $classIds)
            ->when($studentUserId, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('user_id', $studentUserId)))
            ->get();
    }

    /**
     * Ambil semester aktif untuk sekolah tertentu.
     */
    public function getActiveTerm(int $schoolId): ?Term
    {
        return Term::with('academicYear:id,name,school_id')
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Ambil daftar tahun pelajaran untuk sekolah tertentu.
     *
     * @return BaseCollection<int, object>
     */
    public function getAcademicYears(int $schoolId): BaseCollection
    {
        return DB::table('academic_years')
            ->where('school_id', $schoolId)
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'is_active', 'start_date', 'end_date']);
    }

    /**
     * Ambil daftar semester milik sekolah tertentu (opsional difilter per tahun pelajaran).
     */
    public function getTermsForSchool(int $schoolId, ?int $academicYearId = null): Collection
    {
        return Term::with('academicYear:id,name,school_id')
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Ambil satu semester milik sekolah tertentu berdasarkan ID.
     */
    public function getTermByIdForSchool(int $schoolId, int $termId): ?Term
    {
        return Term::with('academicYear:id,name,school_id')
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->find($termId);
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
     * Resolve the active student cohort for a school term without exposing Academic models to other modules.
     *
     * @return list<int>
     */
    public function getActiveEnrolledStudentIds(int $schoolId, int $termId, ?int $classGroupId = null): array
    {
        return DB::table('enrollments')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('class_groups', 'class_groups.id', '=', 'enrollments.class_group_id')
            ->join('terms', 'terms.id', '=', 'enrollments.term_id')
            ->join('academic_years', 'academic_years.id', '=', 'terms.academic_year_id')
            ->where('enrollments.term_id', $termId)
            ->where('enrollments.status', 'active')
            ->where('students.school_id', $schoolId)
            ->where('students.status', 'active')
            ->where('class_groups.school_id', $schoolId)
            ->where('academic_years.school_id', $schoolId)
            ->when($classGroupId !== null, fn ($query) => $query->where('enrollments.class_group_id', $classGroupId))
            ->distinct()
            ->orderBy('students.id')
            ->pluck('students.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Resolve student display fields for another module while keeping school scope in Academic.
     *
     * @param  list<int>  $studentIds
     * @return BaseCollection<int, Student>
     */
    public function getStudentSummaries(int $schoolId, array $studentIds): BaseCollection
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->get(['id', 'school_id', 'nis', 'name'])
            ->keyBy('id');
    }

    /** @return list<int> */
    public function getStudentIdsMatchingSearch(int $schoolId, string $search): array
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Resolve term names for another module while enforcing the owning school's boundary.
     *
     * @param  list<int>  $termIds
     * @return BaseCollection<int, Term>
     */
    public function getTermSummaries(int $schoolId, array $termIds): BaseCollection
    {
        return Term::query()
            ->whereIn('id', $termIds)
            ->whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
            ->get(['id', 'name', 'academic_year_id'])
            ->keyBy('id');
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

    /** @param list<int> $classGroupIds
     * @return list<int>
     */
    public function getClassGroupIdsTaughtByTeacher(int $schoolId, int $teacherUserId, array $classGroupIds): array
    {
        return DB::table('teaching_assignments')
            ->join('teachers', 'teaching_assignments.teacher_id', '=', 'teachers.id')
            ->join('class_groups', 'teaching_assignments.class_group_id', '=', 'class_groups.id')
            ->where('teachers.user_id', $teacherUserId)
            ->where('teachers.school_id', $schoolId)
            ->where('class_groups.school_id', $schoolId)
            ->whereIn('teaching_assignments.class_group_id', $classGroupIds)
            ->distinct()
            ->orderBy('teaching_assignments.class_group_id')
            ->pluck('teaching_assignments.class_group_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Ambil ID rombel aktif untuk seorang siswa berdasarkan user_id.
     *
     * @return list<int>
     */
    public function getStudentClassGroupIds(int $studentUserId, ?int $termId = null, ?int $schoolId = null): array
    {
        return Enrollment::query()
            ->whereHas('student', fn ($student) => $student
                ->where('user_id', $studentUserId)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            )
            ->where('status', 'active')
            ->when($termId, fn ($query) => $query->where('term_id', $termId))
            ->distinct()
            ->pluck('class_group_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Ambil daftar ID rombel aktif untuk siswa berdasarkan user ID.
     *
     * @return list<int>
     */
    public function getStudentActiveClassGroupIds(int $studentUserId, ?int $schoolId = null): array
    {
        return $this->getStudentClassGroupIds($studentUserId, null, $schoolId);
    }

    /**
     * Ambil daftar user ID siswa aktif dalam rombel tertentu.
     *
     * @return list<int>
     */
    public function getClassGroupStudentUserIds(int $classGroupId): array
    {
        return Enrollment::query()
            ->where('class_group_id', $classGroupId)
            ->where('status', 'active')
            ->whereHas('student', fn ($student) => $student->whereNotNull('user_id'))
            ->with('student:id,user_id')
            ->get()
            ->pluck('student.user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Ambil daftar user ID guru yang mengajar pada rombel tertentu.
     *
     * @return list<int>
     */
    public function getClassGroupTeacherUserIds(int $classGroupId): array
    {
        return TeachingAssignment::query()
            ->where('class_group_id', $classGroupId)
            ->whereHas('teacher', fn ($teacher) => $teacher->whereNotNull('user_id'))
            ->with('teacher:id,user_id')
            ->get()
            ->pluck('teacher.user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Ambil data enrollment aktif siswa pada semester tertentu.
     */
    public function getStudentEnrollmentForTerm(int $studentUserId, int $termId): ?Enrollment
    {
        return Enrollment::query()
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->whereHas('student', fn ($student) => $student->where('user_id', $studentUserId))
            ->first();
    }

    /**
     * Ambil data enrollment siswa pada kelas dan semester tertentu.
     */
    public function getStudentEnrollmentForClassTerm(int $studentUserId, int $termId, int $classGroupId): ?Enrollment
    {
        return Enrollment::query()
            ->where('term_id', $termId)
            ->where('class_group_id', $classGroupId)
            ->where('status', 'active')
            ->whereHas('student', fn ($student) => $student->where('user_id', $studentUserId))
            ->first();
    }

    /**
     * Ambil penugasan mengajar berdasarkan ID.
     */
    public function getTeachingAssignmentById(int $id): ?TeachingAssignment
    {
        return TeachingAssignment::find($id);
    }

    /**
     * Ambil daftar ID siswa yang terhubung dengan akun wali murid terverifikasi.
     *
     * @return list<int>
     */
    public function getGuardianStudentIdsForUser(int $schoolId, int $userId): array
    {
        return StudentGuardian::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $userId)
            ->where('is_verified', true)
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Ambil daftar ID siswa milik akun siswa itu sendiri dalam sekolah tertentu.
     *
     * @return list<int>
     */
    public function getStudentIdsForUser(int $schoolId, int $userId): array
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $userId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Ambil daftar ID siswa yang berada di kelas perwalian aktif untuk user wali kelas.
     *
     * @return list<int>
     */
    public function getHomeroomStudentIdsForUser(int $schoolId, int $userId): array
    {
        $activeTerm = $this->getActiveTerm($schoolId);
        $teacher = $this->getTeacherByUserId($userId);

        if ($activeTerm === null || $teacher === null || (int) $teacher->school_id !== $schoolId) {
            return [];
        }

        $classGroupIds = ClassGroup::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $activeTerm->academic_year_id)
            ->where('homeroom_teacher_id', $teacher->id)
            ->pluck('id');

        if ($classGroupIds->isEmpty()) {
            return [];
        }

        return Enrollment::query()
            ->where('term_id', $activeTerm->id)
            ->whereIn('class_group_id', $classGroupIds)
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ambil snapshot identitas siswa (beserta kelas & periode aktif) untuk kebutuhan referensi dokumen/tiket.
     *
     * @return array{student_id: int, school_id: int, nis: string, nisn: ?string, name: string, status: string, term_id: ?int, term_name: ?string, class_group_id: ?int, class_group_name: ?string}|null
     */
    public function getStudentIdentitySnapshot(int $schoolId, int $studentId): ?array
    {
        $student = Student::query()
            ->where('school_id', $schoolId)
            ->find($studentId);

        if ($student === null) {
            return null;
        }

        $activeTerm = $this->getActiveTerm($schoolId);
        $enrollment = $activeTerm !== null
            ? Enrollment::query()
                ->with('classGroup:id,name')
                ->where('term_id', $activeTerm->id)
                ->where('student_id', $student->id)
                ->first()
            : null;

        return [
            'student_id' => (int) $student->id,
            'school_id' => (int) $student->school_id,
            'nis' => (string) $student->nis,
            'nisn' => $student->nisn,
            'name' => (string) $student->name,
            'status' => (string) $student->status,
            'term_id' => $activeTerm?->id ? (int) $activeTerm->id : null,
            'term_name' => $activeTerm?->name,
            'class_group_id' => $enrollment?->class_group_id ? (int) $enrollment->class_group_id : null,
            'class_group_name' => $enrollment?->classGroup?->name,
        ];
    }

    /**
     * Public-safe GTK directory returning only approved public attributes (M4-PUB-03).
     * Never exposes NIP, email, phone, or private metadata.
     *
     * @return list<array{id: int, name: string, position: string, employment_status: ?string}>
     */
    public function getPublicDirectoryTeachers(int $schoolId, ?string $search = null, ?string $position = null): array
    {
        $cleanSearch = trim((string) $search);
        $cleanPosition = trim((string) $position);

        return Teacher::query()
            ->with(['teachingAssignments.subject:id,name'])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($cleanSearch !== '', function ($query) use ($cleanSearch): void {
                $query->where('name', 'like', '%'.$cleanSearch.'%');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'employment_status'])
            ->map(static function (Teacher $teacher): array {
                $subjects = $teacher->teachingAssignments
                    ->map(static fn (TeachingAssignment $assignment): ?string => $assignment->subject?->name)
                    ->filter(static fn (?string $name): bool => is_string($name) && trim($name) !== '')
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => (int) $teacher->id,
                    'name' => (string) $teacher->name,
                    'position' => $subjects !== [] ? implode(', ', $subjects) : 'Guru Mata Pelajaran',
                    'employment_status' => $teacher->employment_status ?: null,
                ];
            })
            ->when($cleanPosition !== '', function ($collection) use ($cleanPosition) {
                return $collection->filter(
                    static fn (array $row): bool => mb_strtolower($row['position']) === mb_strtolower($cleanPosition),
                );
            })
            ->values()
            ->all();
    }

    /**
     * Resolve active students owned by a guardian or student user account.
     *
     * @return list<array<string, mixed>>
     */
    public function getStudentsForUserScope(int $schoolId, User $user): array
    {
        $studentIds = [];

        if ($user->hasRole('parent')) {
            $studentIds = $this->getGuardianStudentIdsForUser($schoolId, (int) $user->id);
        } elseif ($user->hasRole('student')) {
            $studentIds = $this->getStudentIdsForUser($schoolId, (int) $user->id);
        }

        if ($studentIds === []) {
            return [];
        }

        return array_values($this->getStudentsSnapshotsByIds($schoolId, $studentIds));
    }

    /**
     * Batch-resolve student snapshots (with active class name & term) keyed by student_id.
     *
     * @param  list<int>  $studentIds
     * @return array<int, array<string, mixed>>
     */
    public function getStudentsSnapshotsByIds(int $schoolId, array $studentIds): array
    {
        $ids = array_values(array_unique(array_filter($studentIds, static fn (mixed $id): bool => (int) $id > 0)));
        if ($ids === []) {
            return [];
        }

        $activeTerm = $this->getActiveTerm($schoolId);
        $students = Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->get(['id', 'school_id', 'nis', 'nisn', 'name', 'status']);

        $enrollments = $activeTerm !== null
            ? Enrollment::query()
                ->with('classGroup:id,name')
                ->where('term_id', $activeTerm->id)
                ->whereIn('student_id', $ids)
                ->get()
                ->keyBy('student_id')
            : collect();

        $map = [];
        foreach ($students as $student) {
            $enr = $enrollments->get($student->id);
            $map[(int) $student->id] = [
                'student_id' => (int) $student->id,
                'school_id' => (int) $student->school_id,
                'nis' => (string) $student->nis,
                'nisn' => $student->nisn,
                'name' => (string) $student->name,
                'status' => (string) $student->status,
                'term_id' => $activeTerm?->id ? (int) $activeTerm->id : null,
                'term_name' => $activeTerm?->name,
                'class_group_id' => $enr?->class_group_id ? (int) $enr->class_group_id : null,
                'class_group_name' => $enr?->classGroup?->name,
            ];
        }

        return $map;
    }

    /**
     * Search active students by name, NIS, NISN, or active card/QR token for staff workflows.
     *
     * @return list<array<string, mixed>>
     */
    public function searchActiveStudentsForStaff(int $schoolId, string $query = '', int $limit = 20): array
    {
        $clean = trim($query);
        $cardStudentIds = [];

        if ($clean !== '') {
            $cardStudentIds = StudentCard::query()
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->where(fn ($q) => $q->where('card_uid', $clean)->orWhere('qr_token', $clean))
                ->pluck('student_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        $studentIds = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($clean !== '', function ($q) use ($clean, $cardStudentIds): void {
                $q->where(function ($sub) use ($clean, $cardStudentIds): void {
                    $sub->where('name', 'like', "%{$clean}%")
                        ->orWhere('nis', 'like', "%{$clean}%")
                        ->orWhere('nisn', 'like', "%{$clean}%");
                    if ($cardStudentIds !== []) {
                        $sub->orWhereIn('id', $cardStudentIds);
                    }
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return array_values($this->getStudentsSnapshotsByIds($schoolId, $studentIds));
    }

    /**
     * Find an active student strictly by an active RFID UID or QR token within the school.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveStudentByCardToken(int $schoolId, string $token): ?array
    {
        $clean = trim($token);
        if ($clean === '') {
            return null;
        }

        /** @var StudentCard|null $card */
        $card = StudentCard::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('card_uid', $clean)->orWhere('qr_token', $clean))
            ->first();

        if ($card === null) {
            return null;
        }

        $snapshot = $this->getStudentIdentitySnapshot($schoolId, (int) $card->student_id);
        if ($snapshot === null || $snapshot['status'] !== 'active') {
            return null;
        }

        return [
            ...$snapshot,
            'card_id' => (int) $card->id,
        ];
    }

    /**
     * Publicly confirm active/graduated/transferred student status when exact NISN + name match,
     * returning strictly minimal attributes without leaking PII (M4-LIF-04).
     *
     * @return array{found: bool, status_label?: string, status_code?: string, student_name?: string, class_or_level?: string, academic_year?: string, message: string}
     */
    public function verifyPublicStudentStatus(int $schoolId, string $nisn, string $nameQuery): array
    {
        $cleanNisn = preg_replace('/\D+/', '', trim($nisn)) ?? '';
        $cleanName = mb_strtolower(trim($nameQuery));

        if ($cleanNisn === '' || $cleanName === '') {
            return [
                'found' => false,
                'message' => 'NISN dan nama siswa wajib diisi untuk verifikasi status siswa.',
            ];
        }

        /** @var Student|null $student */
        $student = Student::query()
            ->where('school_id', $schoolId)
            ->where('nisn', $cleanNisn)
            ->first();

        if ($student === null || ! str_contains(mb_strtolower((string) $student->name), $cleanName)) {
            return [
                'found' => false,
                'message' => 'Data keaktifan siswa tidak ditemukan atau tidak cocok dengan kombinasi NISN dan nama.',
            ];
        }

        $latestEnrollment = Enrollment::query()
            ->with(['classGroup:id,name,grade_level', 'term.academicYear:id,name'])
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        $statusCode = (string) ($student->status ?? 'active');
        $statusLabel = match ($statusCode) {
            'active' => 'Siswa Aktif',
            'graduated' => 'Lulus / Alumni',
            'transferred' => 'Mutasi Keluar',
            'dropped_out' => 'Nonaktif / Berhenti',
            default => ucfirst($statusCode),
        };

        return [
            'found' => true,
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'student_name' => (string) $student->name,
            'class_or_level' => $latestEnrollment?->classGroup?->name
                ? sprintf('%s (Tingkat %s)', $latestEnrollment->classGroup->name, (string) $latestEnrollment->classGroup->grade_level)
                : '-',
            'academic_year' => (string) ($latestEnrollment?->term?->academicYear?->name ?? '-'),
            'message' => 'Data keaktifan siswa ditemukan dan terkonfirmasi.',
        ];
    }

    /**
     * Publicly aggregated student and class-group counts without exposing student PII.
     *
     * @return array{
     *     academic_year: string,
     *     term_name: string,
     *     active_students_count: int,
     *     active_classes_count: int,
     *     grade_levels: list<array{grade_level: int, students_count: int, classes: list<array{id: int, name: string, students_count: int}>}>
     * }
     */
    public function publicStudentSummary(int $schoolId): array
    {
        $activeTerm = Term::query()
            ->with('academicYear:id,name')
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $activeStudentsCount = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        $classGroups = ClassGroup::query()
            ->where('school_id', $schoolId)
            ->when($activeTerm !== null, fn ($q) => $q->where('academic_year_id', $activeTerm->academic_year_id))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);

        $enrollmentCounts = $activeTerm !== null
            ? Enrollment::query()
                ->select('class_group_id', DB::raw('COUNT(*) as total'))
                ->where('term_id', $activeTerm->id)
                ->where('status', 'active')
                ->groupBy('class_group_id')
                ->pluck('total', 'class_group_id')
            : collect();

        $gradeLevels = $classGroups
            ->groupBy('grade_level')
            ->map(function (BaseCollection $groups, int|string $gradeLevel) use ($enrollmentCounts): array {
                $classes = $groups->map(fn (ClassGroup $cg): array => [
                    'id' => (int) $cg->id,
                    'name' => (string) $cg->name,
                    'students_count' => (int) ($enrollmentCounts->get($cg->id) ?? 0),
                ])->values()->all();

                return [
                    'grade_level' => (int) $gradeLevel,
                    'students_count' => array_sum(array_column($classes, 'students_count')),
                    'classes' => $classes,
                ];
            })
            ->values()
            ->all();

        return [
            'academic_year' => (string) ($activeTerm?->academicYear?->name ?? '2026/2027'),
            'term_name' => (string) ($activeTerm?->name ?? 'Semester Aktif'),
            'active_students_count' => $activeStudentsCount,
            'active_classes_count' => $classGroups->count(),
            'grade_levels' => $gradeLevels,
        ];
    }
}
