<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Infrastructure\Models\LoginAttemptLog;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Interface\Http\Requests\LoginRequest;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        $school = School::query()->orderBy('id')->first();

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'branding' => $school ? [
                'school_name' => $school->name,
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
                'favicon_url' => $school->favicon_path ? route('branding.school', [$school, 'favicon']) : null,
                'theme_primary' => data_get($school->settings, 'theme_primary', '#3157a4'),
            ] : null,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $identity = Str::lower(trim((string) $request->input('identity')));
        $candidate = User::where(filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $identity)->first();
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            $this->recordLoginAttempt($request, $identity, $candidate, 'failed', $candidate && ! $candidate->is_active ? 'inactive' : 'invalid_credentials');
            throw $exception;
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user !== null) {
            $this->recordLoginAttempt($request, $identity, $user, 'success');
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

        if (config('auth.enforce_password_change') && $user?->must_change_password) {
            return redirect()->route('core.password.edit');
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

    private function recordLoginAttempt(Request $request, string $identity, ?User $user, string $status, ?string $reason = null): void
    {
        try {
            if (! Schema::hasTable('login_attempt_logs')) {
                return;
            }

            LoginAttemptLog::create([
                'school_id' => $user?->school_id,
                'user_id' => $user?->id,
                'identity_hash' => hash('sha256', $identity),
                'status' => $status,
                'reason' => $reason,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Authentication must remain available when operational telemetry fails.
        }
    }
}
