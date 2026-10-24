<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Interface\Http\Requests\LoginRequest;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Display the login view.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user !== null) {
            $this->auditLogger->log(
                eventType: 'auth.login',
                targetType: 'User',
                targetId: (string) $user->id,
                newValues: [
                    'email' => $user->email,
                    'username' => $user->username,
                ],
                actor: $user,
                schoolId: $user->school_id !== null ? (int) $user->school_id : null
            );
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user !== null) {
            $this->auditLogger->log(
                eventType: 'auth.logout',
                targetType: 'User',
                targetId: (string) $user->id,
                actor: $user,
                schoolId: $user->school_id !== null ? (int) $user->school_id : null
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
