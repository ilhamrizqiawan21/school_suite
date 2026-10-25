<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('core.password.*', 'logout')) {
            return redirect()->route('core.password.edit')->with('status', 'Ganti kata sandi awal sebelum melanjutkan.');
        }

        return $next($request);
    }
}
