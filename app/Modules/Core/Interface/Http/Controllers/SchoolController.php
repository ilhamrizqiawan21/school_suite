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
            'accreditation' => ['nullable', 'string', 'max:10'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'vision' => ['nullable', 'string', 'max:5000'],
            'mission' => ['nullable', 'string', 'max:10000'],
            'theme_primary' => ['sometimes', Rule::in(['#3157a4', '#277450', '#7255ae', '#b87818', '#b64a42'])],
            'logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'favicon' => ['nullable', File::image()->types(['png'])->max(512)->dimensions(Rule::dimensions()->maxWidth(512)->maxHeight(512)->ratio(1))],
        ]);

        $oldValues = $school->only(['name', 'npsn', 'level', 'address', 'phone', 'email', 'legal_name', 'school_status', 'accreditation', 'principal_name', 'website', 'vision', 'mission', 'logo_path', 'favicon_path', 'settings']);
        $settings = $school->settings ?? [];
        $settings['theme_primary'] = $validated['theme_primary'] ?? data_get($settings, 'theme_primary', '#3157a4');
        $validated['settings'] = $settings;
        unset($validated['theme_primary']);
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
