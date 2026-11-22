<?php

declare(strict_types=1);

namespace App\Modules\Library\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Library\Infrastructure\Models\LibraryClearanceCertificate;
use App\Modules\Library\Infrastructure\Models\LibraryCollection;
use App\Modules\Library\Infrastructure\Models\LibraryLoan;
use App\Modules\Library\Infrastructure\Models\LibraryProcurementRequest;
use App\Modules\Library\Infrastructure\Models\LibraryVisit;
use App\Shared\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Library catalog (`M4-SERV-03`), protected e-book access, QR/manual visit attendance,
 * procurement requests, loan circulation, and loan-aware library clearance (`M4-SERV-04`).
 */
class LibraryService
{
    public const CATEGORIES = [
        'buku_teks' => 'Buku Teks & Pelajaran',
        'referensi' => 'Referensi & Ensiklopedia',
        'fiksi' => 'Fiksi & Sastra',
        'jurnal' => 'Jurnal & Karya Ilmiah',
        'agama' => 'Keagamaan & Kitab',
        'ebook' => 'Koleksi Digital (E-Book)',
        'lainnya' => 'Koleksi Lainnya',
    ];

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AcademicQueryService $academicQuery,
        private readonly ServiceTicketService $ticketService,
        private readonly ServiceDocumentService $documentService,
        private readonly AuditLogger $audit,
    ) {}

    public function assertLibrarian(?User $actor): void
    {
        if ($actor === null || ! $actor->is_active || ! $actor->hasRole([RoleEnum::ADMIN->value, RoleEnum::LIBRARIAN->value])) {
            abort(403, 'Akses pengelolaan perpustakaan hanya untuk Pustakawan dan Admin.');
        }
    }

    /**
     * Catalog search enforcing visibility rules (`staff_only`, `members_only`, `public`) and external source fallback.
     *
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array<string, mixed>>, source_status: array<string, mixed>}
     */
    public function searchCatalog(int $schoolId, array $filters = [], ?User $viewer = null): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $format = trim((string) ($filters['format'] ?? ''));

        $isMember = $viewer !== null && $viewer->is_active && (int) $viewer->school_id === $schoolId;
        $isStaff = $isMember && (RoleEnum::tryFrom((string) $viewer->role?->name)?->isStaff() ?? false);

        $allowedAccess = ['public'];
        if ($isMember) {
            $allowedAccess[] = 'members_only';
        }
        if ($isStaff) {
            $allowedAccess[] = 'staff_only';
        }

        $items = LibraryCollection::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when(! $isStaff, fn ($q) => $q->whereIn('access_level', $allowedAccess))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->when(! empty($filters['id']), fn ($q) => $q->whereKey((int) $filters['id']))
            ->when(in_array($format, ['physical', 'ebook', 'hybrid'], true), fn ($q) => $q->where('format', $format))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(fn ($sub) => $sub
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('publisher', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%"));
            })
            ->orderBy('title')
            ->limit(80)
            ->get();

        $documentIds = $items->pluck('ebook_document_id')->filter()->map(static fn ($id): int => (int) $id)->values();
        $storedDocumentIds = $this->documentService->storedDocumentIds($schoolId, $documentIds->all());
        $missingLocalFiles = 0;

        $items = $items->map(function (LibraryCollection $c) use ($isMember, $isStaff, $storedDocumentIds, &$missingLocalFiles): array {
            $canAccess = match ($c->access_level) {
                'public' => true,
                'members_only' => $isMember,
                'staff_only' => $isStaff,
                default => false,
            };

            $hasEbookFile = $c->ebook_document_id !== null
                && in_array((int) $c->ebook_document_id, $storedDocumentIds, true);
            if ($c->ebook_document_id !== null && ! $hasEbookFile) {
                $missingLocalFiles++;
            }

            return [
                'id' => (int) $c->id,
                'code' => (string) $c->code,
                'title' => (string) $c->title,
                'author' => $c->author,
                'publisher' => $c->publisher,
                'publish_year' => $c->publish_year,
                'category' => (string) $c->category,
                'category_label' => self::CATEGORIES[$c->category] ?? $c->category,
                'format' => (string) $c->format,
                'total_copies' => (int) $c->total_copies,
                'available_copies' => (int) $c->available_copies,
                'shelf_location' => $c->shelf_location,
                'description' => $c->description,
                'access_level' => (string) $c->access_level,
                'can_access_material' => $canAccess,
                'has_ebook_file' => $hasEbookFile,
                'ebook_url' => $canAccess
                    ? ($hasEbookFile ? route('library.collections.download', $c->id) : $c->ebook_url)
                    : null,
            ];
        })
            ->values()
            ->all();

        $school = $this->schoolScope->getSchoolDetails($schoolId);
        $libCfg = is_array(data_get($school, 'settings.library')) ? data_get($school, 'settings.library') : [];
        $slimsConfigured = ! empty($libCfg['slims_base_url']);

        return [
            'items' => $items,
            'source_status' => [
                'mode' => $slimsConfigured ? 'slims_hybrid' : 'local_fallback',
                'slims_available' => false,
                'source_of_truth' => 'school_suite_local_catalog',
                'provider_configured' => $slimsConfigured,
                'fallback_active' => true,
                'missing_local_files' => $missingLocalFiles,
                'message' => $missingLocalFiles > 0
                    ? 'Sebagian berkas e-book lokal tidak tersedia; katalog tetap berjalan dan tautan eksternal dipakai bila dikonfigurasi.'
                    : ($slimsConfigured
                        ? 'Sinkronisasi SLiMS tidak aktif pada lingkungan saat ini; menampilkan katalog lokal terpadu.'
                        : 'Menggunakan katalog perpustakaan lokal School Suite (mode fallback SLiMS siap).'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveCollection(User $actor, array $data, ?UploadedFile $ebookFile = null, ?LibraryCollection $existing = null): LibraryCollection
    {
        $this->assertLibrarian($actor);
        $schoolId = (int) $actor->school_id;
        if ($existing !== null) {
            abort_unless((int) $existing->school_id === $schoolId, 404);
        }

        $docId = $existing?->ebook_document_id;
        if ($ebookFile instanceof UploadedFile) {
            $doc = $this->documentService->storePrivateAttachment(
                schoolId: $schoolId,
                file: $ebookFile,
                documentKind: 'ebook',
                ticket: null,
                uploader: $actor,
                allowedMimes: ['application/pdf'],
            );
            $docId = (int) $doc->id;
        }

        $total = max(0, (int) ($data['total_copies'] ?? ($existing?->total_copies ?? 1)));
        $available = array_key_exists('available_copies', $data)
            ? min($total, max(0, (int) $data['available_copies']))
            : ($existing ? min($total, (int) $existing->available_copies) : $total);

        $attrs = [
            'school_id' => $schoolId,
            'code' => strtoupper(trim((string) $data['code'])),
            'title' => trim((string) $data['title']),
            'author' => isset($data['author']) ? trim((string) $data['author']) ?: null : null,
            'publisher' => isset($data['publisher']) ? trim((string) $data['publisher']) ?: null : null,
            'publish_year' => ! empty($data['publish_year']) ? (int) $data['publish_year'] : null,
            'category' => (string) ($data['category'] ?? 'buku_teks'),
            'format' => (string) ($data['format'] ?? 'physical'),
            'total_copies' => $total,
            'available_copies' => $available,
            'shelf_location' => isset($data['shelf_location']) ? trim((string) $data['shelf_location']) ?: null : null,
            'description' => isset($data['description']) ? trim((string) $data['description']) ?: null : null,
            'ebook_url' => isset($data['ebook_url']) ? trim((string) $data['ebook_url']) ?: null : null,
            'ebook_document_id' => $docId,
            'access_level' => (string) ($data['access_level'] ?? 'public'),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : ($existing?->is_active ?? true),
        ];

        if ($existing !== null) {
            $existing->forceFill($attrs)->save();
            $record = $existing->refresh();
        } else {
            $record = LibraryCollection::query()->create([...$attrs, 'created_by' => $actor->id]);
        }

        $this->audit->log(
            eventType: $existing ? 'library.collection.updated' : 'library.collection.created',
            targetType: 'LibraryCollection',
            targetId: (string) $record->id,
            newValues: ['code' => $record->code, 'access_level' => $record->access_level],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $record;
    }

    /**
     * Record a library visit via student card QR token or manual entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordVisit(int $schoolId, array $data, ?User $actor = null): LibraryVisit
    {
        $cardToken = isset($data['card_token']) ? trim((string) $data['card_token']) : '';
        $method = $cardToken !== '' ? 'qr' : 'manual';

        $studentId = null;
        $visitorName = trim((string) ($data['visitor_name'] ?? ''));
        $visitorIdentifier = isset($data['visitor_identifier']) ? trim((string) $data['visitor_identifier']) ?: null : null;
        $visitorRole = (string) ($data['visitor_role'] ?? 'student');

        if ($cardToken !== '') {
            $student = $this->academicQuery->findActiveStudentByCardToken($schoolId, $cardToken);
            if ($student === null) {
                throw ValidationException::withMessages(['card_token' => 'Kartu QR siswa tidak valid atau sudah dicabut.']);
            }
            $studentId = (int) $student['student_id'];
            $visitorName = (string) $student['name'];
            $visitorIdentifier = (string) ($student['nis'] ?? $student['nisn'] ?? '');
            $visitorRole = 'student';
        } elseif (! empty($data['student_id'])) {
            $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, (int) $data['student_id']);
            if ($student === null || $student['status'] !== 'active') {
                throw ValidationException::withMessages(['student_id' => 'Siswa aktif tidak ditemukan.']);
            }
            $studentId = (int) $student['student_id'];
            $visitorName = (string) $student['name'];
            $visitorIdentifier = (string) ($student['nis'] ?? $student['nisn'] ?? '');
            $visitorRole = 'student';
        } elseif ($visitorName === '' && $actor !== null) {
            $visitorName = $actor->name;
            $visitorRole = $actor->role?->name === RoleEnum::STUDENT->value ? 'student' : 'teacher';
        }

        if ($visitorName === '') {
            throw ValidationException::withMessages(['visitor_name' => 'Nama pengunjung wajib diisi.']);
        }

        return LibraryVisit::query()->create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'user_id' => $actor?->id,
            'visitor_name' => $visitorName,
            'visitor_identifier' => $visitorIdentifier,
            'visitor_role' => $visitorRole,
            'check_in_method' => $method,
            'purpose' => isset($data['purpose']) ? trim((string) $data['purpose']) ?: null : null,
            'visited_at' => now(),
        ]);
    }

    /**
     * Record a loan with atomic stock decrement.
     *
     * @param  array<string, mixed>  $data
     */
    public function borrowCollection(User $actor, array $data): LibraryLoan
    {
        $this->assertLibrarian($actor);
        $schoolId = (int) $actor->school_id;

        return DB::transaction(function () use ($actor, $schoolId, $data): LibraryLoan {
            /** @var LibraryCollection $collection */
            $collection = LibraryCollection::query()->where('school_id', $schoolId)->whereKey((int) $data['collection_id'])->lockForUpdate()->firstOrFail();

            if ($collection->available_copies < 1) {
                throw ValidationException::withMessages(['collection_id' => 'Stok eksemplar koleksi yang tersedia sedang habis.']);
            }

            $studentId = ! empty($data['student_id']) ? (int) $data['student_id'] : null;
            $borrowerName = trim((string) ($data['borrower_name'] ?? ''));
            $borrowerIdentifier = isset($data['borrower_identifier']) ? trim((string) $data['borrower_identifier']) ?: null : null;

            if ($studentId !== null) {
                $snap = $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId);
                if ($snap === null) {
                    throw ValidationException::withMessages(['student_id' => 'Siswa tidak ditemukan.']);
                }
                $borrowerName = (string) $snap['name'];
                $borrowerIdentifier = (string) ($snap['nis'] ?? '');
            }

            if ($borrowerName === '') {
                throw ValidationException::withMessages(['borrower_name' => 'Nama peminjam wajib diisi.']);
            }

            $collection->decrement('available_copies');

            $loan = LibraryLoan::query()->create([
                'school_id' => $schoolId,
                'collection_id' => $collection->id,
                'student_id' => $studentId,
                'borrower_name' => $borrowerName,
                'borrower_identifier' => $borrowerIdentifier,
                'loan_code' => 'PJM-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(6)),
                'borrowed_at' => $data['borrowed_at'] ?? $this->schoolScope->localNow($schoolId)->toDateString(),
                'due_at' => $data['due_at'] ?? $this->schoolScope->localNow($schoolId)->addDays(7)->toDateString(),
                'status' => 'borrowed',
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) ?: null : null,
                'recorded_by' => $actor->id,
            ]);

            $this->audit->log(
                eventType: 'library.loan.borrowed',
                targetType: 'LibraryLoan',
                targetId: (string) $loan->id,
                newValues: ['collection_id' => $collection->id, 'student_id' => $studentId],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $loan;
        });
    }

    public function reserveCollection(User $actor, int $collectionId, ?string $notes = null): LibraryLoan
    {
        abort_unless($actor->is_active && $actor->hasRole(RoleEnum::STUDENT->value), 403);
        $schoolId = (int) $actor->school_id;
        $studentIds = $this->academicQuery->getStudentIdsForUser($schoolId, (int) $actor->id);
        abort_unless(count($studentIds) === 1, 403);
        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, (int) $studentIds[0]);
        abort_unless($student !== null && $student['status'] === 'active', 403);

        return DB::transaction(function () use ($actor, $schoolId, $collectionId, $student, $notes): LibraryLoan {
            $collection = LibraryCollection::query()->where('school_id', $schoolId)->whereKey($collectionId)->lockForUpdate()->firstOrFail();
            abort_unless($collection->is_active && in_array($collection->access_level, ['public', 'members_only'], true), 403);
            if (! in_array($collection->format, ['physical', 'hybrid'], true)) {
                throw ValidationException::withMessages(['id_buku' => 'Koleksi ini hanya tersedia dalam format digital.']);
            }
            $existing = LibraryLoan::query()->where('school_id', $schoolId)->where('collection_id', $collectionId)
                ->where('student_id', $student['student_id'])->whereIn('status', ['reserved', 'borrowed', 'overdue'])->first();
            if ($existing !== null) {
                return $existing;
            }
            if ($collection->available_copies < 1) {
                throw ValidationException::withMessages(['id_buku' => 'Stok buku sudah habis.']);
            }
            $collection->decrement('available_copies');
            $loan = LibraryLoan::query()->create([
                'school_id' => $schoolId, 'collection_id' => $collectionId,
                'student_id' => $student['student_id'], 'borrower_user_id' => $actor->id,
                'borrower_name' => $student['name'], 'borrower_identifier' => $student['nis'],
                'loan_code' => 'RSV-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(10)),
                'borrowed_at' => $this->schoolScope->localNow($schoolId)->toDateString(),
                'due_at' => $this->schoolScope->localNow($schoolId)->addDays(7)->toDateString(),
                'status' => 'reserved', 'notes' => $notes, 'recorded_by' => $actor->id,
            ]);
            $this->audit->log(eventType: 'library.loan.reserved', targetType: 'LibraryLoan', targetId: (string) $loan->id,
                newValues: ['collection_id' => $collectionId, 'student_id' => $student['student_id']], actor: $actor, schoolId: $schoolId);

            return $loan;
        });
    }

    public function confirmReservation(User $actor, LibraryLoan $loan): LibraryLoan
    {
        $this->assertLibrarian($actor);
        abort_unless((int) $loan->school_id === (int) $actor->school_id, 404);

        return DB::transaction(function () use ($actor, $loan): LibraryLoan {
            $locked = LibraryLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'borrowed') {
                return $locked;
            }
            if ($locked->status !== 'reserved') {
                throw ValidationException::withMessages(['loan' => 'Reservasi ini sudah tidak aktif.']);
            }
            $locked->update(['status' => 'borrowed', 'borrowed_at' => now()->toDateString(),
                'due_at' => $this->schoolScope->localNow((int) $actor->school_id)->addDays(7)->toDateString(), 'recorded_by' => $actor->id]);
            $this->audit->log(eventType: 'library.reservation.confirmed', targetType: 'LibraryLoan', targetId: (string) $locked->id,
                newValues: ['status' => 'borrowed'], actor: $actor, schoolId: (int) $actor->school_id);

            return $locked;
        });
    }

    public function returnLoan(User $actor, LibraryLoan $loan): LibraryLoan
    {
        $this->assertLibrarian($actor);
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $loan->school_id === $schoolId, 404);

        return DB::transaction(function () use ($actor, $schoolId, $loan): LibraryLoan {
            /** @var LibraryLoan $locked */
            $locked = LibraryLoan::query()->where('school_id', $schoolId)->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'returned') {
                return $locked;
            }

            $collection = LibraryCollection::query()->where('school_id', $schoolId)->whereKey($locked->collection_id)->lockForUpdate()->first();
            if ($collection !== null && $collection->available_copies < $collection->total_copies) {
                $collection->increment('available_copies');
            }

            $locked->forceFill([
                'status' => 'returned',
                'returned_at' => $this->schoolScope->localNow($schoolId)->toDateString(),
            ])->save();

            $this->audit->log(
                eventType: 'library.loan.returned',
                targetType: 'LibraryLoan',
                targetId: (string) $locked->id,
                newValues: ['student_id' => $locked->student_id],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $locked->refresh();
        });
    }

    /**
     * Check active loans for a student and either block clearance (`blocked_active_loan`)
     * or issue a verifiable Bebas Pustaka PDF (`cleared`) (M4-SERV-04).
     */
    public function checkOrIssueClearance(int $schoolId, int $studentId, ?User $actor = null): LibraryClearanceCertificate
    {
        $student = $this->academicQuery->getStudentIdentitySnapshot($schoolId, $studentId);
        if ($student === null) {
            throw ValidationException::withMessages(['student_id' => 'Data siswa tidak ditemukan pada sekolah ini.']);
        }

        return DB::transaction(function () use ($schoolId, $studentId, $student, $actor): LibraryClearanceCertificate {
            $activeLoans = LibraryLoan::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->whereIn('status', ['borrowed', 'overdue', 'lost'])
                ->count();

            $code = 'BP-'.$this->schoolScope->localNow($schoolId)->format('Y').'-'.Str::upper(Str::random(6));

            if ($activeLoans > 0) {
                return LibraryClearanceCertificate::query()->create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'clearance_code' => $code,
                    'status' => 'blocked_active_loan',
                    'active_loan_count' => $activeLoans,
                    'issued_by' => $actor?->id,
                    'notes' => sprintf('Masih terdapat %d peminjaman koleksi yang belum dikembalikan.', $activeLoans),
                ]);
            }

            $school = $this->schoolScope->getSchoolDetails($schoolId);
            $verificationToken = Str::random(40);
            $signatoryName = $actor?->name ?? 'Pustakawan Perpustakaan';

            $ticket = $this->ticketService->issueTicket($schoolId, [
                'service_type' => 'perpustakaan',
                'prefix' => 'LIB',
                'applicant_user_id' => $actor?->id,
                'subject_student_id' => $studentId,
                'applicant_snapshot' => [
                    'student_name' => $student['name'],
                    'clearance_code' => $code,
                ],
                'internal_status' => 'cleared',
                'public_summary' => sprintf('Surat Keterangan Bebas Pustaka %s diterbitkan.', $code),
            ], $actor);

            $clearance = LibraryClearanceCertificate::query()->create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'service_ticket_id' => (int) $ticket->id,
                'clearance_code' => $code,
                'status' => 'cleared',
                'active_loan_count' => 0,
                'issued_by' => $actor?->id,
                'issued_at' => now(),
                'notes' => 'Bebas tanggungan perpustakaan.',
            ]);

            $bytes = Pdf::loadView('library.clearance-letter', [
                'school' => $school,
                'clearance' => $clearance,
                'student' => $student,
                'documentNumber' => $code,
                'signatoryName' => $signatoryName,
                'verificationToken' => $verificationToken,
            ])->setPaper('a4')->output();

            $doc = $this->documentService->issueGeneratedDocument(
                schoolId: $schoolId,
                bytes: $bytes,
                payload: [
                    'document_kind' => 'clearance',
                    'number_prefix' => 'LIB',
                    'template_key' => 'library_clearance',
                    'template_version' => 1,
                    'verification_token' => $verificationToken,
                    'original_name' => sprintf('bebas-pustaka-%s.pdf', $code),
                    'signatory_name' => $signatoryName,
                    'signatory_title' => 'Pustakawan Perpustakaan',
                    'signature_mode' => 'image',
                    'service_ticket_id' => (int) $ticket->id,
                ],
                actor: $actor,
            );

            $clearance->forceFill(['clearance_document_id' => (int) $doc->id])->save();

            $this->audit->log(
                eventType: 'library.clearance.issued',
                targetType: 'LibraryClearanceCertificate',
                targetId: (string) $clearance->id,
                newValues: ['student_id' => $studentId, 'clearance_code' => $code],
                actor: $actor,
                schoolId: $schoolId,
            );

            return $clearance->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitProcurement(User $actor, array $data): LibraryProcurementRequest
    {
        $schoolId = (int) $actor->school_id;

        return LibraryProcurementRequest::query()->create([
            'school_id' => $schoolId,
            'requester_user_id' => $actor->id,
            'requester_name' => $actor->name,
            'title' => trim((string) $data['title']),
            'author' => isset($data['author']) ? trim((string) $data['author']) ?: null : null,
            'publisher' => isset($data['publisher']) ? trim((string) $data['publisher']) ?: null : null,
            'isbn' => isset($data['isbn']) ? trim((string) $data['isbn']) ?: null : null,
            'reason' => trim((string) $data['reason']),
            'estimated_copies' => max(1, (int) ($data['estimated_copies'] ?? 1)),
            'status' => 'submitted',
        ]);
    }

    public function decideProcurement(User $actor, LibraryProcurementRequest $req, string $status, ?string $note = null): LibraryProcurementRequest
    {
        $this->assertLibrarian($actor);
        $schoolId = (int) $actor->school_id;
        abort_unless((int) $req->school_id === $schoolId, 404);

        if (! in_array($status, ['approved', 'rejected', 'acquired'], true)) {
            throw ValidationException::withMessages(['status' => 'Status keputusan usulan tidak valid.']);
        }

        $req->forceFill([
            'status' => $status,
            'decision_note' => $note !== null ? trim($note) ?: null : null,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ])->save();

        return $req->refresh();
    }

    public function statistics(int $schoolId): array
    {
        $now = $this->schoolScope->localNow($schoolId);
        $visits = LibraryVisit::query()->where('school_id', $schoolId)
            ->whereBetween('visited_at', [$now->copy()->startOfDay()->timezone(config('app.timezone')), $now->copy()->endOfDay()->timezone(config('app.timezone'))])
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN visitor_role = 'student' THEN 1 ELSE 0 END) AS students, SUM(CASE WHEN visitor_role = 'teacher' THEN 1 ELSE 0 END) AS teachers, SUM(CASE WHEN visitor_role = 'guest' THEN 1 ELSE 0 END) AS guests")->first();
        $collections = LibraryCollection::query()->where('school_id', $schoolId)->where('is_active', true)
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN access_level = 'public' THEN 1 ELSE 0 END) AS public_titles, SUM(CASE WHEN access_level = 'public' AND format IN ('physical', 'hybrid') THEN total_copies ELSE 0 END) AS public_copies, COUNT(DISTINCT CASE WHEN access_level = 'public' THEN category END) AS public_categories")->first();

        return [
            'collections_count' => (int) $collections->total,
            'public_collections_count' => (int) $collections->public_titles,
            'public_physical_copies' => (int) $collections->public_copies,
            'public_categories_count' => (int) $collections->public_categories,
            'visits_today' => (int) $visits->total,
            'student_visits_today' => (int) $visits->students,
            'teacher_visits_today' => (int) $visits->teachers,
            'guest_visits_today' => (int) $visits->guests,
            'active_loans' => LibraryLoan::query()->where('school_id', $schoolId)->whereIn('status', ['borrowed', 'overdue', 'lost'])->count(),
            'pending_procurements' => LibraryProcurementRequest::query()->where('school_id', $schoolId)->where('status', 'submitted')->count(),
        ];
    }

    public function publicVisitStatistics(int $schoolId): array
    {
        $now = $this->schoolScope->localNow($schoolId);
        $visits = LibraryVisit::query()->where('school_id', $schoolId)
            ->where('visited_at', '>=', $now->copy()->subDays(29)->startOfDay()->timezone(config('app.timezone')))
            ->get(['visited_at', 'visitor_role']);
        $counts = $visits->groupBy(fn ($visit) => $visit->visited_at->timezone($now->timezone)->toDateString())->map->count();
        $days = collect(range(29, 0))->map(fn ($i) => $now->copy()->subDays($i)->toDateString());
        $roleCounts = $visits->countBy('visitor_role');
        $today = (int) ($counts[$now->toDateString()] ?? 0);

        return [
            'kpi' => [...array_intersect_key($this->statistics($schoolId), array_flip(['collections_count', 'visits_today', 'active_loans'])), 'today' => $today,
                'week' => $visits->where('visited_at', '>=', $now->copy()->subDays(6)->startOfDay()->utc())->count(),
                'month' => LibraryVisit::query()->where('school_id', $schoolId)->where('visited_at', '>=', $now->copy()->startOfMonth()->timezone(config('app.timezone')))->count(),
                'all' => LibraryVisit::query()->where('school_id', $schoolId)->count(),
                'avg_daily' => round($visits->count() / 30, 1)],
            'trend' => ['labels' => $days->all(), 'data' => $days->map(fn ($day) => (int) ($counts[$day] ?? 0))->all()],
            'tipe' => ['labels' => ['Siswa', 'Guru', 'Tamu'], 'data' => [(int) ($roleCounts['student'] ?? 0), (int) ($roleCounts['teacher'] ?? 0), (int) ($roleCounts['guest'] ?? 0)]],
            'kelas' => ['labels' => [], 'data' => []], 'keperluan' => ['labels' => [], 'data' => []],
            'jam_sibuk' => ['labels' => range(0, 23), 'data' => collect(range(0, 23))->map(fn ($h) => $visits->filter(fn ($v) => $v->visited_at->timezone($now->timezone)->hour === $h)->count())->all()],
        ];
    }
}
