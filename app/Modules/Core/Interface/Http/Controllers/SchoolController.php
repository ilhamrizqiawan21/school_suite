<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    private const ACCREDITATION_OPTIONS = ['Unggul', 'Baik Sekali', 'Baik', 'A', 'B', 'C', 'Belum Terakreditasi'];

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
            'publicThemePresets' => School::publicThemePresetOptions(),
            'publicHeroVariants' => School::publicHeroVariantOptions(),
        ]);
    }

    /**
     * Update the current school profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $schoolId = $request->user()?->school_id;
        $school = School::findOrFail($schoolId);

        Gate::authorize('update', $school);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'npsn' => ['required', 'string', 'max:20', Rule::unique('schools', 'npsn')->ignore($school->id)],
            'level' => ['required', 'string', 'in:SD,SMP,SMA,SMK,MI,MTs,MA'],
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
            'theme_primary' => ['sometimes', Rule::in(['#3157a4', '#277450', '#7255ae', '#b87818', '#b64a42'])],
            'public_theme_preset' => ['sometimes', Rule::in(['editorial', 'institutional', 'campus'])],
            'public_hero_variant' => ['sometimes', Rule::in(['collage', 'single', 'minimal'])],
            'public_hero_title' => ['nullable', 'string', 'max:120'],
            'public_hero_accent' => ['nullable', 'string', 'max:80'],
            'public_hero_primary_label' => ['nullable', 'string', 'max:40'],
            'public_hero_secondary_label' => ['nullable', 'string', 'max:40'],
            'public_show_explore' => ['sometimes', 'boolean'],
            'public_show_news' => ['sometimes', 'boolean'],
            'public_show_gallery' => ['sometimes', 'boolean'],
            'logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'favicon' => ['nullable', File::image()->types(['png'])->max(512)->dimensions(Rule::dimensions()->maxWidth(512)->maxHeight(512)->ratio(1))],
        ]);

        $oldValues = $school->only(['name', 'npsn', 'level', 'address', 'phone', 'email', 'legal_name', 'school_status', 'accreditation', 'principal_name', 'website', 'vision', 'mission', 'about', 'principal_message', 'logo_path', 'favicon_path', 'settings']);
        $settings = $school->settings ?? [];
        $settings['theme_primary'] = $validated['theme_primary'] ?? data_get($settings, 'theme_primary', '#3157a4');
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
        $publicTheme['preset'] = $validated['public_theme_preset'] ?? data_get($publicTheme, 'preset', 'editorial');
        $publicTheme['hero_variant'] = $validated['public_hero_variant'] ?? data_get($publicTheme, 'hero_variant', 'collage');
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
        $validated['settings'] = $settings;
        unset(
            $validated['theme_primary'],
            $validated['letterhead_parent_institution'],
            $validated['letterhead_city'],
            $validated['letterhead_postal_code'],
            $validated['public_theme_preset'],
            $validated['public_hero_variant'],
            $validated['public_hero_title'],
            $validated['public_hero_accent'],
            $validated['public_hero_primary_label'],
            $validated['public_hero_secondary_label'],
            $validated['public_show_explore'],
            $validated['public_show_news'],
            $validated['public_show_gallery']
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
    }
}
