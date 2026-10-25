<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Domain\Enums\EnrollmentStatusEnum;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLifecycleEvent;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EnrollmentService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Mendaftarkan satu siswa ke kelas pada semester/term tertentu.
     */
    public function enrollStudent(
        int $termId,
        int $classGroupId,
        int $studentId,
        ?int $rollNumber = null,
        ?int $actorId = null
    ): Enrollment {
        $term = Term::with('academicYear')->findOrFail($termId);
        $classGroup = ClassGroup::findOrFail($classGroupId);
        $student = Student::findOrFail($studentId);

        // Validasi sekolah
        if ($classGroup->school_id !== $student->school_id) {
            throw new InvalidArgumentException('Siswa dan rombongan belajar harus berada di sekolah yang sama.');
        }

        if ($classGroup->academic_year_id !== $term->academic_year_id) {
            throw new InvalidArgumentException('Rombongan belajar tidak sesuai dengan tahun ajaran semester.');
        }

        // Cek apakah siswa sudah terdaftar pada term ini
        $existing = Enrollment::where('term_id', $termId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing !== null) {
            throw new InvalidArgumentException("Siswa [{$student->name}] sudah terdaftar di kelas lain pada semester ini.");
        }

        $activeEnrollmentCount = Enrollment::where('term_id', $termId)
            ->where('class_group_id', $classGroupId)
            ->where('status', EnrollmentStatusEnum::ACTIVE->value)
            ->count();

        if ($activeEnrollmentCount >= $classGroup->capacity) {
            throw new InvalidArgumentException("Rombongan belajar [{$classGroup->name}] sudah mencapai kapasitas maksimal.");
        }

        // Hitung nomor absen jika tidak ditentukan
        if ($rollNumber === null) {
            $maxRoll = (int) Enrollment::where('term_id', $termId)
                ->where('class_group_id', $classGroupId)
                ->max('roll_number');
            $rollNumber = $maxRoll + 1;
        }

        $enrollment = Enrollment::create([
            'term_id' => $termId,
            'class_group_id' => $classGroupId,
            'student_id' => $studentId,
            'roll_number' => $rollNumber,
            'status' => EnrollmentStatusEnum::ACTIVE->value,
        ]);

        $this->auditLogger->log(
            eventType: 'academic.enrollment.created',
            targetType: 'enrollment',
            targetId: (string) $enrollment->id,
            newValues: [
                'term_id' => $termId,
                'class_group_id' => $classGroupId,
                'student_id' => $studentId,
                'roll_number' => $rollNumber,
                'status' => $enrollment->status,
            ],
            oldValues: null,
            actor: null,
            schoolId: $student->school_id
        );

        return $enrollment;
    }

    /**
     * Mendaftarkan batch siswa ke dalam kelas pada semester tertentu secara transaksional.
     *
     * @param  list<int>  $studentIds
     * @return Collection<int, Enrollment>
     */
    public function enrollStudentsBatch(
        int $termId,
        int $classGroupId,
        array $studentIds,
        ?int $actorId = null
    ): Collection {
        return DB::transaction(function () use ($termId, $classGroupId, $studentIds, $actorId) {
            $enrollments = collect();

            foreach ($studentIds as $studentId) {
                $enrollments->push(
                    $this->enrollStudent($termId, $classGroupId, $studentId, null, $actorId)
                );
            }

            return $enrollments;
        });
    }

    /**
     * Kenaikan kelas atau perpindahan semester (SS-D-004: Mempertahankan histori enrollment).
     *
     * @param  list<array{student_id: int, target_class_group_id: int, roll_number?: ?int}>  $promotions
     * @return Collection<int, Enrollment>
     */
    public function promoteOrAdvanceStudents(
        int $sourceTermId,
        int $targetTermId,
        array $promotions,
        ?int $actorId = null
    ): Collection {
        return DB::transaction(function () use ($sourceTermId, $targetTermId, $promotions, $actorId) {
            $newEnrollments = collect();

            foreach ($promotions as $promo) {
                $studentId = $promo['student_id'];
                $targetClassId = $promo['target_class_group_id'];
                $rollNumber = $promo['roll_number'] ?? null;

                // Verifikasi keberadaan enrollment di source term
                $sourceEnrollment = Enrollment::where('term_id', $sourceTermId)
                    ->where('student_id', $studentId)
                    ->first();

                if (! $sourceEnrollment) {
                    throw new InvalidArgumentException("Histori enrollment siswa ID [{$studentId}] pada semester sumber tidak ditemukan.");
                }

                // Buat enrollment baru di target term tanpa menyentuh source enrollment
                $newEnrollment = $this->enrollStudent(
                    termId: $targetTermId,
                    classGroupId: $targetClassId,
                    studentId: $studentId,
                    rollNumber: $rollNumber,
                    actorId: $actorId
                );

                $newEnrollments->push($newEnrollment);
            }

            return $newEnrollments;
        });
    }

    /**
     * Memindahkan siswa ke kelas lain dalam semester yang sama.
     */
    public function transferClass(
        int $termId,
        int $studentId,
        int $newClassGroupId,
        ?int $actorId = null
    ): Enrollment {
        return DB::transaction(function () use ($termId, $studentId, $newClassGroupId) {
            $enrollment = Enrollment::where('term_id', $termId)
                ->where('student_id', $studentId)
                ->firstOrFail();

            $targetClass = ClassGroup::findOrFail($newClassGroupId);
            $student = Student::findOrFail($studentId);

            if ($targetClass->school_id !== $student->school_id) {
                throw new InvalidArgumentException('Kelas tujuan tidak berada di sekolah yang sama.');
            }

            $oldValues = [
                'class_group_id' => $enrollment->class_group_id,
                'roll_number' => $enrollment->roll_number,
            ];

            $maxRoll = (int) Enrollment::where('term_id', $termId)
                ->where('class_group_id', $newClassGroupId)
                ->max('roll_number');

            $enrollment->update([
                'class_group_id' => $newClassGroupId,
                'roll_number' => $maxRoll + 1,
            ]);

            $this->auditLogger->log(
                eventType: 'academic.enrollment.transferred',
                targetType: 'enrollment',
                targetId: (string) $enrollment->id,
                newValues: [
                    'class_group_id' => $newClassGroupId,
                    'roll_number' => $enrollment->roll_number,
                ],
                oldValues: $oldValues,
                actor: null,
                schoolId: $student->school_id
            );

            return $enrollment;
        });
    }

    /**
     * @param  array{enrollment_id:int,action:string,target_term_id?:?int,target_class_group_id?:?int,effective_date:string,notes?:?string}  $data
     */
    public function processLifecycle(array $data, User $actor): StudentLifecycleEvent
    {
        return DB::transaction(function () use ($data, $actor): StudentLifecycleEvent {
            $source = Enrollment::with(['student', 'term.academicYear', 'classGroup'])->lockForUpdate()->findOrFail($data['enrollment_id']);
            if ((int) $source->student->school_id !== (int) $actor->school_id || $source->status !== EnrollmentStatusEnum::ACTIVE->value) {
                throw new InvalidArgumentException('Enrollment aktif siswa tidak valid untuk sekolah ini.');
            }

            $target = null;
            if (in_array($data['action'], ['promoted', 'retained'], true)) {
                $targetTerm = Term::with('academicYear')->findOrFail($data['target_term_id']);
                $targetClass = ClassGroup::findOrFail($data['target_class_group_id']);
                if ((int) $targetTerm->academicYear->school_id !== (int) $actor->school_id || (int) $targetClass->school_id !== (int) $actor->school_id || (int) $targetClass->academic_year_id !== (int) $targetTerm->academic_year_id) {
                    throw new InvalidArgumentException('Periode atau kelas tujuan tidak sesuai sekolah dan tahun ajaran.');
                }
                if ($targetTerm->id === $source->term_id || $targetTerm->start_date->lessThanOrEqualTo($source->term->start_date)) {
                    throw new InvalidArgumentException('Periode tujuan harus setelah periode sumber.');
                }
                if ($data['action'] === 'promoted' && $targetClass->grade_level <= $source->classGroup->grade_level) {
                    throw new InvalidArgumentException('Kenaikan kelas harus menuju tingkat yang lebih tinggi.');
                }
                if ($data['action'] === 'retained' && $targetClass->grade_level !== $source->classGroup->grade_level) {
                    throw new InvalidArgumentException('Tinggal kelas harus tetap pada tingkat yang sama.');
                }
                $target = $this->enrollStudent((int) $targetTerm->id, (int) $targetClass->id, (int) $source->student_id, actorId: (int) $actor->id);
                $source->update(['status' => $data['action']]);
            } elseif ($data['action'] === 'graduated') {
                $source->update(['status' => EnrollmentStatusEnum::GRADUATED->value]);
                $source->student->update(['status' => 'graduated']);
            } else {
                $source->update(['status' => EnrollmentStatusEnum::WITHDRAWN->value]);
                $source->student->update(['status' => 'dropped_out']);
            }

            $event = StudentLifecycleEvent::create(['school_id' => $actor->school_id, 'student_id' => $source->student_id, 'source_enrollment_id' => $source->id, 'target_enrollment_id' => $target?->id, 'action' => $data['action'], 'effective_date' => $data['effective_date'], 'notes' => $data['notes'] ?? null, 'recorded_by' => $actor->id]);
            $this->auditLogger->log('academic.student_lifecycle.processed', 'student_lifecycle_event', (string) $event->id, $event->only(['student_id', 'source_enrollment_id', 'target_enrollment_id', 'action', 'effective_date']), actor: $actor, schoolId: (int) $actor->school_id);

            return $event;
        });
    }
}
