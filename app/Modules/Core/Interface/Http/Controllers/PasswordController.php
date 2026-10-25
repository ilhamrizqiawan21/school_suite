<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(): Response
    {
        return Inertia::render('Core/Security/ChangePassword');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()]]);
        $user = $request->user();
        $user->update(['password' => Hash::make($data['password']), 'must_change_password' => false, 'password_changed_at' => now()]);
        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        $this->audit->log('core.password.changed', 'User', (string) $user->id, ['must_change_password' => false], actor: $user, schoolId: (int) $user->school_id);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('message', 'Kata sandi berhasil diperbarui.');
    }
}
