<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\PublicIkmSurvey;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortalAdminController extends Controller
{
    public function __construct(
        private readonly PublicPortalService $portalService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function downloadsIndex(Request $request): Response
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);

        $downloads = PublicDownload::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(static fn (PublicDownload $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'category_label' => PublicPortalService::DOWNLOAD_CATEGORIES[$item->category] ?? $item->category,
                'title' => $item->title,
                'description' => $item->description,
                'version' => $item->version,
                'original_name' => $item->original_name,
                'mime_type' => $item->mime_type,
                'size_bytes' => $item->size_bytes,
                'is_published' => (bool) $item->is_published,
                'download_count' => $item->download_count,
                'updated_at' => $item->updated_at?->toIso8601String(),
                'download_url' => $item->is_published ? route('core.public.downloads.file', $item) : null,
            ])
            ->values()
            ->all();

        return Inertia::render('Core/Downloads/Index', [
            'downloads' => $downloads,
            'categories' => PublicPortalService::DOWNLOAD_CATEGORIES,
            'can_manage' => true,
        ]);
    }

    public function storeDownload(Request $request): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);

        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(PublicPortalService::DOWNLOAD_CATEGORIES))],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'version' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $this->portalService->storeDownload($schoolId, $request->file('file'), $validated, $request->user());

        return back()->with('message', 'Dokumen pusat unduh berhasil disimpan.');
    }

    public function updateDownload(Request $request, PublicDownload $download): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        if ($schoolId <= 0 || (int) $download->school_id !== $schoolId || ! $request->user()?->hasRole(['admin', 'operator'])) {
            abort(403);
        }

        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(PublicPortalService::DOWNLOAD_CATEGORIES))],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'version' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $this->portalService->updateDownload(
            $download,
            $validated,
            $request->file('file'),
            $request->user(),
        );

        return back()->with('message', 'Dokumen pusat unduh berhasil diperbarui.');
    }

    public function toggleDownloadPublish(Request $request, PublicDownload $download): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        if ($schoolId <= 0 || (int) $download->school_id !== $schoolId || ! $request->user()?->hasRole(['admin', 'operator'])) {
            abort(403);
        }

        $download->update(['is_published' => ! $download->is_published]);

        $this->auditLogger->log(
            eventType: 'public_download.visibility_toggled',
            targetType: 'PublicDownload',
            targetId: (string) $download->id,
            newValues: ['is_published' => $download->is_published],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        return back()->with('message', 'Status publikasi dokumen diperbarui.');
    }

    public function destroyDownload(Request $request, PublicDownload $download): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        if ($schoolId <= 0 || (int) $download->school_id !== $schoolId || ! $request->user()?->hasRole(['admin', 'operator'])) {
            abort(403);
        }

        $this->portalService->deleteDownload($download, $request->user());

        return back()->with('message', 'Dokumen pusat unduh berhasil dihapus.');
    }

    public function integrityIndex(Request $request): Response
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);
        $school = School::query()->findOrFail($schoolId);

        $surveys = PublicIkmSurvey::query()
            ->where('school_id', $schoolId)
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(static fn (PublicIkmSurvey $item): array => [
                'id' => $item->id,
                'respondent_name' => $item->respondent_name ?: 'Anonim',
                'respondent_role' => $item->respondent_role,
                'service_name' => $item->service_name,
                'rating' => $item->rating,
                'comment' => $item->comment,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('Core/Integrity/Index', [
            'surveys' => $surveys,
            'summary' => $this->portalService->ikmSummary($schoolId),
            'integrityConfig' => $this->portalService->integrityZoneConfig($school),
            'downloadCategories' => PublicPortalService::DOWNLOAD_CATEGORIES,
            'can_manage' => true,
        ]);
    }

    public function updateIntegrityContent(Request $request): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);
        $school = School::query()->findOrFail($schoolId);

        $validated = $request->validate([
            'declaration' => ['required', 'string', 'max:2000'],
            'sop_category' => ['required', 'in:'.implode(',', array_keys(PublicPortalService::DOWNLOAD_CATEGORIES))],
            'areas' => ['required', 'array', 'min:1', 'max:10'],
            'areas.*' => ['required', 'string', 'max:180'],
            'service_options' => ['nullable', 'array', 'max:15'],
            'service_options.*' => ['required', 'string', 'max:120'],
        ]);

        $settings = is_array($school->settings) ? $school->settings : [];
        $settings['integrity_zone'] = [
            'declaration' => trim($validated['declaration']),
            'sop_category' => $validated['sop_category'],
            'areas' => array_values(array_filter(array_map('trim', $validated['areas']))),
            'service_options' => array_values(array_filter(array_map('trim', $validated['service_options'] ?? []))),
        ];
        $school->update(['settings' => $settings]);

        $this->auditLogger->log(
            eventType: 'public_integrity.updated',
            targetType: 'School',
            targetId: (string) $school->id,
            newValues: $settings['integrity_zone'],
            actor: $request->user(),
            schoolId: $schoolId,
        );

        return back()->with('message', 'Konten Zona Integritas berhasil diperbarui.');
    }

    public function moderateIkm(Request $request, int $survey): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);
        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
        ]);

        $this->portalService->approveIkmSurvey($survey, $schoolId, (bool) $validated['approved'], $request->user());

        return back()->with('message', 'Status moderasi IKM diperbarui.');
    }

    public function destroyIkm(Request $request, int $survey): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);

        $this->portalService->deleteIkmSurvey($survey, $schoolId, $request->user());

        return back()->with('message', 'Respon survei IKM berhasil dihapus.');
    }
}
