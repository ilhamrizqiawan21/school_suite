<?php

declare(strict_types=1);

use App\Modules\Homeroom\Interface\Http\Controllers\HomeroomDashboardController;
use App\Modules\Homeroom\Interface\Http\Controllers\StudentDisciplineController;
use App\Modules\Homeroom\Interface\Http\Controllers\StudentObservationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher,homeroom'])->prefix('wali-kelas')->name('homeroom.')->group(function () {
    Route::get('/', [HomeroomDashboardController::class, 'index'])->name('dashboard');

    // Catatan Observasi Siswa
    Route::get('catatan', [StudentObservationController::class, 'index'])->name('observations.index');
    Route::post('catatan', [StudentObservationController::class, 'store'])->name('observations.store');
    Route::delete('catatan/{studentNote}', [StudentObservationController::class, 'destroy'])->name('observations.destroy');

    // Kedisiplinan, Prestasi, & Bantuan
    Route::get('kedisiplinan', [StudentDisciplineController::class, 'index'])->name('discipline.index');
    Route::post('kedisiplinan/insiden', [StudentDisciplineController::class, 'storeIncident'])->name('discipline.incident');
    Route::post('kedisiplinan/prestasi', [StudentDisciplineController::class, 'storeAchievement'])->name('discipline.achievement');
    Route::post('kedisiplinan/bantuan', [StudentDisciplineController::class, 'storeAssistance'])->name('discipline.assistance');
    Route::post('kedisiplinan/dispensasi', [StudentDisciplineController::class, 'storeDispensation'])->name('discipline.dispensation');
});
