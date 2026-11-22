<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\School;
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
            'serviceOverrides' => $this->portalService->serviceCatalogOverrides($school),
        ]);
    }

    public function teachers(Request $request): Response|HttpResponse
    {
        $school = School::query()->orderBy('id')->firstOrFail();
        $search = trim((string) $request->query('q', ''));
        $position = trim((string) $request->query('mapel', ''));
        $allTeachers = $this->academicQuery->getPublicDirectoryTeachers((int) $school->id);
        $teachers = $this->academicQuery->getPublicDirectoryTeachers((int) $school->id, $search, $position);
        $shell = $this->portalService->madrasahShellData($school);
        $hideDemo = (bool) ($shell['hideDemoFallbacks'] ?? false);

        if ($this->schoolScope->usesMadrasahPublicTheme((string) $school->npsn)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            return $this->portalService->applyPreviewResponseHeaders(
                response()->view('public.madrasah.teachers', [
                    ...$shell,
                    'teachers' => $teachers,
                    'availablePositions' => array_values(array_unique(array_column($allTeachers, 'position'))),
                    'usesDemoTeachers' => $allTeachers === [] && ! $hideDemo,
                    'filters' => ['q' => $search, 'mapel' => $position],
                ]),
                (bool) ($shell['isDraftPreview'] ?? false),
            );
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

        $items = $this->portalService->publishedAchievements((int) $school->id);

        if ($this->schoolScope->usesMadrasahPublicTheme((string) $school->npsn)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            $shell = $this->portalService->madrasahShellData($school);

            return $this->portalService->applyPreviewResponseHeaders(
                response()->view('public.madrasah.achievements', [
                    ...$shell,
                    'items' => $items,
                ]),
                (bool) ($shell['isDraftPreview'] ?? false),
            );
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

        $filename = trim((string) $download->original_name);
        if ($filename === '' || preg_match('/[\x00-\x1F\x7F\r\n]/', $filename) === 1) {
            $filename = basename($download->file_path) ?: 'download';
        }

        $response = response()->download(
            $this->storage->path($download->file_path),
            $filename,
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
            'integrityConfig' => $this->portalService->integrityZoneConfig($school),
        ]);
    }

    public function submitIkm(Request $request): RedirectResponse
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'respondent_name' => ['nullable', 'string', 'max:120'],
            'respondent_role' => ['required', 'in:siswa,wali,alumni,tamu,umum'],
            'service_name' => ['required', 'string', 'max:120'],
            'rating' => ['required_without:answers', 'nullable', 'integer', 'between:1,5'],
            'instrument_version' => ['nullable', 'string', 'size:64'],
            'submission_key' => ['nullable', 'uuid'],
            'answers' => ['nullable', 'array', 'min:1', 'max:20'],
            'answers.*' => ['required', 'integer', 'between:1,5'],
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
            'logo_url' => $school->publicBrandingUrl('logo'),
            'theme' => $school->publicTheme(),
        ];
    }
}
