<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Homeroom\Domain\Enums\NoteAccessLevelEnum;
use App\Modules\Homeroom\Infrastructure\Models\StudentNote;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentObservationService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Memeriksa apakah user berhak melihat catatan berkategori confidential untuk siswa tertentu.
     */
    public function canViewConfidential(User $viewer, int $studentId): bool
    {
        if (! DB::table('students')
            ->where('id', $studentId)
            ->where('school_id', $viewer->school_id)
            ->exists()) {
            return false;
        }

        // Admin, Kepala Sekolah, dan Guru BK (counselor) selalu berhak
        if ($viewer->hasRole('admin') || $viewer->hasRole('principal') || $viewer->hasRole('counselor')) {
            return true;
        }

        // Wali kelas dari siswa yang bersangkutan berhak
        if ($viewer->hasRole('homeroom') || $viewer->hasRole('teacher')) {
            $isHomeroomForStudent = DB::table('enrollments')
                ->join('class_groups', 'enrollments.class_group_id', '=', 'class_groups.id')
                ->join('teachers', 'class_groups.homeroom_teacher_id', '=', 'teachers.id')
                ->where('enrollments.student_id', $studentId)
                ->where('class_groups.school_id', $viewer->school_id)
                ->where('teachers.user_id', $viewer->id)
                ->exists();

            if ($isHomeroomForStudent) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mengambil daftar catatan perkembangan siswa dengan penyaringan level akses ketat (SS-F-006).
     *
     * @return Collection<int, StudentNote>
     */
    public function getNotesForStudent(int $studentId, User $viewer): Collection
    {
        if (! DB::table('students')->where('id', $studentId)->where('school_id', $viewer->school_id)->exists()) {
            return collect();
        }

        $allowedLevels = [NoteAccessLevelEnum::PUBLIC->value];

        // Pengguna internal sekolah dapat melihat internal notes
        if ($viewer->hasRole('admin') || $viewer->hasRole('principal') || $viewer->hasRole('operator') ||
            $viewer->hasRole('teacher') || $viewer->hasRole('homeroom') || $viewer->hasRole('counselor')) {
            $allowedLevels[] = NoteAccessLevelEnum::INTERNAL->value;
        }

        // Catatan rahasia (confidential) hanya untuk pihak yang berwenang
        if ($this->canViewConfidential($viewer, $studentId)) {
            $allowedLevels[] = NoteAccessLevelEnum::CONFIDENTIAL->value;
        }

        return StudentNote::where('student_id', $studentId)
            ->where('school_id', $viewer->school_id)
            ->whereIn('access_level', $allowedLevels)
            ->with('author')
            ->latest('id')
            ->get();
    }

    /**
     * Mengambil catatan khusus untuk ekspor rapor / publikasi umum.
     * Secara ketat MENGECUALIKAN seluruh catatan internal dan confidential (SS-F-006).
     *
     * @return Collection<int, StudentNote>
     */
    public function getNotesForReportExport(int $termId, int $classGroupId): Collection
    {
        return StudentNote::where('term_id', $termId)
            ->where('class_group_id', $classGroupId)
            ->where('access_level', NoteAccessLevelEnum::PUBLIC->value)
            ->with('author')
            ->get();
    }

    /**
     * Membuat catatan perkembangan baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function createNote(array $data, User $author): StudentNote
    {
        $schoolId = (int) $author->school_id;
        $termAcademicYearId = DB::table('terms')
            ->join('academic_years', 'academic_years.id', '=', 'terms.academic_year_id')
            ->where('terms.id', $data['term_id'] ?? 0)
            ->where('academic_years.school_id', $schoolId)
            ->value('terms.academic_year_id');
        $studentInSchool = DB::table('students')->where('id', $data['student_id'] ?? 0)->where('school_id', $schoolId)->exists();
        $classAcademicYearId = null;
        if (! empty($data['class_group_id'])) {
            $classAcademicYearId = DB::table('class_groups')
                ->join('academic_years', 'academic_years.id', '=', 'class_groups.academic_year_id')
                ->where('class_groups.id', $data['class_group_id'])
                ->where('class_groups.school_id', $schoolId)
                ->value('class_groups.academic_year_id');
        }

        if (! $studentInSchool) {
            throw ValidationException::withMessages(['student_id' => 'Siswa tidak berada di sekolah akun Anda.']);
        }
        if ($termAcademicYearId === null) {
            throw ValidationException::withMessages(['term_id' => 'Semester tidak berada di sekolah akun Anda.']);
        }
        if (! empty($data['class_group_id']) && ($classAcademicYearId === null || (int) $classAcademicYearId !== (int) $termAcademicYearId)) {
            throw ValidationException::withMessages(['class_group_id' => 'Kelas tidak sesuai dengan semester sekolah Anda.']);
        }

        $data['school_id'] = $schoolId;
        $data['author_id'] = $author->id;
        $data['access_level'] = $data['access_level'] ?? NoteAccessLevelEnum::INTERNAL->value;

        $note = StudentNote::create($data);

        $this->auditLogger->log(
            eventType: 'homeroom.student_note.created',
            targetType: 'student_note',
            targetId: (string) $note->id,
            newValues: [
                'student_id' => $note->student_id,
                'title' => $note->title,
                'category' => $note->category,
                'access_level' => $note->access_level,
            ],
            oldValues: null,
            actor: $author,
            schoolId: $author->school_id
        );

        return $note;
    }

    /**
     * Memperbarui catatan perkembangan.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateNote(StudentNote $note, array $data, User $actor): StudentNote
    {
        $oldValues = $note->toArray();
        $note->update($data);

        $this->auditLogger->log(
            eventType: 'homeroom.student_note.updated',
            targetType: 'student_note',
            targetId: (string) $note->id,
            newValues: [
                'title' => $note->title,
                'category' => $note->category,
                'access_level' => $note->access_level,
            ],
            oldValues: $oldValues,
            actor: $actor,
            schoolId: $note->school_id
        );

        return $note;
    }

    /**
     * Menghapus catatan perkembangan.
     */
    public function deleteNote(StudentNote $note, User $actor): bool
    {
        $oldValues = $note->toArray();
        $id = $note->id;
        $schoolId = $note->school_id;

        $deleted = (bool) $note->delete();

        if ($deleted) {
            $this->auditLogger->log(
                eventType: 'homeroom.student_note.deleted',
                targetType: 'student_note',
                targetId: (string) $id,
                newValues: null,
                oldValues: $oldValues,
                actor: $actor,
                schoolId: $schoolId
            );
        }

        return $deleted;
    }
}
