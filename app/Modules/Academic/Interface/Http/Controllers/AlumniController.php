<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AlumniService;
use App\Modules\Academic\Infrastructure\Models\AlumniProfile;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniService $alumniService,
        private readonly ExternalApplicantAuthService $externalAuth,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
        private readonly PublicPortalService $publicPortal,
    ) {}

    public function homeIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'], 'graduation_year' => ['nullable', 'integer', 'between:1970,2100'],
            'current_status' => ['nullable', Rule::in(array_keys(AlumniService::STATUS_LABELS))],
            'major_or_class' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', Rule::in([24, 48, 100, 'all'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'section' => ['nullable', Rule::in(['directory', 'tracer', 'careers', 'scholarships', 'kta'])],
        ]);

        return Inertia::render('Public/Alumni/Home', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'summary' => $this->alumniService->publicSummary((int) $school['id']),
            'statistics' => $this->alumniService->publicStatistics((int) $school['id']),
            'directory' => $this->alumniService->publicHomeDirectory((int) $school['id'], $filters),
            'spotlight' => $this->alumniService->publicDirectory((int) $school['id'], ['limit' => 4]),
            'filters' => $filters,
            'section' => $filters['section'] ?? 'directory',
            'statusOptions' => collect(AlumniService::STATUS_LABELS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all(),
            'opportunities' => $this->publicPortal->alumniOpportunities((int) $school['id']),
            'contentBlocks' => $school['content_blocks'] ?? [],
        ]);
    }

    public function publicEktaCheck(Request $request): JsonResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:150'],
        ]);

        return response()->json([
            'matched' => $this->alumniService->publicEktaLookup((int) $school['id'], $validated['query']),
        ]);
    }

    public function portalIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $user = $request->user();
        $ext = $this->externalAuth->currentFromSession($request, $schoolId);

        $isAlumniUser = $user !== null && (int) $user->school_id === $schoolId && $user->hasRole([RoleEnum::ALUMNI->value, RoleEnum::ADMIN->value]);
        $isExtAlumni = $ext !== null && ($ext['applicant_type'] ?? '') === 'alumni';

        $profile = ($isAlumniUser || $isExtAlumni)
            ? $this->alumniService->findOwnedProfile($schoolId, $isAlumniUser ? $user : null, $isExtAlumni ? (int) $ext['id'] : null)
            : null;

        $ektaToken = ($profile?->ekta_document_id !== null)
            ? $this->documentService->getVerificationTokenById($schoolId, (int) $profile->ekta_document_id)
            : null;

        return Inertia::render('Public/Alumni/Portal', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'authenticatedAlumni' => $isAlumniUser || $isExtAlumni,
            'accountName' => $user?->name ?? ($ext['full_name'] ?? null),
            'accountEmail' => $user?->email ?? ($ext['email'] ?? null),
            'statusOptions' => collect(AlumniService::STATUS_LABELS)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'profile' => $profile ? [
                ...$profile->toArray(),
                'ekta_verification_token' => $ektaToken,
            ] : null,
        ]);
    }

    public function saveSelfProfile(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $user = $request->user();
        $ext = $this->externalAuth->currentFromSession($request, $schoolId);

        $isAlumniUser = $user !== null && (int) $user->school_id === $schoolId && $user->hasRole([RoleEnum::ALUMNI->value, RoleEnum::ADMIN->value]);
        $isExtAlumni = $ext !== null && ($ext['applicant_type'] ?? '') === 'alumni';
        abort_unless($isAlumniUser || $isExtAlumni, 403);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'nis' => ['nullable', 'string', 'max:30'],
            'nisn' => ['nullable', 'string', 'max:30'],
            'graduation_year' => ['required', 'integer', 'min:1980', 'max:2100'],
            'major_or_class' => ['nullable', 'string', 'max:100'],
            'current_status' => ['required', Rule::in(array_keys(AlumniService::STATUS_LABELS))],
            'institution_or_company' => ['nullable', 'string', 'max:180'],
            'position_or_program' => ['nullable', 'string', 'max:150'],
            'career_notes' => ['nullable', 'string', 'max:2000'],
            'scholarship_info' => ['nullable', 'string', 'max:1000'],
            'testimonial' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'opt_in_directory' => ['nullable', 'boolean'],
            'opt_in_contact' => ['nullable', 'boolean'],
            'opt_in_career' => ['nullable', 'boolean'],
            'opt_in_testimonial' => ['nullable', 'boolean'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'diploma_serial' => ['nullable', 'string', 'max:100'],
            'activity_location' => ['nullable', 'string', 'max:150'],
            'residence_city' => ['nullable', 'string', 'max:150'],
            'linkedin_url' => ['nullable', 'url:https', 'max:255'],
            'instagram_handle' => ['nullable', 'string', 'max:50'],
            'photo_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->alumniService->upsertSelfProfile(
            schoolId: $schoolId,
            data: $validated,
            user: $isAlumniUser ? $user : null,
            externalApplicantId: $isExtAlumni ? (int) $ext['id'] : null,
            proofFile: $request->file('proof_file'),
            photoFile: $request->file('photo_file'),
        );

        return redirect()->route('academic.alumni.tracer.portal')->with('message', 'Profil dan data tracer alumni berhasil diperbarui.');
    }

    public function downloadEkta(Request $request, AlumniProfile $profile): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        abort_unless((int) $profile->school_id === $schoolId && $profile->ekta_document_id !== null, 404);

        $user = $request->user();
        $ext = $this->externalAuth->currentFromSession($request, $schoolId);
        $isStaff = $user !== null && (int) $user->school_id === $schoolId && $user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value]);
        $isOwnerUser = $user !== null && $profile->user_id !== null && (int) $user->id === (int) $profile->user_id;
        $isOwnerExt = $ext !== null && $profile->external_applicant_id !== null && (int) $ext['id'] === (int) $profile->external_applicant_id;

        abort_unless($isStaff || $isOwnerUser || $isOwnerExt, 403);

        $file = $this->documentService->resolveDownloadByDocumentId($schoolId, (int) $profile->ekta_document_id);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function directoryIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'graduation_year' => $request->query('graduation_year'),
            'current_status' => trim((string) $request->query('current_status', '')),
        ];

        return Inertia::render('Public/Alumni/Directory', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => $school['logo_url'] ?? null,
                'theme' => $school['theme'] ?? [],
            ],
            'alumni' => $this->alumniService->publicDirectory($schoolId, $filters),
            'statusOptions' => collect(AlumniService::STATUS_LABELS)
                ->map(static fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'filters' => [
                'search' => (string) $filters['search'],
                'graduation_year' => (string) ($filters['graduation_year'] ?? ''),
                'current_status' => (string) $filters['current_status'],
            ],
        ]);
    }

    public function moderationIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $status = trim((string) $request->query('status', ''));

        $profiles = AlumniProfile::query()
            ->where('school_id', $schoolId)
            ->when($status !== '', fn ($q) => $q->where('moderation_status', $status))
            ->latest('id')
            ->limit(60)
            ->get();

        return Inertia::render('Academic/Alumni/Index', [
            'profiles' => $profiles->toArray(),
            'statusLabels' => AlumniService::STATUS_LABELS,
            'filters' => ['status' => $status],
        ]);
    }

    public function moderate(Request $request, AlumniProfile $profile): RedirectResponse
    {
        $validated = $request->validate([
            'moderation_status' => ['required', Rule::in(['approved', 'rejected'])],
            'moderation_note' => ['nullable', 'string', 'max:1000'],
            'issue_ekta' => ['nullable', 'boolean'],
        ]);

        $this->alumniService->moderate(
            $request->user(),
            $profile,
            $validated['moderation_status'],
            $validated['moderation_note'] ?? null,
            (bool) ($validated['issue_ekta'] ?? true),
        );

        return back()->with('message', 'Keputusan moderasi alumni berhasil disimpan.');
    }
}
