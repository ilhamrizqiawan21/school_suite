<?php

declare(strict_types=1);

use App\Http\Controllers\HealthCheckController;
use App\Shared\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'stats' => [
            'modules_ready' => 9,
            'active_period' => '2026/2027 Ganjil',
            'framework_version' => app()->version(),
        ],
    ]);
})->middleware('auth')->name('dashboard');

// Observability & Health Check Subsystem (SS-F-009)
Route::get('/up', HealthCheckController::class)->name('health.up');
Route::get('/health', HealthCheckController::class)->name('health.index');

// Route Pengunduhan Berkas Privat dengan URL Bertanda Tangan (SS-F-008)
Route::get('/berkas-privat/unduh', [PrivateFileController::class, 'download'])
    ->name('private.file.download');
