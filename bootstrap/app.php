<?php

use App\Http\Middleware\CheckBlockedIp;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CheckBlockedIp::class,
            RequirePasswordChange::class,
            HandleInertiaRequests::class,
            SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            try {
                if (! Schema::hasTable('system_error_events')) {
                    return;
                }
                $request = request();
                $class = $exception::class;
                $path = $request?->path();
                $fingerprint = hash('sha256', $class.'|'.($path ?? 'cli').'|'.$exception->getFile().'|'.$exception->getLine());
                $schoolId = auth()->user()?->school_id;
                $existing = DB::table('system_error_events')->where('fingerprint', $fingerprint)->where('school_id', $schoolId)->whereNull('resolved_at')->first();
                if ($existing) {
                    DB::table('system_error_events')->where('id', $existing->id)->update(['occurrences' => ((int) $existing->occurrences) + 1, 'last_seen_at' => now(), 'updated_at' => now()]);
                } else {
                    DB::table('system_error_events')->insert(['school_id' => $schoolId, 'fingerprint' => $fingerprint, 'exception_class' => $class, 'request_method' => $request?->method(), 'request_path' => $path, 'user_id' => auth()->id(), 'occurrences' => 1, 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                }
            } catch (Throwable) {
                // Error telemetry must never replace the original exception.
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
