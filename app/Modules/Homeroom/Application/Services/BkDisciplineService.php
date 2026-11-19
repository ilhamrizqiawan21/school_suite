<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Homeroom\Infrastructure\Models\StudentIncident;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dedicated Guidance & Counseling (BK) discipline case workflow with strict confidentiality,
 * private evidence attachments, auditable case updates, and authorized CSV export (M4-OPS-05).
 */
class BkDisciplineService
{
    public const VIOLATION_CATEGORIES = [
        'kedisiplinan' => 'Kedisiplinan & Tata Tertib',
        'kehadiran' => 'Ketidakhadiran / Bolos',
        'perundungan' => 'Perundungan / Sosial',
        'akademik' => 'Pelanggaran Akademik',
        'atribut' => 'Seragam & Atribut',
        'lainnya' => 'Kasus Konseling Lainnya',
    ];

    public function __construct(
        private readonly AcademicQueryService $academicQuery,
        private readonly HomeroomReferenceValidator $referenceValidator,
        private readonly ServiceDocumentService $documentService,
        private readonly AuditLogger $audit,
    ) {}

    public function assertAuthorizedActor(?User $actor): void
    {
        if ($actor === null || ! $actor->is_active || ! $actor->hasRole([RoleEnum::ADMIN->value, RoleEnum::COUNSELOR->value])) {
            abort(403, 'Akses kasus disiplin BK hanya untuk Guru BK dan Administrator.');
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function listCases(User $actor, array $filters = []): array
    {
        $this->assertAuthorizedActor($actor);
        $schoolId = (int) $actor->school_id;
        $search = trim((string) ($filters['search'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $matchingStudentIds = null;
        if ($search !== '') {
            $matched = $this->academicQuery->searchActiveStudentsForStaff($schoolId, $search, 50);
            $matchingStudentIds = array_column($matched, 'student_id');
        }

        $incidents = StudentIncident::query()
            ->where('school_id', $schoolId)
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->when(in_array($status, ['reported', 'investigating', 'resolved'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search, $matchingStudentIds): void {
                $q->where(function ($sub) use ($search, $matchingStudentIds): void {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                    if ($matchingStudentIds !== null && $matchingStudentIds !== []) {
                        $sub->orWhereIn('student_id', $matchingStudentIds);
                    }
                });
            })
            ->latest('incident_date')
            ->latest('id')
            ->limit(60)
            ->get();

        $studentIds = $incidents->pluck('student_id')->map(fn ($id) => (int) $id)->values()->all();
        $snapshots = $this->academicQuery->getStudentsSnapshotsByIds($schoolId, $studentIds);

        return $incidents->map(function (StudentIncident $item) use ($snapshots): array {
            $student = $snapshots[(int) $item->student_id] ?? null;

            return [
                'id' => (int) $item->id,
                'student_id' => (int) $item->student_id,
                'student_name' => $student['name'] ?? '-',
                'student_nis' => $student['nis'] ?? '-',
                'class_name' => $student['class_group_name'] ?? '-',
                'incident_date' => $item->incident_date?->toDateString(),
                'incident_time' => $item->incident_time,
                'location' => $item->location,
                'category' => (string) $item->category,
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'penalty_points' => (int) $item->penalty_points,
                'follow_up_action' => $item->follow_up_action,
                'evidence_document_ids' => $item->evidence_document_ids ?? [],
                'status' => (string) $item->status,
                'is_bk_confidential' => (bool) $item->is_bk_confidential,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $evidenceFiles
     */
    public function recordCase(User $actor, array $data, array $evidenceFiles = []): StudentIncident
    {
        $this->assertAuthorizedActor($actor);
        $schoolId = (int) $actor->school_id;
        $studentId = (int) $data['student_id'];
        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId);

        if ($student === null || empty($student['term_id'])) {
            throw ValidationException::withMessages(['student_id' => 'Siswa atau semester aktif tidak ditemukan.']);
        }

        $termId = (int) $student['term_id'];
        $classGroupId = $student['class_group_id'] ? (int) $student['class_group_id'] : null;
        $this->referenceValidator->validateStudentTermAndClass($schoolId, $studentId, $termId, $classGroupId);

        return DB::transaction(function () use ($actor, $schoolId, $studentId, $termId, $classGroupId, $data, $evidenceFiles): StudentIncident {
            $evidenceIds = [];
            foreach ($evidenceFiles as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'evidence',
                    ticket: null,
                    uploader: $actor,
                );
                $evidenceIds[] = (int) $doc->id;
            }

            $incident = StudentIncident::query()->create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'term_id' => $termId,
                'class_group_id' => $classGroupId,
                'recorded_by_user_id' => $actor->id,
                'incident_date' => $data['incident_date'],
                'incident_time' => $data['incident_time'] ?? null,
                'location' => isset($data['location']) ? trim((string) $data['location']) ?: null : null,
                'title' => trim((string) $data['title']),
                'description' => trim((string) $data['description']),
                'category' => (string) $data['category'],
                'penalty_points' => (int) ($data['penalty_points'] ?? 0),
                'follow_up_action' => isset($data['follow_up_action']) ? trim((string) $data['follow_up_action']) ?: null : null,
                'evidence_document_ids' => $evidenceIds,
                'status' => (string) ($data['status'] ?? 'investigating'),
                'is_bk_confidential' => true,
            ]);

            $this->audit->log(
                eventType: 'homeroom.bk_case.recorded',
                targetType: 'student_incident',
                targetId: (string) $incident->id,
                newValues: [
                    'student_id' => $studentId,
                    'category' => $incident->category,
                    'status' => $incident->status,
                    'evidence_count' => count($evidenceIds),
                ],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $incident;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $evidenceFiles
     */
    public function updateCase(User $actor, StudentIncident $incident, array $data, array $evidenceFiles = []): StudentIncident
    {
        $this->assertAuthorizedActor($actor);
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $incident->school_id === $schoolId, 404);

        return DB::transaction(function () use ($actor, $schoolId, $incident, $data, $evidenceFiles): StudentIncident {
            /** @var StudentIncident $locked */
            $locked = StudentIncident::query()->where('school_id', $schoolId)->whereKey($incident->id)->lockForUpdate()->firstOrFail();

            $oldValues = $locked->only(['title', 'category', 'penalty_points', 'status', 'follow_up_action', 'location', 'incident_time']);

            $evidenceIds = is_array($locked->evidence_document_ids) ? $locked->evidence_document_ids : [];
            foreach ($evidenceFiles as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $doc = $this->documentService->storePrivateAttachment(
                    schoolId: $schoolId,
                    file: $file,
                    documentKind: 'evidence',
                    ticket: null,
                    uploader: $actor,
                );
                $evidenceIds[] = (int) $doc->id;
            }

            $locked->forceFill([
                'incident_date' => $data['incident_date'] ?? $locked->incident_date->toDateString(),
                'incident_time' => $data['incident_time'] ?? $locked->incident_time,
                'location' => isset($data['location']) ? trim((string) $data['location']) ?: null : $locked->location,
                'category' => (string) ($data['category'] ?? $locked->category),
                'title' => trim((string) ($data['title'] ?? $locked->title)),
                'description' => trim((string) ($data['description'] ?? $locked->description)),
                'penalty_points' => (int) ($data['penalty_points'] ?? $locked->penalty_points),
                'follow_up_action' => isset($data['follow_up_action']) ? trim((string) $data['follow_up_action']) ?: null : $locked->follow_up_action,
                'status' => (string) ($data['status'] ?? $locked->status),
                'evidence_document_ids' => $evidenceIds,
            ])->save();

            $this->audit->log(
                eventType: 'homeroom.bk_case.updated',
                targetType: 'student_incident',
                targetId: (string) $locked->id,
                oldValues: $oldValues,
                newValues: $locked->only(['title', 'category', 'penalty_points', 'status', 'follow_up_action', 'location', 'incident_time']),
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(User $actor, array $filters = []): string
    {
        $this->assertAuthorizedActor($actor);
        $cases = $this->listCases($actor, $filters);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID Kasus', 'Tanggal', 'Waktu', 'Lokasi', 'NIS', 'Nama Siswa', 'Rombel', 'Kategori', 'Judul', 'Poin', 'Status', 'Tindak Lanjut']);

        foreach ($cases as $row) {
            fputcsv($handle, [
                $row['id'],
                $row['incident_date'] ?? '-',
                $this->csvSafe((string) ($row['incident_time'] ?? '-')),
                $this->csvSafe((string) ($row['location'] ?? '-')),
                $this->csvSafe((string) $row['student_nis']),
                $this->csvSafe((string) $row['student_name']),
                $this->csvSafe((string) $row['class_name']),
                $this->csvSafe((string) $row['category']),
                $this->csvSafe((string) $row['title']),
                $row['penalty_points'],
                $row['status'],
                $this->csvSafe((string) ($row['follow_up_action'] ?? '-')),
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $this->audit->log(
            eventType: 'homeroom.bk_case.exported',
            targetType: 'student_incident',
            targetId: 'csv_export',
            newValues: ['row_count' => count($cases)],
            actor: $actor,
            schoolId: (int) $actor->school_id,
        );

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
}
