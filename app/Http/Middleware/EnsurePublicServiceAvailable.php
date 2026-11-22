<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Core\Application\Services\SchoolScopeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicServiceAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = match ($request->route()?->getName()) {
            'administration.ptsp.public.submit', 'administration.ptsp.public.draft.save' => 'ptsp',
            'administration.external-letters.store' => 'surat-eksternal',
            'administration.guestbook.public.store' => 'buku-tamu',
            'homeroom.permits.public.submit' => 'izin-siswa',
            'academic.mutations.public.submit' => 'mutasi-siswa',
            'administration.legalization.public.submit' => 'legalisir',
            'homeroom.complaints.public.store' => 'siap-lapor',
            'academic.alumni.tracer.save' => 'tracer-alumni',
            'public.admissions.register' => 'ppdb',
            'integration.independent-proposals.store' => 'proposal-mandiri',
            'library.procurement.store', 'library.clearance.store', 'library.visits.store' => 'perpustakaan',
            'library.replica.action', 'library.replica.legacy_action' => in_array($request->query('pg'), ['booking_pinjam', 'usulkan_buku', 'buku_tamu'], true) && $request->isMethod('post') ? 'perpustakaan' : null,
            'core.public.ikm.store' => 'zona-integritas',
            default => null,
        };
        if ($code !== null) {
            $availability = app(SchoolScopeService::class)->publicServiceAvailability($code);
            if (! $availability['available']) {
                $message = $availability['message'];
                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $message, 'errors' => ['service' => [$message]]], 503);
                }

                return back()->withInput($request->except(['password', 'password_confirmation', 'pin', 'verification_pin']))
                    ->withErrors(['service' => $message]);
            }
        }

        $response = $next($request);
        if ($request->boolean('preview_draft')) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
