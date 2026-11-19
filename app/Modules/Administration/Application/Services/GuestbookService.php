<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Administration\Infrastructure\Models\GuestbookVisit;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestbookService
{
    public const CATEGORIES = [
        'tamu_dinas' => 'Tamu Dinas / Instansi',
        'wali_murid' => 'Orang Tua / Wali Murid',
        'alumni' => 'Alumni',
        'vendor_mitra' => 'Mitra / Vendor',
        'lainnya' => 'Keperluan Lainnya',
    ];

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AcademicQueryService $academicQuery,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordVisit(int $schoolId, array $data, ?User $actor = null): GuestbookVisit
    {
        return DB::transaction(function () use ($schoolId, $data, $actor): GuestbookVisit {
            $this->schoolScope->lockSchool($schoolId);

            $hostTeacherId = isset($data['host_teacher_id']) && (int) $data['host_teacher_id'] > 0
                ? (int) $data['host_teacher_id']
                : null;
            $hostName = isset($data['host_name']) ? trim((string) $data['host_name']) : '';

            if ($hostTeacherId !== null) {
                $teachers = collect($this->academicQuery->getPublicDirectoryTeachers($schoolId));
                $matched = $teachers->firstWhere('id', $hostTeacherId);
                if ($matched !== null) {
                    $hostName = (string) $matched['name'];
                } else {
                    $hostTeacherId = null;
                }
            }

            if ($hostName === '') {
                $hostName = 'Petugas / Tata Usaha';
            }

            $localNow = $this->schoolScope->localNow($schoolId);
            $visitCode = $this->generateVisitCode((int) $localNow->format('Y'));

            $visit = GuestbookVisit::query()->create([
                'school_id' => $schoolId,
                'visit_code' => $visitCode,
                'visitor_name' => trim((string) $data['visitor_name']),
                'visitor_organization' => isset($data['visitor_organization']) ? trim((string) $data['visitor_organization']) ?: null : null,
                'visitor_phone' => isset($data['visitor_phone']) ? trim((string) $data['visitor_phone']) ?: null : null,
                'visitor_email' => isset($data['visitor_email']) ? trim((string) $data['visitor_email']) ?: null : null,
                'category' => array_key_exists((string) ($data['category'] ?? ''), self::CATEGORIES)
                    ? (string) $data['category']
                    : 'lainnya',
                'purpose' => trim((string) $data['purpose']),
                'host_teacher_id' => $hostTeacherId,
                'host_name' => $hostName,
                'host_unit' => isset($data['host_unit']) ? trim((string) $data['host_unit']) ?: null : null,
                'status' => 'checked_in',
                'checked_in_at' => $localNow->toDateTimeString(),
                'recorded_by' => $actor?->id,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) ?: null : null,
            ]);

            $this->audit->log(
                eventType: 'administration.guestbook.checked_in',
                targetType: 'GuestbookVisit',
                targetId: (string) $visit->id,
                newValues: [
                    'visit_code' => $visit->visit_code,
                    'category' => $visit->category,
                    'host_name' => $visit->host_name,
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $visit;
        });
    }

    public function checkOut(int $schoolId, GuestbookVisit $visit, User $actor, ?string $notes = null): GuestbookVisit
    {
        abort_unless((int) $visit->school_id === $schoolId, 404);

        return DB::transaction(function () use ($schoolId, $visit, $actor, $notes): GuestbookVisit {
            /** @var GuestbookVisit $locked */
            $locked = GuestbookVisit::query()
                ->where('school_id', $schoolId)
                ->whereKey($visit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'checked_out') {
                throw ValidationException::withMessages([
                    'status' => 'Pengunjung ini sudah tercatat pulang.',
                ]);
            }

            $localNow = $this->schoolScope->localNow($schoolId);

            $locked->forceFill([
                'status' => 'checked_out',
                'checked_out_at' => $localNow->toDateTimeString(),
                'checked_out_by' => $actor->id,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : $locked->notes,
            ])->save();

            $this->audit->log(
                eventType: 'administration.guestbook.checked_out',
                targetType: 'GuestbookVisit',
                targetId: (string) $locked->id,
                newValues: ['visit_code' => $locked->visit_code],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listForStaff(int $schoolId, array $filters): array
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        $paginated = GuestbookVisit::query()
            ->where('school_id', $schoolId)
            ->when(array_key_exists($category, self::CATEGORIES), fn ($q) => $q->where('category', $category))
            ->when(in_array($status, ['checked_in', 'checked_out'], true), fn ($q) => $q->where('status', $status))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('checked_in_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('checked_in_at', '<=', $dateTo))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('visitor_name', 'like', "%{$search}%")
                        ->orWhere('visitor_organization', 'like', "%{$search}%")
                        ->orWhere('host_name', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('visit_code', 'like', "%{$search}%");
                });
            })
            ->latest('checked_in_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $localNow = $this->schoolScope->localNow($schoolId);
        $today = $localNow->toDateString();
        $monthStart = $localNow->copy()->startOfMonth()->toDateString();
        $monthEnd = $localNow->copy()->endOfMonth()->toDateString();

        $base = GuestbookVisit::query()->where('school_id', $schoolId);

        return [
            'visits' => collect($paginated->items())->map(fn (GuestbookVisit $visit): array => [
                'id' => (int) $visit->id,
                'visit_code' => (string) $visit->visit_code,
                'visitor_name' => (string) $visit->visitor_name,
                'visitor_organization' => $visit->visitor_organization,
                'visitor_phone' => $visit->visitor_phone,
                'visitor_email' => $visit->visitor_email,
                'category' => (string) $visit->category,
                'category_label' => self::CATEGORIES[$visit->category] ?? $visit->category,
                'purpose' => (string) $visit->purpose,
                'host_teacher_id' => $visit->host_teacher_id,
                'host_name' => $visit->host_name,
                'host_unit' => $visit->host_unit,
                'status' => (string) $visit->status,
                'checked_in_at' => $visit->checked_in_at?->toIso8601String(),
                'checked_out_at' => $visit->checked_out_at?->toIso8601String(),
                'notes' => $visit->notes,
            ])->values()->all(),
            'pagination' => [
                'current' => $paginated->currentPage(),
                'last' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'previous' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
            'summary' => [
                'today_total' => (clone $base)->whereDate('checked_in_at', $today)->count(),
                'active_inside' => (clone $base)->where('status', 'checked_in')->count(),
                'checked_out_today' => (clone $base)->whereDate('checked_in_at', $today)->where('status', 'checked_out')->count(),
                'month_total' => (clone $base)->whereBetween('checked_in_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(int $schoolId, array $filters): string
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $rows = GuestbookVisit::query()
            ->where('school_id', $schoolId)
            ->when(array_key_exists($category, self::CATEGORIES), fn ($q) => $q->where('category', $category))
            ->when(in_array($status, ['checked_in', 'checked_out'], true), fn ($q) => $q->where('status', $status))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('checked_in_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('checked_in_at', '<=', $dateTo))
            ->latest('checked_in_at')
            ->limit(1000)
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Kode Kunjungan', 'Nama Pengunjung', 'Instansi/Asal', 'Kategori', 'Tujuan Pegawai/Unit', 'Keperluan', 'Status', 'Waktu Datang', 'Waktu Pulang']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $this->csvSafe($row->visit_code),
                $this->csvSafe($row->visitor_name),
                $this->csvSafe($row->visitor_organization ?? '-'),
                $this->csvSafe(self::CATEGORIES[$row->category] ?? $row->category),
                $this->csvSafe(trim(($row->host_name ?? '').($row->host_unit ? ' ('.$row->host_unit.')' : ''))),
                $this->csvSafe($row->purpose),
                $row->status === 'checked_out' ? 'Pulang' : 'Berkunjung',
                $row->checked_in_at?->format('Y-m-d H:i:s') ?? '-',
                $row->checked_out_at?->format('Y-m-d H:i:s') ?? '-',
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function csvSafe(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$trimmed;
        }

        return $trimmed;
    }

    private function generateVisitCode(int $year): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $len = strlen($alphabet);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, $len - 1)];
            }
            $code = sprintf('TAMU-%d-%s', $year, $suffix);
            if (! GuestbookVisit::query()->where('visit_code', $code)->exists()) {
                return $code;
            }
        }

        return sprintf('TAMU-%d-%s', $year, strtoupper(bin2hex(random_bytes(3))));
    }
}
