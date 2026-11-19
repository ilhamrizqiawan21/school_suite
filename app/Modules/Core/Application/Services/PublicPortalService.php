<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\PublicIkmSurvey;
use App\Modules\Core\Infrastructure\Models\PublicVisitStat;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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

        return [
            'profile' => [
                ...$school->only([
                    'name', 'legal_name', 'level', 'school_status', 'accreditation',
                    'address', 'phone', 'email', 'principal_name', 'website',
                    'about', 'principal_message', 'vision', 'mission',
                ]),
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
            ],
            'visits' => $visits,
            'prayer' => $this->prayerTimesForSchool($school),
            'downloads' => $this->publishedDownloads($schoolId),
            'ikm' => $this->ikmSummary($schoolId),
        ];
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
            'location' => (string) (data_get($school->settings, 'city') ?: $school->address ?: 'Wilayah Sekolah'),
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
}
