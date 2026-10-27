<?php

declare(strict_types=1);

use App\Modules\Administration\Interface\Http\Controllers\CorrespondenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,operator', 'throttle:sensitive'])
    ->prefix('administrasi/persuratan')
    ->name('administration.correspondence.')
    ->group(function (): void {
        Route::get('/', [CorrespondenceController::class, 'index'])->name('index');
        Route::post('/surat', [CorrespondenceController::class, 'store'])->name('store');
        Route::patch('/surat/{record}/status', [CorrespondenceController::class, 'updateStatus'])->name('status');
        Route::get('/surat/{record}/lampiran', [CorrespondenceController::class, 'downloadAttachment'])->name('attachment');
    });
