<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Modules\Academic\Domain\Enums\EnrollmentStatusEnum;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
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
}
