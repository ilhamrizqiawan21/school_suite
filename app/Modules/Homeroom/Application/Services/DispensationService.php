<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Homeroom\Domain\Enums\DispensationStatusEnum;
use App\Modules\Homeroom\Domain\Enums\DispensationTypeEnum;
use App\Modules\Homeroom\Infrastructure\Models\AttendanceDispensation;
use App\Shared\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DispensationService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Mencatat surat dokter, permohonan izin atau dispensasi lomba siswa.
     *
     * @param  array{
     *     student_id: int,
     *     term_id: int,
     *     start_date: string,
     *     end_date: string,
     *     type: string,
     *     title: string,
     *     reason: string,
     *     attachment_path?: ?string,
     *     status?: string,
     *     notes?: ?string
     * }  $data
     */
    public function recordDispensation(array $data, User $actor): AttendanceDispensation
    {
        return DB::transaction(function () use ($data, $actor) {
            $dispensation = AttendanceDispensation::create([
                'school_id' => (int) $actor->school_id,
                'student_id' => (int) $data['student_id'],
                'term_id' => (int) $data['term_id'],
                'submitted_by_user_id' => (int) $actor->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'type' => DispensationTypeEnum::from($data['type']),
                'title' => $data['title'],
                'reason' => $data['reason'],
                'attachment_path' => $data['attachment_path'] ?? null,
                'status' => isset($data['status']) ? DispensationStatusEnum::from($data['status']) : DispensationStatusEnum::APPROVED,
                'approved_by_user_id' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->auditLogger->log(
                eventType: 'homeroom.dispensation.recorded',
                targetType: 'attendance_dispensation',
                targetId: (string) $dispensation->id,
                newValues: [
                    'student_id' => $dispensation->student_id,
                    'type' => $dispensation->type->value,
                    'start_date' => $dispensation->start_date->toDateString(),
                    'end_date' => $dispensation->end_date->toDateString(),
                    'title' => $dispensation->title,
                ],
                oldValues: null,
                actor: $actor,
                schoolId: (int) $actor->school_id
            );

            return $dispensation;
        });
    }

    /**
     * Ambil daftar dispensasi aktif pada tanggal tertentu untuk sekumpulan siswa.
     *
     * @param  list<int>  $studentIds
     * @return Collection<int, AttendanceDispensation>
     */
    public function getActiveDispensationsForStudents(int $schoolId, int $termId, array $studentIds, ?string $date = null): Collection
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return AttendanceDispensation::where('school_id', $schoolId)
            ->where('term_id', $termId)
            ->whereIn('student_id', $studentIds)
            ->where('status', DispensationStatusEnum::APPROVED)
            ->where('start_date', '<=', $targetDate)
            ->where('end_date', '>=', $targetDate)
            ->get();
    }
}
