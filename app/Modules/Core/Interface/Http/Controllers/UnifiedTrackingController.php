<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\UnifiedTrackingService;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnifiedTrackingController extends Controller
{
    public function __construct(
        private readonly UnifiedTrackingService $tracking,
        private readonly ExternalApplicantAuthService $applicantAuth,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function index(Request $request): Response
    {
        $school = School::query()->firstOrFail();
        $code = trim((string) $request->query('code', ''));
        $category = (string) $request->query('category', 'all');
        $secret = trim((string) $request->query('secret', ''));

        $result = null;
        if ($code !== '') {
            $applicant = $this->applicantAuth->currentFromSession($request, (int) $school->id);
            $result = $this->tracking->track(
                schoolId: (int) $school->id,
                ticketCode: $code,
                category: $category,
                verificationSecret: $secret !== '' ? $secret : null,
                viewer: $request->user(),
                externalApplicantId: $applicant ? (int) $applicant['id'] : null,
            );
        }

        $snapshot = $this->schoolScope->getPrimaryPublicSnapshot();

        return Inertia::render('Public/Tracking/Index', [
            'school' => $this->schoolPayload($school, $snapshot),
            'categories' => collect(UnifiedTrackingService::TRACKING_CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => [
                'code' => $code,
                'category' => array_key_exists($category, UnifiedTrackingService::TRACKING_CATEGORIES) ? $category : 'all',
            ],
            'result' => $result,
            'contentBlocks' => $snapshot['content_blocks'] ?? [],
        ]);
    }

    public function lookup(Request $request): Response
    {
        $school = School::query()->firstOrFail();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:40'],
            'verification_secret' => ['nullable', 'string', 'max:80'],
        ]);

        $category = (string) ($validated['category'] ?? 'all');
        $applicant = $this->applicantAuth->currentFromSession($request, (int) $school->id);

        $result = $this->tracking->track(
            schoolId: (int) $school->id,
            ticketCode: $validated['code'],
            category: $category,
            verificationSecret: $validated['verification_secret'] ?? null,
            viewer: $request->user(),
            externalApplicantId: $applicant ? (int) $applicant['id'] : null,
        );

        $snapshot = $this->schoolScope->getPrimaryPublicSnapshot();

        return Inertia::render('Public/Tracking/Index', [
            'school' => $this->schoolPayload($school, $snapshot),
            'categories' => collect(UnifiedTrackingService::TRACKING_CATEGORIES)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => [
                'code' => strtoupper(trim($validated['code'])),
                'category' => array_key_exists($category, UnifiedTrackingService::TRACKING_CATEGORIES) ? $category : 'all',
            ],
            'result' => $result,
            'contentBlocks' => $snapshot['content_blocks'] ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function schoolPayload(School $school, array $snapshot): array
    {
        return [
            'name' => $school->name,
            'logo_url' => $school->publicBrandingUrl('logo'),
            'theme' => $snapshot['theme'] ?? $school->publicTheme(),
        ];
    }
}
