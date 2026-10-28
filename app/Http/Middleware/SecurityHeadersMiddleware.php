<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and enforce strict security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $scriptSource = "'self' 'unsafe-inline'";
        $styleSource = "'self' 'unsafe-inline'";
        $connectSource = "'self'";

        if (config('app.env') === 'local' && Vite::isRunningHot()) {
            $viteUrl = parse_url((string) Vite::devServerUrl());

            if (is_array($viteUrl) && in_array($viteUrl['scheme'] ?? null, ['http', 'https'], true) && isset($viteUrl['host'])) {
                $viteOrigin = $viteUrl['scheme'].'://'.$viteUrl['host'].(isset($viteUrl['port']) ? ':'.$viteUrl['port'] : '');
                $viteWebSocketOrigin = ($viteUrl['scheme'] === 'https' ? 'wss' : 'ws').'://'.$viteUrl['host'].(isset($viteUrl['port']) ? ':'.$viteUrl['port'] : '');

                $scriptSource .= ' '.$viteOrigin;
                $styleSource .= ' '.$viteOrigin;
                $connectSource .= ' '.$viteOrigin.' '.$viteWebSocketOrigin;
            }
        }

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src {$scriptSource}; style-src {$styleSource}; img-src 'self' data: blob:; font-src 'self' data:; connect-src {$connectSource}; frame-ancestors 'self'; base-uri 'self'; form-action 'self'",
        ];

        if ($request->isSecure() || config('app.env') === 'production') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
