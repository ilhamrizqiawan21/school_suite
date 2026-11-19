<?php

declare(strict_types=1);

namespace App\Modules\Integration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integration\Application\Services\ExternalIntegrationService;
use App\Modules\Integration\Infrastructure\Models\IntegrationSyncLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class IntegrationSettingsController extends Controller
{
    public function __construct(
        private readonly ExternalIntegrationService $service,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Integration/Settings/Index', [
            'workspace' => $this->service->getWorkspaceData($request->user()),
            'previewResult' => $request->session()->get('previewResult'),
            'healthResult' => $request->session()->get('healthResult'),
        ]);
    }

    public function updateConnection(Request $request, string $systemKey): RedirectResponse
    {
        $validated = $request->validate([
            'mechanism' => ['required', Rule::in(['link', 'file_exchange', 'api_sync', 'provider'])],
            'is_enabled' => ['required', 'boolean'],
            'endpoint_url' => ['nullable', 'url', 'max:255'],
            'config' => ['nullable', 'array'],
            'secret_token' => ['nullable', 'string', 'max:255'],
            'clear_secret' => ['nullable', 'boolean'],
        ]);

        try {
            $this->service->saveConnection($request->user(), $systemKey, $validated);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['integration' => $e->getMessage()]);
        }

        return back()->with('success', 'Pengaturan koneksi '.strtoupper($systemKey).' berhasil disimpan.');
    }

    public function testConnection(Request $request, string $systemKey): RedirectResponse
    {
        try {
            $result = $this->service->testConnectionHealth(
                $request->user(),
                $systemKey,
                simulateOutage: $request->boolean('simulate_outage'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['integration' => $e->getMessage()]);
        }

        return back()
            ->with('healthResult', $result)
            ->with('success', 'Pemeriksaan koneksi '.strtoupper($systemKey).': '.$result['message']);
    }

    public function previewImport(Request $request, string $systemKey): RedirectResponse
    {
        $source = $this->extractImportSource($request);

        try {
            $preview = $this->service->previewImport($request->user(), $systemKey, $source);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['import' => $e->getMessage()]);
        }

        return back()->with('previewResult', $preview);
    }

    public function commitImport(Request $request, string $systemKey): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => ['nullable', 'string', 'max:96'],
        ]);

        $source = $this->extractImportSource($request);

        try {
            $log = $this->service->commitImport(
                $request->user(),
                $systemKey,
                $source,
                $validated['idempotency_key'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['import' => $e->getMessage()]);
        }

        $summary = sprintf(
            'Validasi batch %s tercatat. %d baris valid, %d gagal validasi. Data tujuan belum diperbarui.',
            $log->batch_code,
            $log->success_rows,
            $log->failed_rows
        );

        return back()->with('success', $summary);
    }

    public function retryLog(Request $request, IntegrationSyncLog $log): RedirectResponse
    {
        $validated = $request->validate([
            'corrected_rows' => ['nullable', 'array'],
        ]);

        try {
            $retried = $this->service->retrySyncLog(
                $request->user(),
                $log,
                isset($validated['corrected_rows']) && is_array($validated['corrected_rows'])
                    ? $validated['corrected_rows']
                    : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['retry' => $e->getMessage()]);
        }

        return back()->with('success', sprintf(
            'Validasi ulang batch %s tercatat. %d baris valid, %d gagal validasi. Data tujuan belum diperbarui.',
            $retried->batch_code,
            $retried->success_rows,
            $retried->failed_rows,
        ));
    }

    public function exportDataset(Request $request, string $systemKey): HttpResponse
    {
        $exported = $this->service->exportDataset($request->user(), $systemKey);

        return response($exported['csv'], 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$exported['filename'].'"',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>|UploadedFile|string
     */
    private function extractImportSource(Request $request): array|UploadedFile|string
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt,json', 'max:5120'],
            'payload_text' => ['nullable', 'string', 'max:100000'],
            'rows' => ['nullable', 'array'],
        ]);

        $file = $request->file('file');
        if ($file instanceof UploadedFile) {
            return $file;
        }

        if ($request->has('rows') && is_array($request->input('rows'))) {
            return $request->input('rows');
        }

        return (string) $request->input('payload_text', '');
    }
}
