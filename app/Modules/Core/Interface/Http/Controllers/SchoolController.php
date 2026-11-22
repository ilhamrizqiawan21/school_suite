<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    private const ACCREDITATION_OPTIONS = ['Unggul', 'Baik Sekali', 'Baik', 'A', 'B', 'C', 'Belum Terakreditasi'];

    private const ALLOWED_TIMEZONES = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'];

    private const THEME_PRIMARY_OPTIONS = ['#3157a4', '#277450', '#7255ae', '#b87818', '#b64a42'];

    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Show the form for editing the current school profile.
     */
    public function edit(Request $request): Response
    {
        $schoolId = $request->user()?->school_id;
        $school = School::findOrFail($schoolId);

        Gate::authorize('view', $school);

        return Inertia::render('Core/School/Edit', [
            'school' => $school,
            'canUpdate' => Gate::allows('update', $school),
            'accreditationOptions' => self::ACCREDITATION_OPTIONS,
            'timezoneOptions' => self::ALLOWED_TIMEZONES,
            'publicThemePresets' => School::publicThemePresetOptions(),
            'publicHeroVariants' => School::publicHeroVariantOptions(),
            'publicContentRegistry' => $school->publicContentRegistry(),
        ]);
    }

    /**
     * Update the current school profile.
     */
    public function update(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request): RedirectResponse {
            $schoolId = $request->user()?->school_id;
            $school = School::query()->lockForUpdate()->findOrFail($schoolId);

            Gate::authorize('update', $school);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'npsn' => ['required', 'string', 'max:20', Rule::unique('schools', 'npsn')->ignore($school->id)],
                'level' => ['required', 'string', 'in:SD,SMP,SMA,SMK,MI,MTs,MA'],
                'timezone' => ['sometimes', 'string', Rule::in(self::ALLOWED_TIMEZONES)],
                'address' => ['nullable', 'string'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'legal_name' => ['nullable', 'string', 'max:255'],
                'school_status' => ['nullable', 'string', 'in:Negeri,Swasta'],
                'accreditation' => ['nullable', Rule::in(self::ACCREDITATION_OPTIONS)],
                'principal_name' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url:http,https', 'max:255'],
                'vision' => ['nullable', 'string', 'max:5000'],
                'mission' => ['nullable', 'string', 'max:10000'],
                'about' => ['nullable', 'string', 'max:10000'],
                'principal_message' => ['nullable', 'string', 'max:10000'],
                'letterhead_parent_institution' => ['nullable', 'string', 'max:255'],
                'letterhead_city' => ['nullable', 'string', 'max:100'],
                'letterhead_postal_code' => ['nullable', 'string', 'max:15'],
                'service_hours' => ['nullable', 'string', 'max:255'],
                'holiday_notice' => ['nullable', 'string', 'max:500'],
                'emergency_contact' => ['nullable', 'string', 'max:120'],
                'map_url' => ['nullable', 'url:http,https', 'max:500'],
                'whatsapp_url' => ['nullable', 'url:http,https', 'max:255'],
                'social_facebook' => ['nullable', 'url:http,https', 'max:255'],
                'social_instagram' => ['nullable', 'url:http,https', 'max:255'],
                'social_youtube' => ['nullable', 'url:http,https', 'max:255'],
                'social_tiktok' => ['nullable', 'url:http,https', 'max:255'],
                'prayer_city' => ['nullable', 'string', 'max:120'],
                'prayer_times' => ['nullable', 'array'],
                'prayer_times.*.name' => ['required_with:prayer_times', 'string', Rule::in(['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'])],
                'prayer_times.*.time' => ['required_with:prayer_times', 'string', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
                'service_catalog' => ['nullable', 'array'],
                'service_catalog.*.available' => ['sometimes', 'boolean'],
                'service_catalog.*.badge' => ['nullable', 'string', 'max:40'],
                'service_catalog.*.maintenance_message' => ['nullable', 'string', 'max:255'],
                'permit_same_day_cutoff' => ['nullable', 'string', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
                'permit_max_advance_days' => ['nullable', 'integer', 'min:1', 'max:90'],
                'kiosk_dedup_seconds' => ['nullable', 'integer', 'min:10', 'max:600'],
                'theme_primary' => ['sometimes', Rule::in(self::THEME_PRIMARY_OPTIONS)],
                'reset_theme_defaults' => ['sometimes', 'boolean'],
                'public_theme_preset' => ['sometimes', Rule::in(['editorial', 'institutional', 'campus'])],
                'public_hero_variant' => ['sometimes', Rule::in(['collage', 'single', 'minimal'])],
                'public_hero_title' => ['nullable', 'string', 'max:120'],
                'public_hero_accent' => ['nullable', 'string', 'max:80'],
                'public_hero_primary_label' => ['nullable', 'string', 'max:40'],
                'public_hero_secondary_label' => ['nullable', 'string', 'max:40'],
                'public_show_explore' => ['sometimes', 'boolean'],
                'public_show_news' => ['sometimes', 'boolean'],
                'public_show_gallery' => ['sometimes', 'boolean'],
                'public_content_mode' => ['nullable', Rule::in(['draft', 'publish', 'restore'])],
                'public_content_expected_revision' => ['required_with:public_content_mode,public_content_blocks', 'integer', 'min:0'],
                'public_content_restore_version' => ['nullable', 'integer', 'min:1'],
                'public_content_blocks' => ['nullable', 'array'],
                'logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
                'favicon' => ['nullable', File::image()->types(['png'])->max(512)->dimensions(Rule::dimensions()->maxWidth(512)->maxHeight(512)->ratio(1))],
            ]);

            $oldValues = $school->only(['name', 'npsn', 'level', 'timezone', 'address', 'phone', 'email', 'legal_name', 'school_status', 'accreditation', 'principal_name', 'website', 'vision', 'mission', 'about', 'principal_message', 'logo_path', 'favicon_path', 'settings']);
            $settings = $school->settings ?? [];
            $resetTheme = (bool) ($validated['reset_theme_defaults'] ?? false);

            $settings['theme_primary'] = $resetTheme
                ? '#3157a4'
                : ($validated['theme_primary'] ?? data_get($settings, 'theme_primary', '#3157a4'));

            foreach (['service_hours', 'holiday_notice', 'emergency_contact', 'map_url', 'whatsapp_url', 'prayer_city'] as $settingKey) {
                if (array_key_exists($settingKey, $validated)) {
                    $settings[$settingKey] = $validated[$settingKey] !== null ? trim((string) $validated[$settingKey]) ?: null : null;
                }
            }

            if (array_key_exists('prayer_times', $validated)) {
                $rawSlots = is_array($validated['prayer_times']) ? $validated['prayer_times'] : [];
                $cleanedSlots = [];
                foreach ($rawSlots as $slot) {
                    if (is_array($slot) && ! empty($slot['name']) && ! empty($slot['time'])) {
                        $cleanedSlots[] = [
                            'name' => trim((string) $slot['name']),
                            'time' => trim((string) $slot['time']),
                        ];
                    }
                }
                if ($cleanedSlots !== []) {
                    $settings['prayer_times'] = $cleanedSlots;
                } else {
                    unset($settings['prayer_times']);
                }
            }

            if (array_key_exists('service_catalog', $validated) && is_array($validated['service_catalog'])) {
                $existingCatalog = is_array($settings['service_catalog'] ?? null) ? $settings['service_catalog'] : [];
                foreach ($validated['service_catalog'] as $serviceId => $cfg) {
                    if (! is_string($serviceId) || ! is_array($cfg)) {
                        continue;
                    }
                    $available = (bool) ($cfg['available'] ?? true);
                    $existingCatalog[$serviceId] = [
                        'available' => $available,
                        'badge' => isset($cfg['badge']) && trim((string) $cfg['badge']) !== ''
                            ? trim((string) $cfg['badge'])
                            : ($available ? 'Aktif' : 'Pemeliharaan'),
                        'maintenance_message' => isset($cfg['maintenance_message']) && trim((string) $cfg['maintenance_message']) !== ''
                            ? trim((string) $cfg['maintenance_message'])
                            : null,
                    ];
                }
                $settings['service_catalog'] = $existingCatalog;
            }

            if (
                array_key_exists('permit_same_day_cutoff', $validated)
                || array_key_exists('permit_max_advance_days', $validated)
                || array_key_exists('kiosk_dedup_seconds', $validated)
            ) {
                $ops = is_array($settings['operations'] ?? null) ? $settings['operations'] : [];
                if (! empty($validated['permit_same_day_cutoff'])) {
                    $ops['permit_same_day_cutoff'] = trim((string) $validated['permit_same_day_cutoff']);
                }
                if (isset($validated['permit_max_advance_days'])) {
                    $ops['permit_max_advance_days'] = max(1, (int) $validated['permit_max_advance_days']);
                }
                if (isset($validated['kiosk_dedup_seconds'])) {
                    $ops['kiosk_dedup_seconds'] = max(10, (int) $validated['kiosk_dedup_seconds']);
                }
                $settings['operations'] = $ops;
            }

            $socialLinks = is_array($settings['social_links'] ?? null) ? $settings['social_links'] : [];
            foreach (['facebook' => 'social_facebook', 'instagram' => 'social_instagram', 'youtube' => 'social_youtube', 'tiktok' => 'social_tiktok'] as $network => $inputKey) {
                if (array_key_exists($inputKey, $validated)) {
                    $socialLinks[$network] = $validated[$inputKey] !== null ? trim((string) $validated[$inputKey]) ?: null : null;
                }
            }
            $settings['social_links'] = array_filter($socialLinks, static fn ($url): bool => is_string($url) && $url !== '');

            $settings['letterhead'] = [
                'parent_institution' => array_key_exists('letterhead_parent_institution', $validated)
                    ? $validated['letterhead_parent_institution']
                    : data_get($settings, 'letterhead.parent_institution'),
                'city' => array_key_exists('letterhead_city', $validated)
                    ? $validated['letterhead_city']
                    : data_get($settings, 'letterhead.city'),
                'postal_code' => array_key_exists('letterhead_postal_code', $validated)
                    ? $validated['letterhead_postal_code']
                    : data_get($settings, 'letterhead.postal_code'),
            ];
            $publicTheme = data_get($settings, 'public_theme', []);
            $publicTheme = is_array($publicTheme) ? $publicTheme : [];
            if ($resetTheme) {
                $publicTheme['preset'] = 'editorial';
                $publicTheme['hero_variant'] = 'collage';
            } else {
                $publicTheme['preset'] = $validated['public_theme_preset'] ?? data_get($publicTheme, 'preset', 'editorial');
                $publicTheme['hero_variant'] = $validated['public_hero_variant'] ?? data_get($publicTheme, 'hero_variant', 'collage');
            }
            $publicTheme['hero_title'] = array_key_exists('public_hero_title', $validated)
                ? $validated['public_hero_title']
                : data_get($publicTheme, 'hero_title');
            $publicTheme['hero_accent'] = array_key_exists('public_hero_accent', $validated)
                ? $validated['public_hero_accent']
                : data_get($publicTheme, 'hero_accent');
            $publicTheme['hero_primary_label'] = array_key_exists('public_hero_primary_label', $validated)
                ? $validated['public_hero_primary_label']
                : data_get($publicTheme, 'hero_primary_label');
            $publicTheme['hero_secondary_label'] = array_key_exists('public_hero_secondary_label', $validated)
                ? $validated['public_hero_secondary_label']
                : data_get($publicTheme, 'hero_secondary_label');
            $publicTheme['show_explore'] = $validated['public_show_explore'] ?? data_get($publicTheme, 'show_explore', true);
            $publicTheme['show_news'] = $validated['public_show_news'] ?? data_get($publicTheme, 'show_news', true);
            $publicTheme['show_gallery'] = $validated['public_show_gallery'] ?? data_get($publicTheme, 'show_gallery', true);
            $settings['public_theme'] = $publicTheme;

            if (! empty($validated['public_content_blocks']) || ! empty($validated['public_content_mode'])) {
                $settings['public_content'] = $this->applyPublicContentUpdate(
                    $school,
                    is_array($validated['public_content_blocks'] ?? null) ? $validated['public_content_blocks'] : [],
                    (string) ($validated['public_content_mode'] ?? 'draft'),
                    isset($validated['public_content_restore_version']) ? (int) $validated['public_content_restore_version'] : null,
                    $request->user()?->name ?? 'Admin',
                    isset($validated['public_content_expected_revision']) ? (int) $validated['public_content_expected_revision'] : null,
                );
            }

            $validated['settings'] = $settings;
            unset(
                $validated['theme_primary'],
                $validated['reset_theme_defaults'],
                $validated['letterhead_parent_institution'],
                $validated['letterhead_city'],
                $validated['letterhead_postal_code'],
                $validated['service_hours'],
                $validated['holiday_notice'],
                $validated['emergency_contact'],
                $validated['map_url'],
                $validated['whatsapp_url'],
                $validated['social_facebook'],
                $validated['social_instagram'],
                $validated['social_youtube'],
                $validated['social_tiktok'],
                $validated['prayer_city'],
                $validated['prayer_times'],
                $validated['service_catalog'],
                $validated['permit_same_day_cutoff'],
                $validated['permit_max_advance_days'],
                $validated['kiosk_dedup_seconds'],
                $validated['public_theme_preset'],
                $validated['public_hero_variant'],
                $validated['public_hero_title'],
                $validated['public_hero_accent'],
                $validated['public_hero_primary_label'],
                $validated['public_hero_secondary_label'],
                $validated['public_show_explore'],
                $validated['public_show_news'],
                $validated['public_show_gallery'],
                $validated['public_content_mode'],
                $validated['public_content_expected_revision'],
                $validated['public_content_restore_version'],
                $validated['public_content_blocks'],
            );
            foreach (['logo', 'favicon'] as $kind) {
                if (! $request->hasFile($kind)) {
                    continue;
                }
                $pathColumn = $kind.'_path';
                if ($school->{$pathColumn}) {
                    Storage::disk('local')->delete($school->{$pathColumn});
                }
                $validated[$pathColumn] = $request->file($kind)->store('branding/'.$school->id, 'local');
                unset($validated[$kind]);
            }
            unset($validated['logo'], $validated['favicon']);
            $school->update($validated);

            $this->auditLogger->log(
                eventType: 'core.school.updated',
                targetType: 'School',
                targetId: (string) $school->id,
                newValues: $validated,
                oldValues: $oldValues,
                actor: $request->user(),
                schoolId: $school->id
            );

            return redirect()->route('core.school.edit')->with('message', 'Profil sekolah berhasil diperbarui.');
        });
    }

    /**
     * Sanitize and persist public content blocks with draft/publish/restore lifecycle (AP-04.07..AP-04.10).
     *
     * @param  array<string, mixed>  $incomingBlocks
     * @return array<string, mixed>
     */
    private function applyPublicContentUpdate(
        School $school,
        array $incomingBlocks,
        string $mode,
        ?int $restoreVersion,
        string $actorName,
        ?int $expectedRevision = null,
    ): array {
        $registry = $school->publicContentRegistry();
        $defaults = School::defaultPublicContentBlocks($school);
        $revisions = $registry['revisions'];
        $revision = $registry['revision'];
        if ($expectedRevision !== null && $expectedRevision !== $revision) {
            throw ValidationException::withMessages(['public_content_blocks' => 'Konten sudah diubah oleh pengguna lain. Muat ulang sebelum menyimpan.']);
        }
        $restoreBlocks = null;

        if ($mode === 'restore') {
            foreach ($revisions as $rev) {
                if ($restoreVersion !== null && (int) ($rev['version'] ?? 0) === $restoreVersion && is_array($rev['blocks'] ?? null)) {
                    if ($restoreBlocks !== null) {
                        throw ValidationException::withMessages(['public_content_restore_version' => 'Nomor versi lama tidak unik. Pilih versi lain yang tidak ambigu.']);
                    }
                    $restoreBlocks = $rev['blocks'];
                }
            }
            if ($restoreBlocks === null) {
                throw ValidationException::withMessages(['public_content_restore_version' => 'Versi konten tidak ditemukan. Muat ulang dan pilih versi yang tersedia.']);
            }
        }

        $sanitized = $restoreBlocks ?? $registry['draft'];
        foreach ($mode === 'restore' ? [] : $incomingBlocks as $blockId => $fields) {
            if (! is_string($blockId) || ! array_key_exists($blockId, $defaults) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $key => $value) {
                if (! is_string($key) || ! array_key_exists($key, $defaults[$blockId])) {
                    continue;
                }
                if ($key === 'id') {
                    continue;
                }
                if (is_bool($defaults[$blockId][$key])) {
                    $sanitized[$blockId][$key] = (bool) $value;
                } else {
                    $cleanText = trim(strip_tags((string) $value));
                    $sanitized[$blockId][$key] = mb_substr($cleanText, 0, 1000);
                }
            }
        }

        if ($mode === 'draft') {
            return [
                'published' => $registry['published'],
                'draft' => $sanitized,
                'revisions' => $revisions,
                'revision' => $revision + 1,
            ];
        }

        $nextVersion = max([0, ...array_column($revisions, 'version')]) + 1;
        $revisions[] = [
            'version' => $nextVersion,
            'action' => $mode,
            'restored_from_version' => $mode === 'restore' ? $restoreVersion : null,
            'published_at' => now()->toIso8601String(),
            'actor' => $actorName,
            'blocks' => $sanitized,
        ];

        return [
            'published' => $sanitized,
            'draft' => $sanitized,
            'revisions' => array_slice($revisions, -15),
            'revision' => $revision + 1,
        ];
    }
}
