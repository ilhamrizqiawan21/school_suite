<?php

declare(strict_types=1);

namespace App\Modules\Academic\Application\Services;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AttendanceKioskDevice;
use App\Modules\Academic\Infrastructure\Models\AttendanceKioskScan;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentCard;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Student RFID/QR card registry and attendance kiosk scanner with atomic deduplication
 * and offline queue batch sync (M4-OPS-03).
 */
class StudentCardKioskService
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
        private readonly AttendanceService $attendanceService,
        private readonly SchoolScopeService $schoolScope,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listCards(int $schoolId, string $search = ''): array
    {
        $clean = trim($search);

        $cards = StudentCard::query()
            ->with('student:id,nis,name,status')
            ->where('school_id', $schoolId)
            ->when($clean !== '', function ($q) use ($clean): void {
                $q->where(function ($sub) use ($clean): void {
                    $sub->where('card_uid', 'like', "%{$clean}%")
                        ->orWhere('qr_token', 'like', "%{$clean}%")
                        ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', "%{$clean}%")->orWhere('nis', 'like', "%{$clean}%"));
                });
            })
            ->latest('id')
            ->limit(100)
            ->get();

        $studentIds = $cards->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all();
        $snapshots = $this->academicQuery->getStudentsSnapshotsByIds($schoolId, $studentIds);

        return $cards->map(function (StudentCard $card) use ($snapshots): array {
            $snap = $snapshots[(int) $card->student_id] ?? null;

            return [
                'id' => (int) $card->id,
                'student_id' => (int) $card->student_id,
                'student_name' => $card->student?->name ?? '-',
                'student_nis' => $card->student?->nis ?? '-',
                'class_name' => $snap['class_group_name'] ?? '-',
                'card_uid' => $card->card_uid,
                'qr_token' => (string) $card->qr_token,
                'status' => (string) $card->status,
                'issued_at' => $card->issued_at?->toIso8601String(),
                'revoked_at' => $card->revoked_at?->toIso8601String(),
                'revocation_reason' => $card->revocation_reason,
            ];
        })->values()->all();
    }

    public function issueCard(User $actor, int $studentId, ?string $cardUid = null, ?string $customQrToken = null): StudentCard
    {
        $schoolId = (int) $actor->school_id;
        $student = Student::query()->where('school_id', $schoolId)->whereKey($studentId)->first();

        if ($student === null || $student->status !== 'active') {
            throw ValidationException::withMessages([
                'student_id' => 'Siswa aktif tidak ditemukan pada sekolah ini.',
            ]);
        }

        $cleanUid = $cardUid !== null && trim($cardUid) !== '' ? strtoupper(trim($cardUid)) : null;

        if ($cleanUid !== null) {
            $uidOwner = StudentCard::query()
                ->where('school_id', $schoolId)
                ->where('card_uid', $cleanUid)
                ->where('student_id', '!=', $studentId)
                ->exists();
            if ($uidOwner) {
                throw ValidationException::withMessages([
                    'card_uid' => 'UID kartu RFID ini sudah terdaftar pada siswa lain.',
                ]);
            }
        }

        return DB::transaction(function () use ($actor, $schoolId, $studentId, $cleanUid, $customQrToken): StudentCard {
            // Revoke previous active card for this student cleanly before issuing replacement
            StudentCard::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'revocation_reason' => 'Diganti dengan kartu baru.',
                    'revoked_by_user_id' => $actor->id,
                ]);

            $qrToken = $customQrToken !== null && trim($customQrToken) !== ''
                ? strtoupper(trim($customQrToken))
                : sprintf('QR-%s-%s', $this->schoolScope->localNow($schoolId)->format('Y'), strtoupper(Str::random(10)));

            $card = StudentCard::query()->create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'card_uid' => $cleanUid,
                'qr_token' => $qrToken,
                'status' => 'active',
                'issued_at' => now(),
                'issued_by_user_id' => $actor->id,
            ]);

            $this->audit->log(
                eventType: 'academic.student_card.issued',
                targetType: 'StudentCard',
                targetId: (string) $card->id,
                newValues: ['student_id' => $studentId, 'has_uid' => $cleanUid !== null],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $card;
        });
    }

    public function revokeCard(User $actor, StudentCard $card, string $reason): StudentCard
    {
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $card->school_id === $schoolId, 404);

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan pencabutan kartu wajib diisi.']);
        }

        $card->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $cleanReason,
            'revoked_by_user_id' => $actor->id,
        ])->save();

        $this->audit->log(
            eventType: 'academic.student_card.revoked',
            targetType: 'StudentCard',
            targetId: (string) $card->id,
            newValues: ['student_id' => $card->student_id, 'reason' => $cleanReason],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $card->refresh();
    }

    /**
     * Process an RFID or QR scan at the attendance kiosk with atomic deduplication and minimal output (M4-OPS-03).
     *
     * @return array<string, mixed>
     */
    public function processScan(
        int $schoolId,
        string $rawCode,
        string $scanMethod = 'rfid',
        ?int $deviceId = null,
        ?string $clientEventId = null,
        ?User $actor = null,
        ?string $scannedAtIso = null,
    ): array {
        $cleanCode = strtoupper(trim($rawCode));
        $method = in_array($scanMethod, ['rfid', 'qr', 'manual'], true) ? $scanMethod : 'rfid';
        $cleanEventId = $clientEventId !== null && trim($clientEventId) !== '' ? trim($clientEventId) : null;

        if ($cleanCode === '') {
            return [
                'status' => 'rejected',
                'code' => 'empty_code',
                'message' => 'Kode kartu atau QR tidak boleh kosong.',
            ];
        }

        return DB::transaction(function () use ($schoolId, $cleanCode, $method, $deviceId, $cleanEventId, $actor, $scannedAtIso): array {
            $this->schoolScope->lockSchool($schoolId);

            if ($cleanEventId !== null) {
                $existingEvent = AttendanceKioskScan::query()
                    ->where('school_id', $schoolId)
                    ->where('client_event_id', $cleanEventId)
                    ->first();

                if ($existingEvent !== null) {
                    $snap = $this->academicQuery->getStudentIdentitySnapshot($schoolId, (int) $existingEvent->student_id);

                    return [
                        'status' => 'duplicate',
                        'code' => 'idempotent_replay',
                        'message' => 'Pemindaian antrean sudah tercatat sebelumnya.',
                        'student' => $snap ? [
                            'name' => $snap['name'],
                            'class_name' => $snap['class_group_name'] ?? '-',
                        ] : null,
                        'scanned_at' => $existingEvent->scanned_at?->toIso8601String(),
                    ];
                }
            }

            /** @var StudentCard|null $card */
            $card = StudentCard::query()
                ->where('school_id', $schoolId)
                ->where(fn ($q) => $q->where('card_uid', $cleanCode)->orWhere('qr_token', $cleanCode))
                ->first();

            if ($card === null) {
                return [
                    'status' => 'rejected',
                    'code' => 'card_not_found',
                    'message' => 'Kartu asing atau tidak terdaftar di sekolah ini.',
                ];
            }

            if ($card->status !== 'active') {
                return [
                    'status' => 'rejected',
                    'code' => 'card_revoked',
                    'message' => 'Kartu sudah dicabut atau nonaktif.',
                ];
            }

            $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, (int) $card->student_id);
            if ($student === null || $student['status'] !== 'active') {
                return [
                    'status' => 'rejected',
                    'code' => 'student_inactive',
                    'message' => 'Data siswa tidak aktif.',
                ];
            }

            if (empty($student['term_id'])) {
                return [
                    'status' => 'rejected',
                    'code' => 'no_active_term',
                    'message' => 'Belum ada semester aktif pada sekolah ini.',
                ];
            }

            $localNow = $this->schoolScope->localNow($schoolId);
            $effectiveTime = $scannedAtIso !== null && trim($scannedAtIso) !== ''
                ? CarbonImmutable::parse($scannedAtIso)->setTimezone($this->schoolScope->getTimezone($schoolId))
                : $localNow;
            $ops = $this->schoolScope->getOperationsSettings($schoolId);
            $dedupStart = $effectiveTime->subSeconds($ops['kiosk_dedup_seconds']);

            $recentDuplicate = AttendanceKioskScan::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $student['student_id'])
                ->where('scan_result', 'recorded')
                ->where('scanned_at', '>=', $dedupStart->toDateTimeString())
                ->where('scanned_at', '<=', $effectiveTime->addSeconds(5)->toDateTimeString())
                ->exists();

            if ($recentDuplicate) {
                AttendanceKioskScan::query()->create([
                    'school_id' => $schoolId,
                    'kiosk_device_id' => $deviceId,
                    'student_id' => $student['student_id'],
                    'student_card_id' => $card->id,
                    'scan_method' => $method,
                    'scan_result' => 'duplicate',
                    'client_event_id' => $cleanEventId,
                    'scanned_at' => $effectiveTime->toDateTimeString(),
                    'recorded_by_user_id' => $actor?->id,
                ]);

                return [
                    'status' => 'duplicate',
                    'code' => 'duplicate_window',
                    'message' => 'Presensi baru saja tercatat. Scan berulang diabaikan.',
                    'student' => [
                        'name' => $student['name'],
                        'class_name' => $student['class_group_name'] ?? '-',
                    ],
                    'scanned_at' => $effectiveTime->toIso8601String(),
                ];
            }

            $attendance = $this->attendanceService->recordDailyPresenceFromKiosk(
                schoolId: $schoolId,
                studentId: (int) $student['student_id'],
                termId: (int) $student['term_id'],
                date: $effectiveTime->toDateString(),
                method: $method,
                actor: $actor,
            );

            if ($attendance === null) {
                return [
                    'status' => 'rejected',
                    'code' => 'enrollment_missing',
                    'message' => 'Siswa belum memiliki rombel aktif pada semester berjalan.',
                ];
            }

            AttendanceKioskScan::query()->create([
                'school_id' => $schoolId,
                'kiosk_device_id' => $deviceId,
                'student_id' => $student['student_id'],
                'student_card_id' => $card->id,
                'scan_method' => $method,
                'scan_result' => 'recorded',
                'client_event_id' => $cleanEventId,
                'scanned_at' => $effectiveTime->toDateTimeString(),
                'recorded_by_user_id' => $actor?->id,
            ]);

            if ($deviceId !== null) {
                AttendanceKioskDevice::query()
                    ->where('school_id', $schoolId)
                    ->whereKey($deviceId)
                    ->update(['last_seen_at' => now()]);
            }

            return [
                'status' => 'recorded',
                'code' => 'recorded',
                'message' => 'Presensi berhasil dicatat.',
                'student' => [
                    'name' => $student['name'],
                    'class_name' => $student['class_group_name'] ?? '-',
                ],
                'scanned_at' => $effectiveTime->toIso8601String(),
            ];
        });
    }

    /**
     * @param  list<array{code: string, scan_method?: string, client_event_id?: string, scanned_at?: string}>  $events
     * @return list<array<string, mixed>>
     */
    public function syncOfflineBatch(int $schoolId, array $events, ?int $deviceId = null, ?User $actor = null): array
    {
        $results = [];
        foreach ($events as $event) {
            $results[] = $this->processScan(
                schoolId: $schoolId,
                rawCode: (string) ($event['code'] ?? ''),
                scanMethod: (string) ($event['scan_method'] ?? 'rfid'),
                deviceId: $deviceId,
                clientEventId: isset($event['client_event_id']) ? (string) $event['client_event_id'] : null,
                actor: $actor,
                scannedAtIso: isset($event['scanned_at']) ? (string) $event['scanned_at'] : null,
            );
        }

        return $results;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecentScans(int $schoolId): array
    {
        $scans = AttendanceKioskScan::query()
            ->with('student:id,name')
            ->where('school_id', $schoolId)
            ->latest('scanned_at')
            ->latest('id')
            ->limit(15)
            ->get();

        $studentIds = $scans->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all();
        $snapshots = $this->academicQuery->getStudentsSnapshotsByIds($schoolId, $studentIds);

        return $scans->map(static fn (AttendanceKioskScan $scan): array => [
            'id' => (int) $scan->id,
            'student_name' => $scan->student?->name ?? '-',
            'class_name' => $snapshots[(int) $scan->student_id]['class_group_name'] ?? '-',
            'scan_method' => (string) $scan->scan_method,
            'scan_result' => (string) $scan->scan_result,
            'scanned_at' => $scan->scanned_at?->toIso8601String(),
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDevices(int $schoolId): array
    {
        return AttendanceKioskDevice::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get()
            ->map(static fn (AttendanceKioskDevice $d): array => [
                'id' => (int) $d->id,
                'name' => (string) $d->name,
                'location' => $d->location,
                'gate_enabled' => (bool) $d->gate_enabled,
                'is_active' => (bool) $d->is_active,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    public function registerDevice(User $actor, string $name, ?string $location, bool $gateEnabled): AttendanceKioskDevice
    {
        $schoolId = (int) $actor->school_id;

        $device = AttendanceKioskDevice::query()->create([
            'school_id' => $schoolId,
            'name' => trim($name),
            'location' => $location !== null ? trim($location) ?: null : null,
            'gate_enabled' => $gateEnabled,
            'is_active' => true,
            'created_by_user_id' => $actor->id,
        ]);

        $this->audit->log(
            eventType: 'academic.kiosk_device.registered',
            targetType: 'AttendanceKioskDevice',
            targetId: (string) $device->id,
            newValues: ['name' => $device->name, 'gate_enabled' => $gateEnabled],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $device;
    }
}
