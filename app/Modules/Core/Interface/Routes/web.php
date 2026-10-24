<?php

declare(strict_types=1);

use App\Modules\Core\Interface\Http\Controllers\AuthController;
use App\Modules\Core\Interface\Http\Controllers\SchoolController;
use App\Modules\Core\Interface\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest authentication routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

// Authenticated Core routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::prefix('pengaturan')->name('core.')->group(function (): void {
        // User management
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // School profile management
        Route::get('/sekolah', [SchoolController::class, 'edit'])->name('school.edit');
        Route::put('/sekolah', [SchoolController::class, 'update'])->name('school.update');
    });
});
