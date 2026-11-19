<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicPortalAdminController extends Controller
{
    public function __construct(
        private readonly PublicPortalService $portalService,
    ) {}

    public function storeDownload(Request $request): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0, 403);

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

    public function toggleDownloadPublish(Request $request, PublicDownload $download): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        if ($schoolId <= 0 || (int) $download->school_id !== $schoolId) {
            abort(403);
        }

        $download->update(['is_published' => ! $download->is_published]);

        return back()->with('message', 'Status publikasi dokumen diperbarui.');
    }

    public function moderateIkm(Request $request, int $survey): RedirectResponse
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);
        abort_if($schoolId <= 0, 403);
        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
        ]);

        $this->portalService->approveIkmSurvey($survey, $schoolId, (bool) $validated['approved'], $request->user());

        return back()->with('message', 'Status moderasi IKM diperbarui.');
    }
}
