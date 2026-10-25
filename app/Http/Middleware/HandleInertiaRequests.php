<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

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

        return [
            ...parent::share($request),
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
            'school' => [
                'name' => $user?->school?->name ?? config('app.school_name', 'Sekolah Pilot School Suite'),
                'logo_url' => $user?->school?->logo_path ? route('branding.school', [$user->school, 'logo']) : null,
                'favicon_url' => $user?->school?->favicon_path ? route('branding.school', [$user->school, 'favicon']) : null,
                'theme_primary' => data_get($user?->school?->settings, 'theme_primary', '#3157a4'),
            ],
            'activePeriod' => [
                'academic_year' => '2026/2027',
                'term' => 'Ganjil',
                'label' => '2026/2027 — Semester Ganjil',
                'is_active' => true,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
