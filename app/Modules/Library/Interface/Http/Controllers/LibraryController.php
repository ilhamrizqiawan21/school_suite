<?php

declare(strict_types=1);

namespace App\Modules\Library\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function replica(Request $request): \Illuminate\Http\Response
    {
        $school = $this->schoolScope->findPrimaryPublicSnapshot();
        $schoolId = (int) ($school['id'] ?? 0);
        $legacyCategories = ['1' => 'agama', '2' => 'buku_teks', '3' => 'referensi', '4' => 'fiksi', '6' => 'jurnal'];
        $category = (string) $request->query('kategori', $legacyCategories[(string) $request->query('kat', '')] ?? '');
        $search = (string) $request->query('q', $request->query('kat') === '5' ? 'teknologi' : '');
        $featuredId = (int) ($school['content_blocks']['library.spotlight']['collection_id'] ?? 0);
        $featured = $schoolId > 0 ? $this->libraryService->searchCatalog($schoolId, ['id' => $featuredId], $request->user())['items'][0] ?? null : null;
        $catalog = $schoolId > 0 ? $this->libraryService->searchCatalog($schoolId, [
            'search' => $search,
            'category' => $category,
            'format' => $request->query('format', ''),
        ], $request->user()) : ['items' => []];

        return response()->view('public.madrasah.perpustakaan', [
            'school' => $school,
            'stats' => $schoolId > 0 ? $this->libraryService->statistics($schoolId) : [],
            'collections' => $catalog['items'],
            'featuredCollection' => $featured, 'selectedCategory' => $category, 'search' => $search,
            'contentBlocks' => $school['content_blocks'] ?? [],
            'categories' => LibraryService::CATEGORIES,
            'memberLoggedIn' => $request->user() !== null && $request->user()->is_active && (int) $request->user()->school_id === $schoolId,
        ])->header('Cache-Control', 'private, no-store');
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
        if ($user !== null && (! $user->is_active || (int) $user->school_id !== $schoolId)) {
            $user = null;
        }

        if ($pg === 'login_siswa') {
            abort_unless($request->isMethod('post'), 405);
            $data = $request->validate(['username' => ['required', 'string', 'max:150'], 'password' => ['required', 'string', 'max:255']]);
            $key = 'library-login:'.hash('sha256', mb_strtolower($data['username']).'|'.$request->ip());
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json(['status' => 'error', 'message' => 'Terlalu banyak percobaan. Coba lagi beberapa menit.'], 429);
            }
            $studentUserId = $this->academicQuery->resolveActiveStudentUserId($schoolId, $data['username']);
            $candidate = User::query()->where('school_id', $schoolId)->where('is_active', true)
                ->where(fn ($q) => $q->where('username', $data['username'])->orWhere('email', $data['username'])
                    ->when($studentUserId !== null, fn ($sub) => $sub->orWhere('id', $studentUserId)))
                ->first();
            if ($candidate === null || ! $candidate->hasRole(RoleEnum::STUDENT->value) || ! Hash::check($data['password'], $candidate->password)) {
                RateLimiter::hit($key, 300);

                return response()->json(['status' => 'error', 'message' => 'Akun atau password tidak sesuai.'], 422);
            }
            Auth::login($candidate);
            $request->session()->regenerate();
            RateLimiter::clear($key);

            return response()->json(['status' => 'success', 'message' => 'Berhasil masuk ke perpustakaan.', 'csrf_token' => csrf_token()]);
        }

        if ($pg === 'logout_siswa') {
            abort_unless($request->isMethod('post'), 405);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['status' => 'success', 'message' => 'Anda sudah keluar.', 'csrf_token' => csrf_token()]);
        }

        if ($pg === 'booking_pinjam') {
            abort_unless($request->isMethod('post'), 405);
            if ($user === null || ! $user->is_active || (int) $user->school_id !== $schoolId) {
                return response()->json(['status' => 'need_login', 'message' => 'Silakan masuk ke akun siswa.'], 401);
            }
            $data = $request->validate(['id_buku' => ['required', 'integer', 'min:1'], 'catatan' => ['nullable', 'string', 'max:1500']]);
            $loan = $this->libraryService->reserveCollection($user, (int) $data['id_buku'], $data['catatan'] ?? null);

            return response()->json(['status' => 'success', 'message' => 'Kode reservasi: '.$loan->loan_code.'. Tunjukkan kode ini kepada pustakawan.', 'loan_code' => $loan->loan_code]);
        }

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

            $book = [
                'id' => (int) $collection->id,
                'id_buku' => (int) $collection->id,
                'nama_kategori' => LibraryService::CATEGORIES[$collection->category] ?? $collection->category,
                'lokasi_rak' => $collection->shelf_location ?? '-',
                'kode_buku' => $collection->code,
                'jumlah_total' => (int) $collection->total_copies,
                'jumlah_tersedia' => (int) $collection->available_copies,
                'tahun_terbit' => $collection->publish_year,
                'deskripsi' => $collection->description ?? '',
                'cover_url' => '/madrasah-theme/perpustakaan/database.png',
                'ebook_url' => $collection->ebook_document_id !== null ? route('library.collections.download', $collection->id) : $collection->ebook_url,
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
            ];

            return response()->json(['status' => 'success', 'data' => $book, ...$book]);
        }

        if ($pg === 'statistik_kunjungan') {
            return response()->json(['status' => 'success', ...$this->libraryService->publicVisitStatistics($schoolId)]);
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
            $request->merge([
                'visitor_name' => $request->input('visitor_name', $request->input('nama_tamu', $request->input('nama_guru'))),
                'visitor_role' => $request->input('visitor_role', match ($request->input('tipe_tamu')) {
                    'siswa' => 'student', 'guru' => 'teacher', default => 'guest'
                }),
                'purpose' => $request->input('purpose', $request->input('keperluan') === 'Lainnya' ? $request->input('keperluan_lainnya') : $request->input('keperluan')),
            ]);
            $validated = $request->validate([
                'card_token' => ['nullable', 'string', 'max:80'],
                'nisn_or_nis' => ['nullable', 'string', 'max:60'],
                'visitor_name' => ['nullable', 'string', 'max:150'],
                'visitor_identifier' => ['nullable', 'string', 'max:60'],
                'visitor_role' => ['nullable', Rule::in(['student', 'teacher', 'guest'])],
                'purpose' => ['nullable', 'string', 'max:180'],
            ]);
            if ($request->filled('nisn_or_nis')) {
                $studentId = $this->academicQuery->resolveActiveStudentIdByNumber($schoolId, (string) $request->input('nisn_or_nis'));
                if ($studentId === null) {
                    throw ValidationException::withMessages(['nisn_or_nis' => 'Identitas siswa tidak valid.']);
                }
                $validated['student_id'] = $studentId;
            }
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
            $request->merge([
                'title' => $request->input('title', $request->input('judul_buku')),
                'author' => $request->input('author', $request->input('penulis')),
                'reason' => $request->input('reason', $request->input('alasan')),
            ]);
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
            abort_unless($request->isMethod('post'), 405);
            if ($user === null) {
                return response()->json(['status' => 'need_login', 'message' => 'Silakan masuk untuk memeriksa bebas pustaka.'], 401);
            }
            $data = $request->validate(['nisn_or_nis' => ['required', 'string', 'max:60']]);
            $students = $this->academicQuery->getStudentsForUserScope($schoolId, $user);
            $student = collect($students)->first(fn ($s) => in_array($data['nisn_or_nis'], [(string) $s['nis'], (string) ($s['nisn'] ?? '')], true));
            abort_unless($student !== null, 403);
            $loans = LibraryLoan::query()->with('collection:id,title')->where('school_id', $schoolId)
                ->where('student_id', $student['student_id'])->whereIn('status', ['borrowed', 'overdue', 'lost'])->get();
            $certificate = $loans->isEmpty() ? $this->libraryService->checkOrIssueClearance($schoolId, (int) $student['student_id'], $user) : null;

            return response()->json([
                'status' => 'success', 'is_bebas' => $loans->isEmpty(), 'nama_siswa' => $student['name'],
                'kelas' => $student['class_group_name'] ?? '—', 'nisn' => $student['nisn'] ?? $student['nis'],
                'tanggungan' => $loans->count(), 'list_buku' => $loans->map(fn ($loan) => ['judul' => $loan->collection?->title ?? '—', 'tgl_jatuh_tempo' => $loan->due_at?->toDateString()])->all(),
                'print_url' => $certificate !== null ? route('library.clearance.pdf', $certificate->id) : null,
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
                'logo_url' => $school['logo_url'] ?? null,
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
            'nisn_or_nis' => ['nullable', 'string', 'max:60'],
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

    public function confirmReservation(Request $request, LibraryLoan $loan): RedirectResponse
    {
        $this->libraryService->confirmReservation($request->user(), $loan);

        return back()->with('message', 'Reservasi dikonfirmasi menjadi peminjaman.');
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
