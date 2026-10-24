<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
        ]);

        $oldValues = $school->only(array_keys($validated));
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
