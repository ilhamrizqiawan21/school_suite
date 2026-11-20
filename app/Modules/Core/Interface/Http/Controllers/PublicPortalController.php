<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicPortalController extends Controller
{
    public function __construct(
        private readonly PublicPortalService $portalService,
        private readonly AcademicQueryService $academicQuery,
        private readonly PrivateStorageService $storage,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function services(Request $request): Response
    {
        $school = School::query()->firstOrFail();

        return Inertia::render('Public/Services/Index', [
            'school' => $this->schoolPayload($school),
            'initialQuery' => (string) $request->query('q', ''),
        ]);
    }

    public function teachers(Request $request): Response|HttpResponse
    {
        $school = School::query()->orderBy('id')->firstOrFail();
        $search = trim((string) $request->query('q', ''));
        $position = trim((string) $request->query('mapel', ''));
        $allTeachers = $this->academicQuery->getPublicDirectoryTeachers((int) $school->id);
        $teachers = $this->academicQuery->getPublicDirectoryTeachers((int) $school->id, $search, $position);

        if ($this->schoolScope->usesMadrasahPublicTheme((string) $school->npsn)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            return response()->view('public.madrasah.teachers', [
                ...$this->portalService->madrasahShellData($school),
                'teachers' => $teachers,
                'availablePositions' => array_values(array_unique(array_column($allTeachers, 'position'))),
                'usesDemoTeachers' => $allTeachers === [],
                'filters' => ['q' => $search, 'mapel' => $position],
            ]);
        }

        return Inertia::render('Public/Directory/Teachers', [
            'school' => $this->schoolPayload($school),
            'teachers' => $teachers,
            'filters' => ['q' => $search],
        ]);
    }

    public function achievements(Request $request): Response|HttpResponse
    {
        $school = School::query()->orderBy('id')->firstOrFail();

        $items = SchoolNews::query()
            ->where('school_id', $school->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereIn('category', ['prestasi', 'agenda'])
            ->latest('published_at')
            ->limit(24)
            ->get(['id', 'title', 'slug', 'category', 'excerpt', 'published_at'])
            ->map(static fn (SchoolNews $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'category' => $item->category,
                'excerpt' => $item->excerpt,
                'published_at' => $item->published_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        if ($this->schoolScope->usesMadrasahPublicTheme((string) $school->npsn)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            return response()->view('public.madrasah.achievements', [
                ...$this->portalService->madrasahShellData($school),
                'items' => $items,
            ]);
        }

        return Inertia::render('Public/School/Achievements', [
            'school' => $this->schoolPayload($school),
            'items' => $items,
        ]);
    }

    public function downloads(Request $request): Response
    {
        $school = School::query()->firstOrFail();
        $category = trim((string) $request->query('category', ''));

        return Inertia::render('Public/Downloads/Index', [
            'school' => $this->schoolPayload($school),
            'categories' => PublicPortalService::DOWNLOAD_CATEGORIES,
            'selectedCategory' => $category,
            'downloads' => $this->portalService->publishedDownloads((int) $school->id, $category),
        ]);
    }

    public function downloadFile(PublicDownload $download): BinaryFileResponse
    {
        $school = School::query()->firstOrFail();

        if ((int) $download->school_id !== (int) $school->id || ! $download->is_published) {
            abort(404);
        }

        if (! $this->storage->exists($download->file_path)) {
            abort(404);
        }

        $download->increment('download_count');

        $response = response()->download(
            $this->storage->path($download->file_path),
            $download->original_name,
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function integrityZone(): Response
    {
        $school = School::query()->firstOrFail();

        return Inertia::render('Public/Integrity/Index', [
            'school' => $this->schoolPayload($school),
            'ikm' => $this->portalService->ikmSummary((int) $school->id),
        ]);
    }

    public function submitIkm(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'respondent_name' => ['nullable', 'string', 'max:120'],
            'respondent_role' => ['required', 'in:siswa,wali,alumni,tamu,umum'],
            'service_name' => ['required', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->portalService->submitIkmSurvey((int) $school->id, $validated, $request->ip());

        return back()->with('message', 'Penilaian layanan Anda telah dikirim dan menunggu moderasi admin.');
    }

    public function prayerAndStats(): Response
    {
        $school = School::query()->firstOrFail();

        return Inertia::render('Public/Prayer/Index', [
            'school' => $this->schoolPayload($school),
            'prayerSchedule' => $this->portalService->prayerTimesForSchool($school),
            'visitStats' => $this->portalService->visitStats((int) $school->id),
        ]);
    }

    /** @return array<string, mixed> */
    private function schoolPayload(School $school): array
    {
        return [
            ...$school->only([
                'name', 'legal_name', 'level', 'school_status', 'accreditation',
                'address', 'phone', 'email', 'website', 'timezone',
            ]),
            'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
            'theme' => $school->publicTheme(),
        ];
    }
}
