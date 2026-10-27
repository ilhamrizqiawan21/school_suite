<?php

declare(strict_types=1);

use App\Modules\Administration\Interface\Http\Controllers\StaffTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:sensitive'])->prefix('administrasi/tugas')->name('administration.staff-tasks.')->group(function () {
    Route::get('/', [StaffTaskController::class, 'index'])->name('index');
    Route::post('/', [StaffTaskController::class, 'store'])->name('store');
    Route::get('/{task}/lampiran', [StaffTaskController::class, 'downloadAttachment'])->name('attachment');
    Route::patch('/{task}', [StaffTaskController::class, 'update'])->name('update');
    Route::patch('/{task}/progres', [StaffTaskController::class, 'updateProgress'])->name('progress');
    Route::delete('/{task}', [StaffTaskController::class, 'destroy'])->name('destroy');
});
