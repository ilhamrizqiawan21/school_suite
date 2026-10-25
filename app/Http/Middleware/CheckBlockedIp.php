<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Core\Infrastructure\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Schema::hasTable('security_blocked_ips')) {
                $blocked = BlockedIp::where('ip_address', $request->ip())->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
                abort_if($blocked, 403, 'Akses dari alamat jaringan ini diblokir.');
            }
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Pemeriksaan blokir IP gagal.', ['exception' => $exception::class]);
        }

        return $next($request);
    }
}
