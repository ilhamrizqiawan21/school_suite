<?php

declare(strict_types=1);

use App\Modules\Assessment\Interface\Http\Controllers\AssessmentSchemeController;
use App\Modules\Assessment\Interface\Http\Controllers\ReportCardController;
use App\Modules\Assessment\Interface\Http\Controllers\ScoreInputController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('penilaian')->name('assessment.')->group(function () {
    // Input Nilai Guru
    Route::get('skor', [ScoreInputController::class, 'index'])->name('scores.index');
    Route::post('skor', [ScoreInputController::class, 'storeBatch'])->name('scores.store');

    // Skema & Komponen Penilaian Berversi
    Route::get('skema', [AssessmentSchemeController::class, 'index'])->name('schemes.index');
    Route::post('skema', [AssessmentSchemeController::class, 'store'])->name('schemes.store');
    Route::post('skema/{scheme}/komponen', [AssessmentSchemeController::class, 'storeComponent'])->name('schemes.component');

    // Rapor & Ledger Nilai (SS-D-005)
    Route::get('rapor', [ReportCardController::class, 'index'])->name('reports.index');
    Route::get('rapor/{report}', [ReportCardController::class, 'show'])->name('reports.show');
    Route::post('rapor/generate-batch', [ReportCardController::class, 'generateBatch'])->name('reports.generate');
    Route::post('rapor/{report}/publish', [ReportCardController::class, 'publish'])->name('reports.publish');
    Route::get('ledger', [ReportCardController::class, 'ledger'])->name('reports.ledger');
});
