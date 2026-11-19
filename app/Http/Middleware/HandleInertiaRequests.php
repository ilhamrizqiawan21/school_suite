<?php

namespace App\Http\Middleware;

use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Shared\Navigation\SidebarNavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeTerm = $user?->school_id
            ? Term::query()
                ->with('academicYear')
                ->where('is_active', true)
                ->whereHas('academicYear', fn ($query) => $query->where('school_id', $user->school_id))
                ->first()
            : null;
        $activePeriod = [
            'academic_year' => $activeTerm?->academicYear?->name,
            'term' => $activeTerm?->name,
            'label' => $activeTerm?->academicYear?->name && $activeTerm?->name
                ? $activeTerm->academicYear->name.' — Semester '.$activeTerm->name
                : 'Periode belum diatur',
            'is_active' => $activeTerm !== null,
        ];

        return [
            ...parent::share($request),
            'publicPortalTheme' => fn () => app(SchoolScopeService::class)->primaryPublicTheme(),
            'publicProfileMenu' => fn () => config('public_portal.profile_menu'),
            'appName' => config('app.name', 'School Suite'),
            'auth' => fn () => $user ? [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role?->name,
                    'role_label' => $user->role?->label ?? 'Pengguna',
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_path ? route('core.profile.avatar') : null,
                ],
                'school' => $user->school ? [
                    'id' => $user->school->id,
                    'name' => $user->school->name,
                    'npsn' => $user->school->npsn,
                    'logo_url' => $user->school->logo_path ? route('branding.school', [$user->school, 'logo']) : null,
                    'favicon_url' => $user->school->favicon_path ? route('branding.school', [$user->school, 'favicon']) : null,
                    'theme_primary' => data_get($user->school->settings, 'theme_primary', '#3157a4'),
                ] : null,
            ] : null,
            'navigation' => fn () => [
                'visible_keys' => $user ? app(SidebarNavigationService::class)->visibleKeys($user) : [],
            ],
            'school' => [
                'name' => $user?->school?->name ?? config('app.school_name', 'Sekolah Pilot School Suite'),
                'logo_url' => $user?->school?->logo_path ? route('branding.school', [$user->school, 'logo']) : null,
                'favicon_url' => $user?->school?->favicon_path ? route('branding.school', [$user->school, 'favicon']) : null,
                'theme_primary' => data_get($user?->school?->settings, 'theme_primary', '#3157a4'),
            ],
            'activePeriod' => $activePeriod,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'temporary_credential' => fn () => $this->pullTemporaryCredential($request),
        ];
    }

    /** @return array{subject:string, password:string, expires_at:int}|null */
    private function pullTemporaryCredential(Request $request): ?array
    {
        if (! $request->routeIs('academic.students.index', 'academic.teachers.index')) {
            return null;
        }

        $encrypted = $request->session()->pull('temporary_credential');
        if (! is_string($encrypted)) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                return null;
            }

            $expiresAt = (int) ($payload['expires_at'] ?? 0);

            if ($expiresAt < now()->timestamp || ! is_string($payload['subject'] ?? null) || ! is_string($payload['password'] ?? null)) {
                return null;
            }

            return [
                'subject' => $payload['subject'],
                'password' => $payload['password'],
                'expires_at' => $expiresAt,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
