<?php

declare(strict_types=1);

use App\Modules\Core\Interface\Http\Controllers\AuthController;
use App\Modules\Core\Interface\Http\Controllers\BrandMediaController;
use App\Modules\Core\Interface\Http\Controllers\PasswordController;
use App\Modules\Core\Interface\Http\Controllers\ProfileController;
use App\Modules\Core\Interface\Http\Controllers\SchoolController;
use App\Modules\Core\Interface\Http\Controllers\SecurityOperationsController;
use App\Modules\Core\Interface\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/branding/sekolah/{school}/{kind}', [BrandMediaController::class, 'school'])->where('kind', 'logo|favicon')->name('branding.school');

// Guest authentication routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

// Authenticated Core routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/ganti-password', [PasswordController::class, 'edit'])->name('core.password.edit');
    Route::put('/ganti-password', [PasswordController::class, 'update'])->middleware('throttle:sensitive')->name('core.password.update');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('core.profile.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->middleware('throttle:uploads')->name('core.profile.update');
    Route::get('/profil/avatar', [BrandMediaController::class, 'avatar'])->name('core.profile.avatar');

    Route::prefix('pengaturan')->name('core.')->group(function (): void {
        // User management
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // School profile management
        Route::get('/sekolah', [SchoolController::class, 'edit'])->name('school.edit');
        Route::put('/sekolah', [SchoolController::class, 'update'])->name('school.update');
        Route::post('/sekolah', [SchoolController::class, 'update'])->middleware('throttle:uploads')->name('school.upload');

        Route::get('/keamanan', [SecurityOperationsController::class, 'index'])->name('security.index');
        Route::post('/keamanan/ip', [SecurityOperationsController::class, 'block'])->middleware('throttle:sensitive')->name('security.block');
        Route::delete('/keamanan/ip/{blockedIp}', [SecurityOperationsController::class, 'unblock'])->middleware('throttle:sensitive')->name('security.unblock');
        Route::post('/keamanan/error/{error}/selesai', [SecurityOperationsController::class, 'resolveError'])->middleware('throttle:sensitive')->name('security.errors.resolve');
    });
});
