<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
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

    private const PUBLIC_ICON_OPTIONS = ['fa-solid fa-graduation-cap', 'fa-solid fa-stamp', 'fa-solid fa-shield-heart', 'fa-solid fa-magnifying-glass', 'fa-solid fa-book', 'fa-solid fa-file-signature', 'fa-solid fa-school', 'fa-solid fa-users', 'fa-solid fa-calendar-days', 'fa-solid fa-circle-info'];

    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function edit(Request $request): Response
    {
        return $this->renderEditor($request, 'profile');
    }

    public function publicEditor(Request $request): Response
    {
        return $this->renderEditor($request, 'public');
    }

    private function renderEditor(Request $request, string $editorMode): Response
    {
        $school = School::findOrFail($request->user()?->school_id);
        Gate::authorize('view', $school);
        $schoolData = $school->toArray();
        $schoolData['logo_url'] = $school->publicBrandingUrl('logo');
        $schoolData['favicon_url'] = $school->publicBrandingUrl('favicon');
        $schoolData['theme_primary'] = data_get($school->settings, 'theme_primary', '#3157a4');
        $schoolData['admin_theme'] = $school->adminTheme();
        unset($schoolData['settings']['public_content']);
        $registry = $school->publicContentRegistry();
        $registry['revisions'] = array_map(static function (array $revision): array {
            unset($revision['blocks']);

            return $revision;
        }, $registry['revisions']);

        return Inertia::render('Core/School/Edit', [
            'school' => $schoolData, 'canUpdate' => Gate::allows('update', $school), 'editorMode' => $editorMode,
            'profileRevision' => hash('sha256', json_encode($school->getRawOriginal(), JSON_THROW_ON_ERROR)),
            'accreditationOptions' => self::ACCREDITATION_OPTIONS, 'timezoneOptions' => self::ALLOWED_TIMEZONES,
            'publicThemePresets' => School::publicThemePresetOptions(), 'publicHeroVariants' => School::publicHeroVariantOptions(),
            'publicContentRegistry' => $registry, 'publicContentLabels' => [
                ...config('public_content.labels', []),
                'ptsp.hero.use_custom_title' => 'Gunakan judul PTSP khusus',
                'ptsp.hero.title' => 'Judul PTSP khusus',
                'home.hero.integrity_commitment' => 'Pernyataan komitmen integritas (bukan hasil survei)',
            ],
            'publicIconOptions' => self::PUBLIC_ICON_OPTIONS,
            'publicMedia' => SchoolGalleryPhoto::query()
                ->whereHas('album', fn ($q) => $q->where('school_id', $school->id)->where('is_published', true))
                ->latest('id')->limit(200)->get()->map(fn ($photo) => [
                    'label' => $photo->caption ?: $photo->original_name,
                    'url' => route('core.gallery.photo', $photo->id),
                ])->all(),
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

            if ($request->has('profile_expected_revision')) {
                $revision = $request->validate(['profile_expected_revision' => ['required', 'string', 'size:64']])['profile_expected_revision'];
                if (! hash_equals(hash('sha256', json_encode($school->getRawOriginal(), JSON_THROW_ON_ERROR)), $revision)) {
                    throw ValidationException::withMessages(['profile_expected_revision' => 'Profil atau konfigurasi sudah berubah. Muat ulang sebelum menyimpan agar perubahan pengguna lain tidak tertimpa.']);
                }
            }

            if ($request->has('public_content_blocks_json')) {
                $encoded = $request->validate(['public_content_blocks_json' => ['required', 'string', 'json', 'max:1000000']])['public_content_blocks_json'];
                $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decoded)) {
                    throw ValidationException::withMessages(['public_content_blocks' => 'Format konten tidak valid.']);
                }
                $request->merge(['public_content_blocks' => $decoded]);
            }

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
                // The base identity color is shared by the public portal and
                // the admin panel. Keep the contrast check below as the
                // accessibility guard instead of limiting schools to presets.
                'theme_primary' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'reset_theme_defaults' => ['sometimes', 'boolean'],
                'application_name' => ['sometimes', 'nullable', 'string', 'max:80'],
                'admin_theme' => ['sometimes', 'array:primary,accent,success,warning,danger,font,radius,density,navigation_default'],
                'admin_theme.primary' => ['required_with:admin_theme', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'admin_theme.accent' => ['required_with:admin_theme', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'admin_theme.success' => ['required_with:admin_theme', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'admin_theme.warning' => ['required_with:admin_theme', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'admin_theme.danger' => ['required_with:admin_theme', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'admin_theme.font' => ['required_with:admin_theme', Rule::in(['system', 'serif'])],
                'admin_theme.radius' => ['required_with:admin_theme', 'integer', Rule::in([6, 8])],
                'admin_theme.navigation_default' => ['sometimes', Rule::in(['active', 'expanded'])],
                'admin_theme.density' => ['required_with:admin_theme', Rule::in(['comfortable', 'compact'])],
                'public_use_identity_color' => ['sometimes', 'boolean'],
                'public_theme_preset' => ['sometimes', Rule::in(['editorial', 'institutional', 'campus'])],
                'public_hero_variant' => ['sometimes', Rule::in(['collage', 'single', 'minimal'])],
                'public_hero_title' => ['nullable', 'string', 'max:120'],
                'public_hero_accent' => ['nullable', 'string', 'max:80'],
                'public_hero_primary_label' => ['nullable', 'string', 'max:40'],
                'public_hero_secondary_label' => ['nullable', 'string', 'max:40'],
                'public_show_explore' => ['sometimes', 'boolean'],
                'public_show_news' => ['sometimes', 'boolean'],
                'public_show_gallery' => ['sometimes', 'boolean'],
                'public_content_mode' => ['nullable', Rule::in(['draft', 'publish', 'restore', 'unpublish', 'archive', 'schedule', 'cancel_schedule'])],
                'public_content_expected_revision' => ['required_with:public_content_mode,public_content_blocks', 'integer', 'min:0'],
                'public_content_restore_version' => ['nullable', 'integer', 'min:1'],
                'public_content_publish_at' => ['nullable', 'required_if:public_content_mode,schedule', 'date'],
                'public_content_blocks' => ['nullable', 'array'],
                'remove_logo' => ['sometimes', 'boolean'],
                'remove_favicon' => ['sometimes', 'boolean'],
                'logo' => [Rule::prohibitedIf($request->boolean('remove_logo')), 'nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
                'favicon' => [Rule::prohibitedIf($request->boolean('remove_favicon')), 'nullable', File::image()->types(['png'])->max(512)->dimensions(Rule::dimensions()->maxWidth(512)->maxHeight(512)->ratio(1))],
            ]);

            if (($validated['public_content_mode'] ?? null) === 'schedule'
                && ! CarbonImmutable::parse($validated['public_content_publish_at'], $validated['timezone'] ?? $school->timezone)->isFuture()) {
                throw ValidationException::withMessages(['public_content_publish_at' => 'Jadwal harus berada di masa depan menurut zona waktu sekolah.']);
            }

            $oldValues = $school->only(['name', 'npsn', 'level', 'timezone', 'address', 'phone', 'email', 'legal_name', 'school_status', 'accreditation', 'principal_name', 'website', 'vision', 'mission', 'about', 'principal_message', 'logo_path', 'favicon_path', 'settings']);
            $settings = $school->settings ?? [];
            $resetTheme = (bool) ($validated['reset_theme_defaults'] ?? false);

            if (array_key_exists('application_name', $validated)) {
                $settings['application_name'] = trim((string) $validated['application_name']) ?: null;
            }
            if ($resetTheme) {
                unset($settings['admin_theme']);
            } elseif (isset($validated['admin_theme'])) {
                foreach (['primary', 'accent', 'success', 'warning', 'danger'] as $color) {
                    $this->assertAccessibleColor($validated['admin_theme'][$color], 'admin_theme.'.$color);
                }
                $settings['admin_theme'] = $validated['admin_theme'];
            }

            $basePrimary = $resetTheme
                ? '#3157a4'
                : ($validated['theme_primary']
                    ?? data_get($validated, 'admin_theme.primary')
                    ?? data_get($settings, 'theme_primary', '#3157a4'));
            $this->assertAccessibleColor($basePrimary, 'theme_primary');
            $settings['theme_primary'] = $basePrimary;

            // One base color drives both shells. Other admin semantic colors
            // remain independently configurable for status and destructive UI.
            if ($resetTheme) {
                unset($settings['admin_theme']);
            } else {
                $adminTheme = is_array(data_get($settings, 'admin_theme')) ? $settings['admin_theme'] : [];
                $adminTheme['primary'] = $basePrimary;
                $settings['admin_theme'] = $adminTheme;
            }

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
            // An explicit base color is authoritative for the public portal;
            // the legacy madrasah fallback remains only for untouched records.
            $publicTheme['use_identity_color'] = $resetTheme
                ? false
                : (array_key_exists('theme_primary', $validated)
                    ? true
                    : ($validated['public_use_identity_color'] ?? data_get($publicTheme, 'use_identity_color', false)));
            $settings['public_theme'] = $publicTheme;

            if (! empty($validated['public_content_blocks']) || ! empty($validated['public_content_mode'])) {
                $settings['public_content'] = $this->applyPublicContentUpdate(
                    $school,
                    is_array($validated['public_content_blocks'] ?? null) ? $validated['public_content_blocks'] : [],
                    (string) ($validated['public_content_mode'] ?? 'draft'),
                    isset($validated['public_content_restore_version']) ? (int) $validated['public_content_restore_version'] : null,
                    $request->user()?->name ?? 'Admin',
                    isset($validated['public_content_expected_revision']) ? (int) $validated['public_content_expected_revision'] : null,
                    $validated['public_content_publish_at'] ?? null,
                    $validated['timezone'] ?? $school->timezone,
                );
            }

            $validated['settings'] = $settings;
            unset(
                $validated['application_name'],
                $validated['admin_theme'],
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
                $validated['public_use_identity_color'],
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
                $validated['public_content_publish_at'],
                $validated['public_content_blocks'],
            );
            foreach (['logo', 'favicon'] as $kind) {
                if ($request->boolean('remove_'.$kind)) {
                    $oldPath = $school->{$kind.'_path'};
                    $validated[$kind.'_path'] = null;
                    if ($oldPath) {
                        DB::afterCommit(static fn () => Storage::disk('local')->delete($oldPath));
                    }
                }
                if (! $request->hasFile($kind)) {
                    continue;
                }
                $pathColumn = $kind.'_path';
                $oldPath = $school->{$pathColumn};
                $newPath = $request->file($kind)->store('branding/'.$school->id, 'local');
                if (! is_string($newPath) || $newPath === '') {
                    throw ValidationException::withMessages([$kind => 'Berkas gagal disimpan. Berkas lama tetap dipertahankan.']);
                }
                DB::afterRollBack(static fn () => Storage::disk('local')->delete($newPath));
                if ($oldPath) {
                    DB::afterCommit(static fn () => Storage::disk('local')->delete($oldPath));
                }
                $validated[$pathColumn] = $newPath;
                unset($validated[$kind]);
            }
            unset($validated['logo'], $validated['favicon'], $validated['remove_logo'], $validated['remove_favicon']);
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
        ?string $publishAt = null,
        ?string $timezone = null,
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
                $limit = str_starts_with($blockId, 'seo.') ? match ($key) {
                    'title' => 160, 'description' => 320, default => 5000,
                } : 5000;
                if (is_string($value) && mb_strlen(trim(strip_tags($value))) > $limit) {
                    throw ValidationException::withMessages(['public_content_blocks' => 'Isi '.$key.' maksimal '.$limit.' karakter.']);
                }
                $sanitized[$blockId][$key] = $this->sanitizeContentValue($value, $defaults[$blockId][$key], $key);
            }
        }

        if ($mode === 'draft') {
            return [
                ...$registry,
                'published' => $registry['published'],
                'draft' => $school->compactPublicContentBlocks($sanitized),
                'revisions' => $revisions,
                'revision' => $revision + 1,
            ];
        }

        if ($mode === 'cancel_schedule') {
            return [...$registry, 'scheduled' => null, 'revision' => $revision + 1];
        }
        $live = $sanitized;
        $scheduled = null;
        if (in_array($mode, ['unpublish', 'archive'], true)) {
            $live = $registry['published'];
            foreach ($live as $blockId => &$block) {
                if ($blockId !== 'shell.options' && ! str_starts_with($blockId, 'copy.')) {
                    $block['visible'] = false;
                }
            }
            unset($block);
        } elseif ($mode === 'schedule') {
            $scheduled = ['publish_at' => CarbonImmutable::parse($publishAt, $timezone ?? $school->timezone)->utc()->toIso8601String(),
                'blocks' => $school->compactPublicContentBlocks($sanitized), 'actor' => $actorName];
            $live = $registry['published'];
        }

        $nextVersion = max([0, ...array_column($revisions, 'version')]) + 1;
        $revisions[] = [
            'version' => $nextVersion,
            'action' => $mode,
            'restored_from_version' => $mode === 'restore' ? $restoreVersion : null,
            'published_at' => now()->toIso8601String(),
            'actor' => $actorName,
            'blocks' => $school->compactPublicContentBlocks($sanitized),
        ];

        return [
            'published' => $school->compactPublicContentBlocks($live),
            'draft' => $school->compactPublicContentBlocks($sanitized),
            'scheduled' => $scheduled,
            'status' => in_array($mode, ['unpublish', 'archive'], true) ? $mode : ($mode === 'schedule' ? $registry['status'] : 'published'),
            'revisions' => array_slice($revisions, -15),
            'revision' => $revision + 1,
        ];
    }

    private function assertAccessibleColor(string $hex, string $field): void
    {
        $channels = array_map(static function (int $offset) use ($hex): float {
            $value = hexdec(substr($hex, $offset, 2)) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, [1, 3, 5]);
        $ratio = 1.05 / ($channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722 + 0.05);
        if ($ratio < 4.5) {
            throw ValidationException::withMessages([$field => 'Pilih warna dengan kontras teks putih minimal 4,5:1.']);
        }
    }

    private function sanitizeContentValue(mixed $value, mixed $default, string $field): mixed
    {
        $fail = static fn (string $message) => throw ValidationException::withMessages(['public_content_blocks' => $message]);
        if (is_bool($default)) {
            if (! in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                $fail('Pilihan tampil/sembunyi harus bernilai benar atau salah.');
            }

            return (bool) $value;
        }
        if (is_int($default) || is_float($default)) {
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 1000000 || (is_int($default) && (float) $value !== (float) (int) $value)) {
                $fail('Angka konten harus berada pada rentang 0–1.000.000.');
            }

            return is_int($default) ? (int) $value : (float) $value;
        }
        if (is_array($default)) {
            if (! is_array($value) || count($value) > 50) {
                $fail('Daftar konten maksimal berisi 50 item.');
            }
            if (array_is_list($default)) {
                $template = $default[0] ?? '';

                return array_map(fn ($item) => $this->sanitizeContentValue($item, $template, $field), array_values($value));
            }
            $result = [];
            foreach ($default as $key => $sample) {
                $result[$key] = $this->sanitizeContentValue($value[$key] ?? $sample, $sample, (string) $key);
            }

            return $result;
        }
        if (! is_string($value) && ! is_numeric($value) && $value !== null) {
            $fail('Isi konten harus berupa teks.');
        }
        $text = trim(strip_tags((string) $value));
        if (mb_strlen($text) > 5000) {
            $fail('Isi konten maksimal 5.000 karakter.');
        }
        if ($field === 'canonical_url' && $text !== '' && (filter_var($text, FILTER_VALIDATE_URL) === false || ! in_array(strtolower((string) parse_url($text, PHP_URL_SCHEME)), ['http', 'https'], true))) {
            $fail('Canonical harus berupa URL HTTP/HTTPS lengkap.');
        }
        if (preg_match('/(?:^|_)(?:href|url|src|poster|image|photo)(?:_|$)/', $field) && $text !== '') {
            $decoded = rawurldecode(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
            $local = preg_match('~^(?:/(?!/)|#)[^\\x00-\\x20<>"\']*$~u', $decoded) === 1 && ! preg_match('~(?:^|/)\\.\\.(?:/|$)~', $decoded);
            $http = filter_var($text, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) parse_url($text, PHP_URL_SCHEME)), ['http', 'https'], true);
            $contact = (str_starts_with($text, 'mailto:') && filter_var(substr($text, 7), FILTER_VALIDATE_EMAIL))
                || preg_match('/^tel:[+0-9() -]+$/D', $text);
            if (($local && str_contains($decoded, '\\')) || (! $local && ! $http && ! $contact)) {
                $fail('Tautan harus berupa alamat situs HTTP/HTTPS, halaman sekolah, email, atau telepon yang valid.');
            }
        }
        if (($field === 'icon' || str_ends_with($field, '_icon')) && $text !== '' && ! in_array($text, self::PUBLIC_ICON_OPTIONS, true)) {
            $fail('Pilih ikon dari daftar yang diizinkan.');
        }

        return $text;
    }
}
