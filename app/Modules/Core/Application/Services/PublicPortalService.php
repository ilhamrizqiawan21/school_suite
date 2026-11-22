<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\PublicIkmSurvey;
use App\Modules\Core\Infrastructure\Models\PublicVisitStat;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use App\Shared\Audit\AuditLogger;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Public portal domain services: downloads, IKM/Zona Integritas,
 * prayer schedule with local fallback, and anonymous visit counters (M4-PUB-05..07).
 */
class PublicPortalService
{
    public const DOWNLOAD_CATEGORIES = [
        'formulir' => 'Formulir',
        'kalender' => 'Kalender Akademik',
        'tata_tertib' => 'Tata Tertib',
        'brosur' => 'Brosur',
        'sop' => 'SOP Layanan',
        'dokumen_resmi' => 'Dokumen Resmi',
    ];

    /** @return array{careers: array, scholarships: array} */
    public function alumniOpportunities(int $schoolId): array
    {
        $rows = SchoolNews::query()->where('school_id', $schoolId)->where('status', 'published')
            ->where('is_archived', false)->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereIn('category', ['karir-alumni', 'beasiswa-alumni'])->orderByDesc('published_at')->limit(40)->get();
        $project = static fn ($news): array => ['id' => $news->id, 'title' => $news->title, 'excerpt' => $news->excerpt,
            'url' => route('core.news.public.show', ['slug' => $news->slug])];

        return ['careers' => $rows->where('category', 'karir-alumni')->map($project)->values()->all(),
            'scholarships' => $rows->where('category', 'beasiswa-alumni')->map($project)->values()->all()];
    }

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly PrivateStorageService $storage,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Build shared header/footer/modal data for the Madrasah public Blade theme.
     *
     * @return array<string, mixed>
     */
    public function madrasahShellData(School $school, bool $incrementVisit = false): array
    {
        $schoolId = (int) $school->id;

        $visits = $incrementVisit
            ? $this->recordAnonymousVisit($schoolId)
            : $this->visitStats($schoolId);

        $profile = [
            ...$school->only([
                'name', 'legal_name', 'npsn', 'level', 'school_status', 'accreditation',
                'address', 'phone', 'email', 'principal_name', 'website', 'timezone',
                'about', 'principal_message', 'vision', 'mission',
            ]),
            'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
            'favicon_url' => $school->favicon_path ? route('branding.school', [$school, 'favicon']) : null,
            'service_hours' => data_get($school->settings, 'service_hours', 'Senin – Jumat, 07.30 – 15.00'),
            'holiday_notice' => data_get($school->settings, 'holiday_notice'),
            'emergency_contact' => data_get($school->settings, 'emergency_contact'),
            'map_url' => data_get($school->settings, 'map_url'),
            'whatsapp_url' => data_get($school->settings, 'whatsapp_url'),
            'social_links' => data_get($school->settings, 'social_links', []),
        ];

        if (app()->isLocal() && (string) $school->npsn === '20109988' && config('public_portal.theme') !== 'madrasah' && ! request()->is('alumni*')) {
            $profile = ['name' => 'Madrasah Contoh Nusantara'];
        }

        $isDraftPreview = $this->isDraftPreviewRequested(request(), $school);
        $contentRegistry = $school->publicContentRegistry();
        $contentBlocks = $isDraftPreview ? $contentRegistry['draft'] : $contentRegistry['published'];

        return [
            'profile' => $profile,
            'contentBlocks' => $contentBlocks,
            'isDraftPreview' => $isDraftPreview,
            'hideDemoFallbacks' => (bool) ($contentBlocks['shell.options']['hide_demo_fallbacks'] ?? false),
            'visits' => $visits,
            'prayer' => $this->prayerTimesForSchool($school),
            'downloads' => $this->publishedDownloads($schoolId),
            'ikm' => $this->ikmSummary($schoolId),
            'integrityZone' => $this->integrityZoneConfig($school),
            'serviceCatalogOverrides' => $this->serviceCatalogOverrides($school),
        ];
    }

    /**
     * Check whether private draft preview is requested and strictly authorize it (AP-04.08, AP-04.09).
     */
    public function isDraftPreviewRequested(?Request $request, School $school): bool
    {
        if ($request === null || ! $request->boolean('preview_draft')) {
            return false;
        }

        $user = $request->user();
        if ($user === null || ! Gate::forUser($user)->allows('update', $school)) {
            abort(403, 'Pratinjau draf hanya dapat diakses oleh pengelola sekolah yang berwenang.');
        }

        return true;
    }

    /**
     * Apply private no-store cache headers when rendering a draft preview response.
     */
    public function applyPreviewResponseHeaders(HttpResponse $response, bool $isDraftPreview): HttpResponse
    {
        if ($isDraftPreview) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /**
     * Increment anonymous daily visit counter without storing any PII (M4-PUB-07).
     *
     * @return array{today: int, month: int, total: int}
     */
    public function recordAnonymousVisit(int $schoolId): array
    {
        $today = $this->schoolScope->localNow($schoolId)->toDateString();

        DB::transaction(function () use ($schoolId, $today): void {
            $stat = PublicVisitStat::query()
                ->where('school_id', $schoolId)
                ->whereDate('visit_date', $today)
                ->lockForUpdate()
                ->first();

            if ($stat === null) {
                PublicVisitStat::query()->create([
                    'school_id' => $schoolId,
                    'visit_date' => $today,
                    'hits' => 1,
                ]);

                return;
            }

            $stat->increment('hits');
        });

        return $this->visitStats($schoolId);
    }

    /**
     * @return array{today: int, month: int, total: int}
     */
    public function visitStats(int $schoolId): array
    {
        $now = $this->schoolScope->localNow($schoolId);
        $todayDate = $now->toDateString();
        $monthPrefix = $now->format('Y-m');

        $today = (int) PublicVisitStat::query()
            ->where('school_id', $schoolId)
            ->whereDate('visit_date', $todayDate)
            ->sum('hits');

        $month = (int) PublicVisitStat::query()
            ->where('school_id', $schoolId)
            ->whereBetween('visit_date', [$monthPrefix.'-01', $now->copy()->endOfMonth()->toDateString()])
            ->sum('hits');

        $total = (int) PublicVisitStat::query()
            ->where('school_id', $schoolId)
            ->sum('hits');

        return [
            'today' => $today,
            'month' => $month,
            'total' => $total,
        ];
    }

    /**
     * Return prayer schedule for school's timezone with deterministic local fallback (M4-PUB-07).
     *
     * @return array{
     *     timezone: string,
     *     date: string,
     *     location: string,
     *     source: string,
     *     is_fallback: bool,
     *     next_prayer: array{name: string, time: string},
     *     times: list<array{name: string, time: string}>
     * }
     */
    public function prayerTimesForSchool(School $school): array
    {
        $timezone = $this->schoolScope->getTimezone((int) $school->id);
        $localNow = $this->schoolScope->localNow((int) $school->id);
        $configured = data_get($school->settings, 'prayer_times');

        $times = is_array($configured) && $configured !== []
            ? $configured
            : [
                ['name' => 'Subuh', 'time' => '04:45'],
                ['name' => 'Dzuhur', 'time' => '12:10'],
                ['name' => 'Ashar', 'time' => '15:25'],
                ['name' => 'Maghrib', 'time' => '18:15'],
                ['name' => 'Isya', 'time' => '19:25'],
            ];

        $currentHm = $localNow->format('H:i');
        $nextPrayer = $times[0];
        foreach ($times as $slot) {
            if (($slot['time'] ?? '23:59') > $currentHm) {
                $nextPrayer = $slot;
                break;
            }
        }

        return [
            'timezone' => $timezone,
            'date' => $localNow->toDateString(),
            'location' => (string) (
                data_get($school->settings, 'prayer_city')
                ?: data_get($school->settings, 'city')
                ?: data_get($school->settings, 'letterhead.city')
                ?: $school->address
                ?: 'Wilayah Sekolah'
            ),
            'source' => is_array($configured) && $configured !== [] ? 'school_settings' : 'local_fallback',
            'is_fallback' => ! (is_array($configured) && $configured !== []),
            'next_prayer' => [
                'name' => (string) $nextPrayer['name'],
                'time' => (string) $nextPrayer['time'],
            ],
            'times' => array_values(array_map(static fn (array $slot): array => [
                'name' => (string) ($slot['name'] ?? ''),
                'time' => (string) ($slot['time'] ?? ''),
            ], $times)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedDownloads(int $schoolId, ?string $category = null): array
    {
        return PublicDownload::query()
            ->where('school_id', $schoolId)
            ->where('is_published', true)
            ->when($category !== null && $category !== '', function ($query) use ($category): void {
                $query->where('category', $category);
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(static fn (PublicDownload $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'category_label' => self::DOWNLOAD_CATEGORIES[$item->category] ?? $item->category,
                'title' => $item->title,
                'description' => $item->description,
                'version' => $item->version,
                'original_name' => $item->original_name,
                'mime_type' => $item->mime_type,
                'size_bytes' => $item->size_bytes,
                'download_count' => $item->download_count,
                'download_url' => route('core.public.downloads.file', $item),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{category: string, title: string, description?: ?string, version?: int, is_published?: bool}  $data
     */
    public function storeDownload(int $schoolId, UploadedFile $file, array $data, ?User $actor = null): PublicDownload
    {
        $stored = $this->storage->storeValidatedUpload(
            $file,
            sprintf('schools/%d/public_downloads', $schoolId),
            PrivateStorageService::DEFAULT_ALLOWED_MIMES,
            10_485_760,
        );

        $download = PublicDownload::query()->create([
            'school_id' => $schoolId,
            'category' => $data['category'],
            'title' => trim($data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'version' => (int) ($data['version'] ?? 1),
            'file_path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
            'size_bytes' => $stored['size_bytes'],
            'is_published' => (bool) ($data['is_published'] ?? true),
            'download_count' => 0,
            'uploaded_by' => $actor?->id,
        ]);

        $this->auditLogger->log(
            eventType: 'public_download.created',
            targetType: 'PublicDownload',
            targetId: (string) $download->id,
            newValues: [
                'category' => $download->category,
                'title' => $download->title,
                'is_published' => $download->is_published,
            ],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $download;
    }

    /**
     * @return array{
     *     average_rating: float,
     *     total_responses: int,
     *     approved_feedback: list<array{id: int, respondent_name: string, respondent_role: string, service_name: string, rating: int, comment: ?string, created_at: ?string}>
     * }
     */
    public function ikmSummary(int $schoolId): array
    {
        $approvedQuery = PublicIkmSurvey::query()
            ->where('school_id', $schoolId)
            ->where('is_approved', true);

        $total = (int) (clone $approvedQuery)->count();
        $average = $total > 0 ? round((float) (clone $approvedQuery)->avg('rating'), 2) : 0.0;

        $feedback = (clone $approvedQuery)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(static fn (PublicIkmSurvey $item): array => [
                'id' => $item->id,
                'respondent_name' => $item->respondent_name ?: 'Responden',
                'respondent_role' => $item->respondent_role,
                'service_name' => $item->service_name,
                'rating' => $item->rating,
                'comment' => $item->comment,
                'created_at' => $item->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'average_rating' => $average,
            'total_responses' => $total,
            'approved_feedback' => $feedback,
        ];
    }

    /**
     * @param  array{respondent_name?: ?string, respondent_role: string, service_name: string, rating: int, comment?: ?string}  $data
     */
    public function submitIkmSurvey(int $schoolId, array $data, ?string $ipAddress = null): PublicIkmSurvey
    {
        return PublicIkmSurvey::query()->create([
            'school_id' => $schoolId,
            'respondent_name' => isset($data['respondent_name']) && trim((string) $data['respondent_name']) !== ''
                ? trim((string) $data['respondent_name'])
                : null,
            'respondent_role' => $data['respondent_role'],
            'service_name' => trim($data['service_name']),
            'rating' => max(1, min(5, (int) $data['rating'])),
            'comment' => isset($data['comment']) && trim((string) $data['comment']) !== ''
                ? trim((string) $data['comment'])
                : null,
            'is_approved' => false,
            'submitted_ip_hash' => $ipAddress ? hash('sha256', $schoolId.'|'.$ipAddress) : null,
        ]);
    }

    public function approveIkmSurvey(int $surveyId, int $schoolId, bool $approved, ?User $actor = null): PublicIkmSurvey
    {
        /** @var PublicIkmSurvey $survey */
        $survey = PublicIkmSurvey::query()
            ->where('school_id', $schoolId)
            ->whereKey($surveyId)
            ->firstOrFail();

        $survey->update(['is_approved' => $approved]);

        $this->auditLogger->log(
            eventType: 'public_ikm.moderated',
            targetType: 'PublicIkmSurvey',
            targetId: (string) $survey->id,
            newValues: ['is_approved' => $approved],
            actor: $actor,
            schoolId: $schoolId,
        );

        return $survey->refresh();
    }

    public function deleteIkmSurvey(int $surveyId, int $schoolId, ?User $actor = null): void
    {
        /** @var PublicIkmSurvey $survey */
        $survey = PublicIkmSurvey::query()
            ->where('school_id', $schoolId)
            ->whereKey($surveyId)
            ->firstOrFail();

        $oldValues = $survey->only(['respondent_name', 'respondent_role', 'service_name', 'rating', 'is_approved']);
        $survey->delete();

        $this->auditLogger->log(
            eventType: 'public_ikm.deleted',
            targetType: 'PublicIkmSurvey',
            targetId: (string) $surveyId,
            oldValues: $oldValues,
            actor: $actor,
            schoolId: $schoolId,
        );
    }

    /**
     * Update an existing public download metadata and optionally replace its stored file (AP-07.01, AP-07.02).
     *
     * @param  array{category: string, title: string, description?: ?string, version?: int, is_published?: bool}  $data
     */
    public function updateDownload(PublicDownload $download, array $data, ?UploadedFile $file = null, ?User $actor = null): PublicDownload
    {
        $oldValues = $download->only(['category', 'title', 'description', 'version', 'is_published', 'file_path']);
        $updates = [
            'category' => $data['category'],
            'title' => trim($data['title']),
            'description' => isset($data['description']) && trim((string) $data['description']) !== ''
                ? trim((string) $data['description'])
                : null,
            'version' => (int) ($data['version'] ?? $download->version ?? 1),
            'is_published' => (bool) ($data['is_published'] ?? $download->is_published),
        ];

        $oldPath = null;
        if ($file instanceof UploadedFile) {
            $stored = $this->storage->storeValidatedUpload(
                $file,
                sprintf('schools/%d/public_downloads', (int) $download->school_id),
                PrivateStorageService::DEFAULT_ALLOWED_MIMES,
                10_485_760,
            );
            $oldPath = $download->file_path;
            $updates['file_path'] = $stored['path'];
            $updates['original_name'] = $stored['original_name'];
            $updates['mime_type'] = $stored['mime_type'];
            $updates['size_bytes'] = $stored['size_bytes'];
        }

        $download->update($updates);
        if ($oldPath !== null && $oldPath !== $download->file_path) {
            $this->storage->delete($oldPath);
        }

        $this->auditLogger->log(
            eventType: 'public_download.updated',
            targetType: 'PublicDownload',
            targetId: (string) $download->id,
            newValues: $download->only(['category', 'title', 'version', 'is_published']),
            oldValues: $oldValues,
            actor: $actor,
            schoolId: (int) $download->school_id,
        );

        return $download->refresh();
    }

    public function deleteDownload(PublicDownload $download, ?User $actor = null): void
    {
        $oldValues = $download->only(['category', 'title', 'version', 'is_published', 'file_path']);
        $schoolId = (int) $download->school_id;
        $downloadId = (string) $download->id;
        $filePath = $download->file_path;

        $download->delete();
        if ($filePath) {
            $this->storage->delete($filePath);
        }

        $this->auditLogger->log(
            eventType: 'public_download.deleted',
            targetType: 'PublicDownload',
            targetId: $downloadId,
            oldValues: $oldValues,
            actor: $actor,
            schoolId: $schoolId,
        );
    }

    /**
     * Default video gallery items seeded idempotently when school has not customized videos yet (AP-06.06, PUB-11).
     *
     * @return list<array<string, mixed>>
     */
    public static function defaultPublicVideos(?School $school = null): array
    {
        $schoolName = $school?->name ?: 'Madrasah Contoh Nusantara';

        return [
            [
                'id' => 'tur-kampus',
                'title' => 'Tur Kampus Madrasah & Lingkungan Belajar Terpadu',
                'category' => 'Profil',
                'category_label' => 'Profil Madrasah',
                'date' => '11 Oct 2026',
                'description' => 'Klip ilustrasi suasana kampus, sarana pembelajaran, laboratorium, dan ruang terbuka hijau '.$schoolName.'.',
                'poster' => '/madrasah-theme/images/videos/tur-kampus.webp',
                'src' => '/madrasah-theme/images/videos/tur-kampus.webm',
                'is_featured' => true,
                'is_published' => true,
                'is_demo' => true,
                'sort_order' => 1,
            ],
            [
                'id' => 'praktikum-sains',
                'title' => 'Praktikum Sains & Pembinaan Riset Ilmiah Siswa',
                'category' => 'Prestasi',
                'category_label' => 'Prestasi & Riset Sains',
                'date' => '06 Oct 2026',
                'description' => 'Kegiatan eksperimen laboratorium kimia dan fisika terapan oleh tim peneliti muda madrasah.',
                'poster' => '/madrasah-theme/images/videos/praktikum-sains.webp',
                'src' => '/madrasah-theme/images/videos/praktikum-sains.webm',
                'is_featured' => false,
                'is_published' => true,
                'is_demo' => true,
                'sort_order' => 2,
            ],
            [
                'id' => 'kegiatan-literasi',
                'title' => 'Kegiatan Literasi & Halaqah Keagamaan Santri',
                'category' => 'Keagamaan',
                'category_label' => 'Halaqah & Keagamaan',
                'date' => '28 Sep 2026',
                'description' => 'Pembiasaan tadarus pagi, kajian kitab, serta pojok literasi di E-Perpustakaan Digital.',
                'poster' => '/madrasah-theme/images/videos/kegiatan-literasi.webp',
                'src' => '/madrasah-theme/images/videos/kegiatan-literasi.webm',
                'is_featured' => false,
                'is_published' => true,
                'is_demo' => true,
                'sort_order' => 3,
            ],
            [
                'id' => 'karya-siswa',
                'title' => 'Pameran Karya Siswa & Inovasi Teknologi Terapan',
                'category' => 'Prestasi',
                'category_label' => 'Prestasi & Riset Sains',
                'date' => '19 Sep 2026',
                'description' => 'Gelar karya proyek kreatif, robotik, dan inovasi sains siswa lintas peminatan.',
                'poster' => '/madrasah-theme/images/videos/karya-siswa.webp',
                'src' => '/madrasah-theme/images/videos/karya-siswa.webm',
                'is_featured' => false,
                'is_published' => true,
                'is_demo' => true,
                'sort_order' => 4,
            ],
            [
                'id' => 'madrasah-hijau',
                'title' => 'Program Madrasah Hijau & Gerakan Adiwiyata Mandiri',
                'category' => 'Kegiatan',
                'category_label' => 'Kegiatan Santri & Siswa',
                'date' => '10 Sep 2026',
                'description' => 'Aksi pelestarian lingkungan, bank sampah organik, dan perawatan taman belajar asri.',
                'poster' => '/madrasah-theme/images/videos/madrasah-hijau.webp',
                'src' => '/madrasah-theme/images/videos/madrasah-hijau.webm',
                'is_featured' => false,
                'is_published' => true,
                'is_demo' => true,
                'sort_order' => 5,
            ],
            [
                'id' => 'orientasi-siswa',
                'title' => 'Orientasi Siswa Baru & Pengenalan Budaya Madrasah',
                'category' => 'Kegiatan',
                'category_label' => 'Kegiatan Santri & Siswa',
                'date' => '02 Sep 2026',
                'description' => 'Penyambutan peserta didik baru dan pengenalan lima nilai budaya kerja madrasah.',
                'poster' => '/madrasah-theme/images/videos/orientasi-siswa.webp',
                'src' => '/madrasah-theme/images/videos/orientasi-siswa.webm',
                'is_featured' => false,
                'is_published' => true,
                'is_demo' => true,
                'sort_order' => 6,
            ],
        ];
    }

    /**
     * Return managed video gallery items for a school (admin sees all; public sees published only).
     *
     * @return list<array<string, mixed>>
     */
    public function publicVideosForSchool(School $school, bool $publishedOnly = true): array
    {
        $settings = is_array($school->settings) ? $school->settings : [];
        $hasCustomVideos = array_key_exists('public_videos', $settings) && is_array($settings['public_videos']);
        $rawVideos = $hasCustomVideos
            ? $settings['public_videos']
            : self::defaultPublicVideos($school);

        $categoryLabels = [
            'Profil' => 'Profil Madrasah',
            'Kegiatan' => 'Kegiatan Santri & Siswa',
            'Prestasi' => 'Prestasi & Riset Sains',
            'Keagamaan' => 'Halaqah & Keagamaan',
        ];

        $normalized = [];
        foreach ($rawVideos as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $category = (string) ($item['category'] ?? 'Kegiatan');
            $normalized[] = [
                'id' => (string) ($item['id'] ?? ('video-'.($index + 1))),
                'title' => (string) ($item['title'] ?? 'Video Dokumentasi'),
                'category' => $category,
                'category_label' => (string) ($item['category_label'] ?? ($categoryLabels[$category] ?? $category)),
                'date' => (string) ($item['date'] ?? now()->format('d M Y')),
                'description' => (string) ($item['description'] ?? ''),
                'poster' => (string) ($item['poster'] ?? '/madrasah-theme/images/videos/tur-kampus.webp'),
                'src' => (string) ($item['src'] ?? '/madrasah-theme/images/videos/tur-kampus.webm'),
                'is_featured' => (bool) ($item['is_featured'] ?? false),
                'is_published' => (bool) ($item['is_published'] ?? true),
                'is_demo' => (bool) ($item['is_demo'] ?? ! $hasCustomVideos),
                'sort_order' => (int) ($item['sort_order'] ?? ($index + 1)),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => ($a['sort_order'] <=> $b['sort_order']));

        if ($publishedOnly) {
            $hideDemo = (bool) ($school->publicContentRegistry()['published']['shell.options']['hide_demo_fallbacks'] ?? false);
            $normalized = array_values(array_filter(
                $normalized,
                static fn (array $v): bool => $v['is_published'] && (! $hideDemo || ! $v['is_demo']),
            ));
        }

        return $normalized;
    }

    /**
     * Return Zona Integritas configuration (declaration, 6 areas, service options) with defaults (AP-07.03).
     *
     * @return array{
     *     declaration: string,
     *     sop_category: string,
     *     areas: list<string>,
     *     service_options: list<string>
     * }
     */
    public function integrityZoneConfig(School $school): array
    {
        $configured = data_get($school->settings, 'integrity_zone');
        $configured = is_array($configured) ? $configured : [];

        $defaultAreas = [
            'Manajemen Perubahan',
            'Penataan Tatalaksana',
            'Penataan Manajemen SDM',
            'Penguatan Akuntabilitas Kinerja',
            'Penguatan Pengawasan',
            'Peningkatan Kualitas Pelayanan Publik',
        ];

        $defaultServices = [
            'PTSP & Tata Usaha',
            'Legalisir Ijazah & SKL',
            'Mutasi & Perizinan Siswa',
            'Perpustakaan Digital',
            'SPMB / PPDB Online',
        ];

        $areas = isset($configured['areas']) && is_array($configured['areas'])
            ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $configured['areas'])))
            : [];

        $services = isset($configured['service_options']) && is_array($configured['service_options'])
            ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $configured['service_options'])))
            : [];

        return [
            'declaration' => isset($configured['declaration']) && trim((string) $configured['declaration']) !== ''
                ? trim((string) $configured['declaration'])
                : 'Kami berkomitmen menyelenggarakan pelayanan pendidikan dan tata usaha yang cepat, transparan, akuntabel, bebas pungutan liar, dan berorientasi kepuasan masyarakat.',
            'sop_category' => isset($configured['sop_category']) && array_key_exists((string) $configured['sop_category'], self::DOWNLOAD_CATEGORIES)
                ? (string) $configured['sop_category']
                : 'sop',
            'areas' => $areas !== [] ? $areas : $defaultAreas,
            'service_options' => $services !== [] ? $services : $defaultServices,
        ];
    }

    /**
     * Return admin-configured E-Layanan catalog overrides keyed by service ID (AP-08.02, AP-08.05).
     *
     * @return array<string, array{available: bool, badge: string, maintenance_message: ?string}>
     */
    public function serviceCatalogOverrides(School $school): array
    {
        $raw = data_get($school->settings, 'service_catalog');
        if (! is_array($raw)) {
            return [];
        }

        $overrides = [];
        foreach ($raw as $serviceId => $config) {
            if (! is_string($serviceId) || ! is_array($config)) {
                continue;
            }
            $available = (bool) ($config['available'] ?? true);
            $overrides[$serviceId] = [
                'available' => $available,
                'badge' => isset($config['badge']) && trim((string) $config['badge']) !== ''
                    ? trim((string) $config['badge'])
                    : ($available ? 'Aktif' : 'Pemeliharaan'),
                'maintenance_message' => isset($config['maintenance_message']) && trim((string) $config['maintenance_message']) !== ''
                    ? trim((string) $config['maintenance_message'])
                    : null,
            ];
        }

        return $overrides;
    }
}
