<?php

declare(strict_types=1);

use App\Modules\Learning\Interface\Http\Controllers\LearningController;
use App\Modules\Learning\Interface\Http\Controllers\LearningProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:sensitive'])->prefix('pembelajaran')->name('learning.')->group(function () {
    Route::get('/', [LearningController::class, 'index'])->middleware('role:teacher,homeroom,student')->name('index');

    Route::post('/materi', [LearningController::class, 'storeMaterial'])->middleware(['throttle:uploads', 'role:teacher,homeroom'])->name('materials.store');
    Route::post('/materi/{material}/dibaca', [LearningController::class, 'markMaterialRead'])->middleware('role:student')->name('materials.read');
    Route::get('/materi/{material}/unduh', [LearningController::class, 'downloadMaterial'])->middleware('role:teacher,homeroom,student')->name('materials.download');
    Route::delete('/materi/{material}', [LearningController::class, 'destroyMaterial'])->middleware('role:teacher,homeroom')->name('materials.destroy');

    Route::post('/tugas', [LearningController::class, 'storeAssignment'])->middleware('role:teacher,homeroom')->name('assignments.store');
    Route::delete('/tugas/{assignment}', [LearningController::class, 'destroyAssignment'])->middleware('role:teacher,homeroom')->name('assignments.destroy');
    Route::post('/tugas/{assignment}/kumpulkan', [LearningController::class, 'submitAssignment'])->middleware(['throttle:uploads', 'role:student'])->name('assignments.submit');
    Route::post('/pengumpulan/{submission}/nilai', [LearningController::class, 'gradeSubmission'])->middleware('role:teacher,homeroom')->name('submissions.grade');
    Route::get('/berkas/{file}/unduh', [LearningController::class, 'downloadSubmissionFile'])->middleware('role:teacher,homeroom,student')->name('submissions.files.download');
    Route::get('/progress', [LearningProgressController::class, 'index'])->name('progress.index');
});
