<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Homeroom\Infrastructure\Models\GatePass;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Duty picket exit-pass issuance and security guard gate validation (M4-OPS-04).
 */
class PicketGateService
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
        private readonly SchoolScopeService $schoolScope,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function issuePass(User $actor, array $data): GatePass
    {
        $schoolId = (int) $actor->school_id;
        $studentId = (int) $data['student_id'];
        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId);

        if ($student === null || $student['status'] !== 'active') {
            throw ValidationException::withMessages(['student_id' => 'Siswa aktif tidak ditemukan.']);
        }

        if (empty($student['term_id'])) {
            throw ValidationException::withMessages(['student_id' => 'Belum ada semester aktif.']);
        }

        $tz = $this->schoolScope->getTimezone($schoolId);
        $validFrom = CarbonImmutable::parse((string) $data['valid_from'], $tz);
        $validUntil = CarbonImmutable::parse((string) $data['valid_until'], $tz);

        if ($validUntil->lte($validFrom)) {
            throw ValidationException::withMessages(['valid_until' => 'Waktu berakhir izin keluar harus setelah waktu mulai.']);
        }

        $passCode = $this->uniquePassCode((int) $validFrom->format('Y'));

        $appTz = (string) config('app.timezone', 'UTC');

        $pass = GatePass::query()->create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'term_id' => (int) $student['term_id'],
            'pass_code' => $passCode,
            'exit_type' => in_array($data['exit_type'] ?? '', ['temporary_out', 'early_home', 'school_duty'], true)
                ? (string) $data['exit_type']
                : 'temporary_out',
            'reason' => trim((string) $data['reason']),
            'destination' => isset($data['destination']) ? trim((string) $data['destination']) ?: null : null,
            'valid_from' => $validFrom->setTimezone($appTz),
            'valid_until' => $validUntil->setTimezone($appTz),
            'status' => 'active',
            'issued_by_user_id' => $actor->id,
        ]);

        $this->audit->log(
            eventType: 'homeroom.gate_pass.issued',
            targetType: 'GatePass',
            targetId: (string) $pass->id,
            newValues: ['pass_code' => $passCode, 'student_id' => $studentId, 'exit_type' => $pass->exit_type],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $pass;
    }

    public function cancelPass(User $actor, GatePass $pass, string $reason): GatePass
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $pass->school_id === $schoolId, 404);

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan pembatalan izin keluar wajib diisi.']);
        }

        return DB::transaction(function () use ($actor, $schoolId, $pass, $cleanReason): GatePass {
            /** @var GatePass $locked */
            $locked = GatePass::query()->where('school_id', $schoolId)->whereKey($pass->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['active', 'out'], true)) {
                throw ValidationException::withMessages(['status' => 'Izin keluar ini sudah tidak dapat dibatalkan.']);
            }

            $locked->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $cleanReason,
            ])->save();

            $this->audit->log(
                eventType: 'homeroom.gate_pass.cancelled',
                targetType: 'GatePass',
                targetId: (string) $locked->id,
                newValues: ['reason' => $cleanReason],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Validate a gate pass code or student card/QR at the security post and record exit or return (M4-OPS-04).
     *
     * @return array<string, mixed>
     */
    public function processGateCheck(
        User $actor,
        string $rawCode,
        string $mode = 'auto',
        ?string $handoverNotes = null,
    ): array {
        $schoolId = (int) $actor->school_id;
        $clean = strtoupper(trim($rawCode));

        if ($clean === '') {
            return ['status' => 'rejected', 'code' => 'empty', 'message' => 'Kode izin keluar atau kartu wajib diisi.'];
        }

        return DB::transaction(function () use ($actor, $schoolId, $clean, $mode, $handoverNotes): array {
            /** @var GatePass|null $pass */
            $pass = GatePass::query()
                ->where('school_id', $schoolId)
                ->where('pass_code', $clean)
                ->lockForUpdate()
                ->first();

            if ($pass === null) {
                $studentByCard = $this->academicQuery->findActiveStudentByCardToken($schoolId, $clean);
                if ($studentByCard !== null) {
                    $pass = GatePass::query()
                        ->where('school_id', $schoolId)
                        ->where('student_id', (int) $studentByCard['student_id'])
                        ->whereIn('status', ['active', 'out'])
                        ->latest('id')
                        ->lockForUpdate()
                        ->first();
                }
            }

            if ($pass === null) {
                return ['status' => 'rejected', 'code' => 'not_found', 'message' => 'Izin keluar tidak ditemukan untuk kode atau kartu tersebut.'];
            }

            $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, (int) $pass->student_id);

            if ($pass->status === 'cancelled') {
                return [
                    'status' => 'rejected',
                    'code' => 'cancelled',
                    'message' => 'Izin keluar telah dibatalkan: '.($pass->cancellation_reason ?: 'Dibatalkan oleh piket'),
                    'pass' => $this->serializePass($pass, $student),
                ];
            }

            if ($pass->status === 'returned') {
                return [
                    'status' => 'rejected',
                    'code' => 'already_used',
                    'message' => 'Izin keluar ini sudah selesai digunakan (siswa sudah kembali).',
                    'pass' => $this->serializePass($pass, $student),
                ];
            }

            if ($pass->status === 'expired' || ($pass->status === 'active' && $pass->valid_until->isPast())) {
                if ($pass->status !== 'expired') {
                    $pass->forceFill(['status' => 'expired'])->save();
                }

                return [
                    'status' => 'rejected',
                    'code' => 'expired',
                    'message' => 'Masa berlaku izin keluar telah berakhir (kedaluwarsa).',
                    'pass' => $this->serializePass($pass->refresh(), $student),
                ];
            }

            if ($pass->status === 'active' && in_array($mode, ['auto', 'exit'], true)) {
                $pass->forceFill([
                    'status' => 'out',
                    'exit_recorded_at' => now(),
                    'exit_recorded_by' => $actor->id,
                    'handover_notes' => $handoverNotes !== null ? trim($handoverNotes) ?: $pass->handover_notes : $pass->handover_notes,
                ])->save();

                $this->audit->log(
                    eventType: 'homeroom.gate_pass.exited',
                    targetType: 'GatePass',
                    targetId: (string) $pass->id,
                    newValues: ['pass_code' => $pass->pass_code, 'student_id' => $pass->student_id],
                    actor: $actor,
                    schoolId: $schoolId,
                );

                return [
                    'status' => 'exited',
                    'code' => 'exited',
                    'message' => 'Izin keluar sah. Siswa dicatat keluar pos.',
                    'pass' => $this->serializePass($pass->refresh(), $student),
                ];
            }

            if ($pass->status === 'out' && in_array($mode, ['auto', 'return'], true)) {
                $pass->forceFill([
                    'status' => 'returned',
                    'return_recorded_at' => now(),
                    'return_recorded_by' => $actor->id,
                    'handover_notes' => $handoverNotes !== null ? trim($handoverNotes) ?: $pass->handover_notes : $pass->handover_notes,
                ])->save();

                $this->audit->log(
                    eventType: 'homeroom.gate_pass.returned',
                    targetType: 'GatePass',
                    targetId: (string) $pass->id,
                    newValues: ['pass_code' => $pass->pass_code, 'student_id' => $pass->student_id],
                    actor: $actor,
                    schoolId: $schoolId,
                );

                return [
                    'status' => 'returned',
                    'code' => 'returned',
                    'message' => 'Siswa dicatat kembali masuk melalui pos jaga.',
                    'pass' => $this->serializePass($pass->refresh(), $student),
                ];
            }

            return [
                'status' => 'rejected',
                'code' => 'invalid_state',
                'message' => 'Status izin keluar tidak sesuai dengan aksi pos jaga.',
                'pass' => $this->serializePass($pass, $student),
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPasses(int $schoolId, string $statusFilter = ''): array
    {
        $passes = GatePass::query()
            ->where('school_id', $schoolId)
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->latest('id')
            ->limit(60)
            ->get();

        $studentIds = $passes->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all();
        $snapshots = $this->academicQuery->getStudentsSnapshotsByIds($schoolId, $studentIds);

        return $passes->map(fn (GatePass $pass): array => $this->serializePass($pass, $snapshots[(int) $pass->student_id] ?? null))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $student
     * @return array<string, mixed>
     */
    private function serializePass(GatePass $pass, ?array $student): array
    {
        $effectiveStatus = $pass->status === 'active' && $pass->valid_until->isPast()
            ? 'expired'
            : $pass->status;

        return [
            'id' => (int) $pass->id,
            'pass_code' => (string) $pass->pass_code,
            'student_id' => (int) $pass->student_id,
            'student_name' => $student['name'] ?? '-',
            'student_nis' => $student['nis'] ?? '-',
            'class_name' => $student['class_group_name'] ?? '-',
            'exit_type' => (string) $pass->exit_type,
            'reason' => (string) $pass->reason,
            'destination' => $pass->destination,
            'valid_from' => $pass->valid_from?->toIso8601String(),
            'valid_until' => $pass->valid_until?->toIso8601String(),
            'status' => $effectiveStatus,
            'exit_recorded_at' => $pass->exit_recorded_at?->toIso8601String(),
            'return_recorded_at' => $pass->return_recorded_at?->toIso8601String(),
            'cancellation_reason' => $pass->cancellation_reason,
            'handover_notes' => $pass->handover_notes,
        ];
    }

    private function uniquePassCode(int $year): string
    {
        for ($i = 0; $i < 5; $i++) {
            $code = sprintf('PKT-%d-%s', $year, strtoupper(Str::random(6)));
            if (! GatePass::query()->where('pass_code', $code)->exists()) {
                return $code;
            }
        }

        return sprintf('PKT-%d-%s', $year, strtoupper(bin2hex(random_bytes(3))));
    }
}
