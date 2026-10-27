<?php

declare(strict_types=1);

use App\Modules\Administration\Interface\Http\Controllers\FacilitiesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,operator'])
    ->prefix('administrasi/sarpras')
    ->name('administration.facilities.')
    ->group(function () {
        Route::get('/', [FacilitiesController::class, 'index'])->name('index');
        Route::post('/aset', [FacilitiesController::class, 'store'])->middleware('throttle:sensitive')->name('assets.store');
        Route::put('/aset/{assetId}', [FacilitiesController::class, 'update'])->middleware('throttle:sensitive')->name('assets.update');
        Route::post('/peminjaman', [FacilitiesController::class, 'borrow'])->middleware('throttle:sensitive')->name('loans.store');
        Route::post('/peminjaman/{loanId}/kembali', [FacilitiesController::class, 'recordReturn'])->middleware('throttle:sensitive')->name('loans.return');
    });
