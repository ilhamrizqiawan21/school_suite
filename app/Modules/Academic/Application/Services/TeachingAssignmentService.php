<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TeachingAssignmentService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Menetapkan penugasan guru mata pelajaran pada rombongan belajar di semester tertentu.
     */
    public function assignSubjectTeacher(
        int $termId,
        int $classGroupId,
        int $subjectId,
        int $teacherId,
        int $hoursPerWeek = 2,
        ?User $actor = null
    ): TeachingAssignment {
        $term = Term::with('academicYear')->findOrFail($termId);
        $classGroup = ClassGroup::findOrFail($classGroupId);
        $subject = Subject::findOrFail($subjectId);
        $teacher = Teacher::findOrFail($teacherId);

        if ($classGroup->school_id !== $teacher->school_id || $classGroup->school_id !== $subject->school_id) {
            throw new InvalidArgumentException('Guru, mata pelajaran, dan rombongan belajar harus berada di sekolah yang sama.');
        }

        if ($classGroup->academic_year_id !== $term->academic_year_id) {
            throw new InvalidArgumentException('Rombongan belajar tidak sesuai dengan tahun ajaran semester.');
        }

        if (! $teacher->is_active) {
            throw new InvalidArgumentException("Guru [{$teacher->name}] tidak aktif.");
        }

        if (! $subject->is_active) {
            throw new InvalidArgumentException("Mata pelajaran [{$subject->name}] tidak aktif.");
        }

        // Cek duplikasi penugasan
        $exists = TeachingAssignment::where('term_id', $termId)
            ->where('class_group_id', $classGroupId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('Penugasan guru untuk mata pelajaran dan kelas ini sudah ada pada semester yang dipilih.');
        }

        $assignment = TeachingAssignment::create([
            'term_id' => $termId,
            'class_group_id' => $classGroupId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'hours_per_week' => $hoursPerWeek,
        ]);

        $this->auditLogger->log(
            eventType: 'academic.teaching_assignment.created',
            targetType: 'teaching_assignment',
            targetId: (string) $assignment->id,
            newValues: [
                'term_id' => $termId,
                'class_group_id' => $classGroupId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'hours_per_week' => $hoursPerWeek,
            ],
            oldValues: null,
            actor: $actor,
            schoolId: $teacher->school_id
        );

        return $assignment;
    }

    /**
     * Menetapkan wali kelas pada suatu rombongan belajar.
     */
    public function assignHomeroomTeacher(
        int $classGroupId,
        int $teacherId,
        ?User $actor = null
    ): ClassGroup {
        return DB::transaction(function () use ($classGroupId, $teacherId, $actor) {
            $classGroup = ClassGroup::findOrFail($classGroupId);
            $teacher = Teacher::findOrFail($teacherId);

            if ($classGroup->school_id !== $teacher->school_id) {
                throw new InvalidArgumentException('Guru dan rombongan belajar harus berada di sekolah yang sama.');
            }

            if (! $teacher->is_active) {
                throw new InvalidArgumentException("Guru [{$teacher->name}] tidak aktif.");
            }

            // Validasi keunikan: Satu guru tidak boleh menjadi wali kelas di lebih dari satu rombel pada tahun ajaran yang sama
            $alreadyHomeroom = ClassGroup::where('academic_year_id', $classGroup->academic_year_id)
                ->where('homeroom_teacher_id', $teacherId)
                ->where('id', '!=', $classGroupId)
                ->first();

            if ($alreadyHomeroom !== null) {
                throw new InvalidArgumentException("Guru [{$teacher->name}] sudah ditugaskan sebagai wali kelas di rombel [{$alreadyHomeroom->name}].");
            }

            $oldTeacherId = $classGroup->homeroom_teacher_id;
            $classGroup->update(['homeroom_teacher_id' => $teacherId]);

            $this->auditLogger->log(
                eventType: 'academic.homeroom_teacher.assigned',
                targetType: 'class_group',
                targetId: (string) $classGroup->id,
                newValues: ['homeroom_teacher_id' => $teacherId],
                oldValues: ['homeroom_teacher_id' => $oldTeacherId],
                actor: $actor,
                schoolId: $teacher->school_id
            );

            return $classGroup;
        });
    }

    /**
     * Menghapus penugasan mengajar.
     */
    public function removeTeachingAssignment(int $assignmentId, ?User $actor = null): bool
    {
        $assignment = TeachingAssignment::with(['classGroup', 'teacher'])->findOrFail($assignmentId);

        $oldValues = [
            'term_id' => $assignment->term_id,
            'class_group_id' => $assignment->class_group_id,
            'subject_id' => $assignment->subject_id,
            'teacher_id' => $assignment->teacher_id,
        ];

        $schoolId = $assignment->teacher->school_id;
        $id = $assignment->id;

        $deleted = (bool) $assignment->delete();

        if ($deleted) {
            $this->auditLogger->log(
                eventType: 'academic.teaching_assignment.deleted',
                targetType: 'teaching_assignment',
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
