<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('Core/Profile/Edit', ['profile' => $request->user()->only('id', 'name', 'username', 'email', 'phone', 'avatar_path')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('update', $user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
        ]);
        $old = $user->only('name', 'email', 'phone', 'avatar_path');
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('local')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('profiles/'.$user->school_id, 'local');
        }
        unset($data['avatar']);
        $user->update($data);
        $this->audit->log('core.profile.updated', 'User', (string) $user->id, ['changed_fields' => array_keys($data)], ['changed_fields' => array_keys($old)], $user, (int) $user->school_id);

        return back()->with('message', 'Profil akun berhasil diperbarui.');
    }
}
