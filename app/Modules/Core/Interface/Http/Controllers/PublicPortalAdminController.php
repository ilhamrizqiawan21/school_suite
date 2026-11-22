<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\PublicIkmSurvey;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'schoolTimezone' => $school->timezone ?: config('app.timezone'),
            'downloadCategories' => PublicPortalService::DOWNLOAD_CATEGORIES,
            'can_manage' => true,
        ]);
    }

    public function exportIkm(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->is_active && $user->hasRole(['admin', 'operator']) && $user->school_id, 403);
        $period = trim((string) $request->query('period', ''));
        $query = PublicIkmSurvey::query()->where('school_id', $user->school_id)
            ->when($period !== '', fn ($q) => $period === 'legacy'
                ? $q->whereNull('instrument_snapshot') : $q->where('instrument_snapshot->period->id', $period))
            ->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Responden', 'Kategori', 'Layanan', 'Periode', 'Versi instrumen', 'Nilai', 'Disetujui', 'Masukan'], ',', '"', '');
            foreach ($query->cursor() as $survey) {
                $snapshot = $survey->instrument_snapshot ?? [];
                $values = [$survey->created_at?->toIso8601String(), $survey->respondent_name, $survey->respondent_role,
                    $survey->service_name, $snapshot['period']['label'] ?? 'Instrumen lama', $snapshot['version'] ?? 'legacy',
                    number_format($survey->rating, 2, '.', ''), $survey->is_approved ? 'Ya' : 'Tidak', $survey->comment];
                $values = array_map(static function ($value): string {
                    $text = str_replace("\0", '', (string) $value);

                    return preg_match('/^(?:[\\t\\r\\n]|\\s*[=+@-])/u', $text) ? "'".$text : $text;
                }, $values);
                fputcsv($output, $values, ',', '"', '');
            }
            fclose($output);
        }, 'survei-ikm.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function updateIntegrityContent(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            $schoolId = (int) ($request->user()?->school_id ?? 0);
            abort_if($schoolId <= 0 || ! $request->user()?->hasRole(['admin', 'operator']), 403);
            $school = School::query()->lockForUpdate()->findOrFail($schoolId);
            $current = $this->portalService->integrityZoneConfig($school);
            $validated = $request->validate([
                'expected_revision' => ['sometimes', 'string', 'size:64'],
                'declaration' => ['required', 'string', 'max:2000'],
                'sop_category' => ['required', 'in:'.implode(',', array_keys(PublicPortalService::DOWNLOAD_CATEGORIES))],
                'areas' => ['required', 'array', 'min:1', 'max:10'], 'areas.*' => ['required', 'string', 'max:180'],
                'service_options' => ['nullable', 'array', 'max:15'], 'service_options.*' => ['required', 'string', 'max:120'],
                'questions' => ['sometimes', 'array', 'min:1', 'max:20'],
                'questions.*.id' => ['required', 'string', 'alpha_dash', 'max:80', 'distinct:strict'],
                'questions.*.label' => ['required', 'string', 'max:240'],
                'questions.*.weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
                'answer_labels' => ['sometimes', 'array:1,2,3,4,5', 'size:5'],
                'answer_labels.*' => ['required', 'string', 'max:100'],
                'calculation_mode' => ['sometimes', 'in:weighted_mean,simple_mean'],
                'period' => ['sometimes', 'array:id,label,starts_at,ends_at'],
                'period.id' => ['required_with:period', 'string', 'alpha_dash', 'max:80'],
                'period.label' => ['required_with:period', 'string', 'max:120'],
                'period.starts_at' => ['nullable', 'date'], 'period.ends_at' => ['nullable', 'date'],
            ]);
            if (isset($validated['expected_revision']) && ! hash_equals($current['config_revision'], $validated['expected_revision'])) {
                throw ValidationException::withMessages(['expected_revision' => 'Konfigurasi telah diubah petugas lain. Muat ulang sebelum menyimpan.']);
            }
            $answerLabels = array_map(static fn ($value) => trim(strip_tags($value)), $validated['answer_labels'] ?? $current['answer_labels']);
            foreach ($answerLabels as $score => $label) {
                if ($label === '') {
                    throw ValidationException::withMessages(['answer_labels.'.$score => 'Label jawaban wajib berisi teks.']);
                }
            }
            $period = $validated['period'] ?? $current['period'];
            $period['label'] = trim(strip_tags($period['label']));
            if ($period['label'] === '') {
                throw ValidationException::withMessages(['period.label' => 'Nama periode wajib berisi teks.']);
            }
            foreach (['starts_at', 'ends_at'] as $key) {
                $period[$key] = ! empty($period[$key])
                    ? CarbonImmutable::parse($period[$key], $school->timezone ?: config('app.timezone'))->utc()->toIso8601String() : null;
            }
            if ($period['starts_at'] && $period['ends_at'] && $period['ends_at'] < $period['starts_at']) {
                throw ValidationException::withMessages(['period.ends_at' => 'Akhir periode tidak boleh mendahului awal periode.']);
            }
            $questions = array_map(static fn ($q) => ['id' => $q['id'], 'label' => trim(strip_tags($q['label'])), 'weight' => (float) $q['weight']],
                $validated['questions'] ?? $current['questions']);
            foreach ($questions as $question) {
                if ($question['label'] === '') {
                    throw ValidationException::withMessages(['questions' => 'Label unsur penilaian wajib diisi.']);
                }
            }
            $settings = is_array($school->settings) ? $school->settings : [];
            $settings['integrity_zone'] = [
                'declaration' => trim(strip_tags($validated['declaration'])), 'sop_category' => $validated['sop_category'],
                'areas' => array_values(array_map(static fn ($v) => trim(strip_tags($v)), $validated['areas'])),
                'service_options' => array_values(array_map(static fn ($v) => trim(strip_tags($v)), $validated['service_options'] ?? [])),
                'questions' => $questions,
                'answer_labels' => $answerLabels,
                'calculation_mode' => $validated['calculation_mode'] ?? $current['calculation_mode'], 'period' => $period,
            ];
            $school->update(['settings' => $settings]);
            $this->auditLogger->log(eventType: 'public_integrity.updated', targetType: 'School', targetId: (string) $school->id,
                newValues: ['instrument_version' => $this->portalService->ikmInstrument($school)['instrument_version'], 'config' => $settings['integrity_zone']],
                actor: $request->user(), schoolId: $schoolId);

            return back()->with('message', 'Konten dan instrumen IKM berhasil diperbarui. Jawaban lama tetap memakai instrumen sebelumnya.');
        });
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
