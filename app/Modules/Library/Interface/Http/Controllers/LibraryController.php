<?php

declare(strict_types=1);

namespace App\Modules\Library\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Library\Application\Services\LibraryService;
use App\Modules\Library\Infrastructure\Models\LibraryClearanceCertificate;
use App\Modules\Library\Infrastructure\Models\LibraryCollection;
use App\Modules\Library\Infrastructure\Models\LibraryLoan;
use App\Modules\Library\Infrastructure\Models\LibraryProcurementRequest;
use App\Modules\Library\Infrastructure\Models\LibraryVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LibraryController extends Controller
{
    public function __construct(
        private readonly LibraryService $libraryService,
        private readonly AcademicQueryService $academicQuery,
        private readonly AccessScopeService $accessScope,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
    ) {}

    /**
     * Halaman publik utama Perpustakaan (`/perpustakaan`) yang terhubung ke `LibraryService`.
     */
    public function replica(): \Illuminate\Http\Response
    {
        $school = $this->schoolScope->findPrimaryPublicSnapshot();
        $stats = $school !== null
            ? $this->libraryService->statistics((int) $school['id'])
            : ['collections_count' => 0, 'visits_today' => 0, 'active_loans' => 0];

        return response()->view('public.madrasah.perpustakaan', [
            'school' => $school,
            'stats' => $stats,
        ]);
    }

    /**
     * Endpoint aksi interaktif `/perpustakaan/aksi` yang terhubung ke `LibraryService` dengan kontrol otorisasi.
     */
    public function replicaAction(Request $request): JsonResponse
    {
        $school = $this->schoolScope->findPrimaryPublicSnapshot();
        if ($school === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data sekolah belum tersedia.',
            ], 404);
        }

        $schoolId = (int) $school['id'];
        $pg = trim((string) $request->query('pg', ''));
        $user = $request->user();

        if ($pg === 'katalog') {
            $catalog = $this->libraryService->searchCatalog($schoolId, [
                'search' => (string) $request->query('search', ''),
                'category' => (string) $request->query('category', ''),
                'format' => (string) $request->query('format', ''),
            ], $user);

            return response()->json(['status' => 'success', ...$catalog]);
        }

        if ($pg === 'detail_buku') {
            $id = (int) $request->query('id', 0);
            $collection = LibraryCollection::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->whereKey($id)
                ->first();

            if ($collection === null) {
                return response()->json(['status' => 'error', 'message' => 'Koleksi tidak ditemukan.'], 404);
            }

            $isMember = $user !== null && $user->is_active && (int) $user->school_id === $schoolId;
            $isStaff = $isMember && (RoleEnum::tryFrom((string) $user->role?->name)?->isStaff() ?? false);
            $allowed = match ($collection->access_level) {
                'public' => true,
                'members_only' => $isMember,
                'staff_only' => $isStaff,
                default => false,
            };

            if (! $allowed) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke koleksi ini.'], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => (int) $collection->id,
                    'judul' => $collection->title,
                    'penulis' => $collection->author ?? '-',
                    'penerbit' => $collection->publisher ?? '-',
                    'isbn' => $collection->isbn ?? $collection->code,
                    'kategori' => LibraryService::CATEGORIES[$collection->category] ?? $collection->category,
                    'stok' => (int) $collection->total_copies,
                    'tersedia' => (int) $collection->available_copies,
                    'rak' => $collection->shelf_location ?? '-',
                    'format' => $collection->format,
                    'access_level' => $collection->access_level,
                    'download_url' => $collection->ebook_document_id !== null ? route('library.collections.download', $collection->id) : null,
                ],
            ]);
        }

        if ($pg === 'statistik_kunjungan') {
            return response()->json([
                'status' => 'success',
                'kpi' => $this->libraryService->statistics($schoolId),
            ]);
        }

        if ($pg === 'traffic') {
            $stats = $this->libraryService->statistics($schoolId);

            return response()->json([
                'status' => 'success',
                'active_readers' => (int) ($stats['visits_today'] ?? 0),
            ]);
        }

        if ($pg === 'buku_tamu') {
            abort_unless($request->isMethod('post'), 405);
            $validated = $request->validate([
                'card_token' => ['nullable', 'string', 'max:80'],
                'visitor_name' => ['nullable', 'string', 'max:150'],
                'visitor_identifier' => ['nullable', 'string', 'max:60'],
                'visitor_role' => ['nullable', Rule::in(['student', 'teacher', 'guest'])],
                'purpose' => ['nullable', 'string', 'max:180'],
            ]);
            $visit = $this->libraryService->recordVisit($schoolId, $validated, $user);

            return response()->json([
                'status' => 'success',
                'message' => "Kunjungan perpustakaan tercatat ({$visit->visitor_name}).",
            ]);
        }

        if ($pg === 'usulkan_buku') {
            abort_unless($request->isMethod('post'), 405);
            if ($user === null) {
                return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu untuk mengusulkan pengadaan buku.'], 401);
            }
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:200'],
                'author' => ['nullable', 'string', 'max:150'],
                'isbn' => ['nullable', 'string', 'max:40'],
                'reason' => ['required', 'string', 'max:1500'],
                'estimated_copies' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);
            $this->libraryService->submitProcurement($user, $validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Usulan pengadaan koleksi perpustakaan berhasil dikirim.',
            ]);
        }

        if ($pg === 'cek_bebas_pustaka') {
            if ($user === null || (int) $user->school_id !== $schoolId) {
                return response()->json(['status' => 'error', 'message' => 'Silakan masuk ke akun anggota untuk memeriksa status bebas pustaka.'], 401);
            }
            $studentIds = array_column($this->academicQuery->getStudentsForUserScope($schoolId, $user), 'student_id');
            $clearances = $studentIds === []
                ? []
                : LibraryClearanceCertificate::query()
                    ->where('school_id', $schoolId)
                    ->whereIn('student_id', $studentIds)
                    ->latest('id')
                    ->get()
                    ->toArray();

            return response()->json([
                'status' => 'success',
                'clearances' => $clearances,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Aksi tidak dikenali.',
        ], 404);
    }

    public function publicIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $user = $request->user();

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', '')),
            'format' => trim((string) $request->query('format', '')),
        ];

        $catalog = $this->libraryService->searchCatalog($schoolId, $filters, $user);

        $students = [];
        $myClearances = [];
        if ($user !== null && (int) $user->school_id === $schoolId) {
            $students = $this->academicQuery->getStudentsForUserScope($schoolId, $user);
            $studentIds = array_column($students, 'student_id');
            if ($studentIds !== []) {
                $myClearances = LibraryClearanceCertificate::query()
                    ->where('school_id', $schoolId)
                    ->whereIn('student_id', $studentIds)
                    ->latest('id')
                    ->get()
                    ->toArray();
            }
        }

        return Inertia::render('Public/Library/Index', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'collections' => $catalog['items'],
            'sourceStatus' => $catalog['source_status'],
            'categories' => collect(LibraryService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => $filters,
            'stats' => $this->libraryService->statistics($schoolId),
            'students' => $students,
            'myClearances' => $myClearances,
            'visitReceipt' => $request->session()->get('library_visit_receipt'),
            'clearanceResult' => $request->session()->get('library_clearance_result'),
        ]);
    }

    public function downloadEbook(Request $request, LibraryCollection $collection): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        abort_unless((int) $collection->school_id === $schoolId && $collection->is_active && $collection->ebook_document_id !== null, 404);

        $user = $request->user();
        $isMember = $user !== null && $user->is_active && (int) $user->school_id === $schoolId;
        $isStaff = $isMember && (RoleEnum::tryFrom((string) $user->role?->name)?->isStaff() ?? false);

        if ($collection->access_level === 'members_only') {
            abort_unless($isMember, 403);
        } elseif ($collection->access_level === 'staff_only') {
            abort_unless($isStaff, 403);
        }

        $file = $this->documentService->resolveDownloadByDocumentId($schoolId, (int) $collection->ebook_document_id);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function recordVisit(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'card_token' => ['nullable', 'string', 'max:80'],
            'student_id' => ['nullable', 'integer'],
            'visitor_name' => ['nullable', 'string', 'max:150'],
            'visitor_identifier' => ['nullable', 'string', 'max:60'],
            'visitor_role' => ['nullable', Rule::in(['student', 'teacher', 'guest'])],
            'purpose' => ['nullable', 'string', 'max:180'],
        ]);

        $visit = $this->libraryService->recordVisit($schoolId, $validated, $request->user());

        return back()
            ->with('message', "Kunjungan perpustakaan tercatat ({$visit->visitor_name}).")
            ->with('library_visit_receipt', [
                'visitor_name' => $visit->visitor_name,
                'method' => $visit->check_in_method,
            ]);
    }

    public function requestProcurement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            'publisher' => ['nullable', 'string', 'max:150'],
            'isbn' => ['nullable', 'string', 'max:40'],
            'reason' => ['required', 'string', 'max:1500'],
            'estimated_copies' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->libraryService->submitProcurement($request->user(), $validated);

        return back()->with('message', 'Usulan pengadaan koleksi perpustakaan berhasil dikirim.');
    }

    public function requestClearance(Request $request): RedirectResponse
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
        ]);

        $studentId = (int) $validated['student_id'];
        $isLibrarian = $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::LIBRARIAN->value]);
        abort_unless($isLibrarian || $this->accessScope->canAccessStudentScope($user, $schoolId, $studentId), 403);

        $clearance = $this->libraryService->checkOrIssueClearance($schoolId, $studentId, $user);

        return back()
            ->with('message', $clearance->status === 'cleared'
                ? 'Surat Keterangan Bebas Pustaka berhasil diterbitkan.'
                : "Pengajuan Bebas Pustaka ditolak: {$clearance->notes}")
            ->with('library_clearance_result', $clearance->toArray());
    }

    public function downloadClearancePdf(Request $request, LibraryClearanceCertificate $clearance): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        abort_unless((int) $clearance->school_id === $schoolId && $clearance->status === 'cleared' && $clearance->clearance_document_id !== null, 404);

        $user = $request->user();
        abort_unless($user !== null && (int) $user->school_id === $schoolId, 403);

        $isLibrarian = $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::LIBRARIAN->value, RoleEnum::OPERATOR->value]);
        abort_unless($isLibrarian || $this->accessScope->canAccessStudentScope($user, $schoolId, (int) $clearance->student_id), 403);

        $file = $this->documentService->resolveDownloadByDocumentId($schoolId, (int) $clearance->clearance_document_id);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function manageIndex(Request $request): Response
    {
        $user = $request->user();
        $this->libraryService->assertLibrarian($user);
        $schoolId = (int) $user->school_id;

        $catalog = $this->libraryService->searchCatalog($schoolId, [], $user);
        $loans = LibraryLoan::query()
            ->with('collection:id,code,title')
            ->where('school_id', $schoolId)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (LibraryLoan $l): array => [
                'id' => (int) $l->id,
                'loan_code' => $l->loan_code,
                'collection_title' => $l->collection?->title ?? '-',
                'borrower_name' => $l->borrower_name,
                'borrower_identifier' => $l->borrower_identifier,
                'borrowed_at' => $l->borrowed_at?->toDateString(),
                'due_at' => $l->due_at?->toDateString(),
                'returned_at' => $l->returned_at?->toDateString(),
                'status' => $l->status,
            ])
            ->values()
            ->all();

        $procurements = LibraryProcurementRequest::query()
            ->where('school_id', $schoolId)
            ->latest('id')
            ->limit(50)
            ->get()
            ->toArray();

        $visits = LibraryVisit::query()
            ->where('school_id', $schoolId)
            ->latest('visited_at')
            ->limit(30)
            ->get()
            ->toArray();

        return Inertia::render('Library/Manage/Index', [
            'collections' => $catalog['items'],
            'categories' => collect(LibraryService::CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'loans' => $loans,
            'procurements' => $procurements,
            'visits' => $visits,
            'students' => $this->academicQuery->searchActiveStudentsForStaff($schoolId, '', 50),
            'stats' => $this->libraryService->statistics($schoolId),
        ]);
    }

    public function storeCollection(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->libraryService->assertLibrarian($user);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            'publisher' => ['nullable', 'string', 'max:150'],
            'publish_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'category' => ['required', Rule::in(array_keys(LibraryService::CATEGORIES))],
            'format' => ['required', Rule::in(['physical', 'ebook', 'hybrid'])],
            'total_copies' => ['required', 'integer', 'min:0', 'max:10000'],
            'shelf_location' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'ebook_url' => ['nullable', 'url', 'max:500'],
            'access_level' => ['required', Rule::in(['public', 'members_only', 'staff_only'])],
            'ebook_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $this->libraryService->saveCollection($user, $validated, $request->file('ebook_file'));

        return back()->with('message', 'Koleksi perpustakaan berhasil disimpan.');
    }

    public function borrow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'collection_id' => ['required', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'borrower_name' => ['nullable', 'string', 'max:150'],
            'borrower_identifier' => ['nullable', 'string', 'max:60'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->libraryService->borrowCollection($request->user(), $validated);

        return back()->with('message', 'Peminjaman koleksi berhasil dicatat.');
    }

    public function returnLoan(Request $request, LibraryLoan $loan): RedirectResponse
    {
        $this->libraryService->returnLoan($request->user(), $loan);

        return back()->with('message', 'Pengembalian koleksi berhasil dicatat.');
    }

    public function decideProcurement(Request $request, LibraryProcurementRequest $procurement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'acquired'])],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->libraryService->decideProcurement($request->user(), $procurement, $validated['status'], $validated['decision_note'] ?? null);

        return back()->with('message', 'Keputusan usulan pengadaan berhasil disimpan.');
    }
}
