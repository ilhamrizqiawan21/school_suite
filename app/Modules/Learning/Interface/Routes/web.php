<?php

declare(strict_types=1);

use App\Modules\Learning\Interface\Http\Controllers\LearningController;
use App\Modules\Learning\Interface\Http\Controllers\LearningProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:sensitive'])->prefix('pembelajaran')->name('learning.')->group(function () {
    Route::get('/', [LearningController::class, 'index'])->name('index');

    Route::post('/materi', [LearningController::class, 'storeMaterial'])->middleware('throttle:uploads')->name('materials.store');
    Route::post('/materi/{material}/dibaca', [LearningController::class, 'markMaterialRead'])->name('materials.read');
    Route::get('/materi/{material}/unduh', [LearningController::class, 'downloadMaterial'])->name('materials.download');
    Route::delete('/materi/{material}', [LearningController::class, 'destroyMaterial'])->name('materials.destroy');

    Route::post('/tugas', [LearningController::class, 'storeAssignment'])->name('assignments.store');
    Route::delete('/tugas/{assignment}', [LearningController::class, 'destroyAssignment'])->name('assignments.destroy');
    Route::post('/tugas/{assignment}/kumpulkan', [LearningController::class, 'submitAssignment'])->middleware('throttle:uploads')->name('assignments.submit');
    Route::post('/pengumpulan/{submission}/nilai', [LearningController::class, 'gradeSubmission'])->name('submissions.grade');
    Route::get('/berkas/{file}/unduh', [LearningController::class, 'downloadSubmissionFile'])->name('submissions.files.download');
    Route::get('/progress', [LearningProgressController::class, 'index'])->name('progress.index');
});
