<?php

declare(strict_types=1);

use App\Modules\Learning\Interface\Http\Controllers\LearningController;
use App\Modules\Learning\Interface\Http\Controllers\LearningProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:sensitive'])->prefix('pembelajaran')->name('learning.')->group(function () {
    Route::get('/', [LearningController::class, 'index'])->middleware('role:teacher,homeroom,student')->name('index');
    Route::get('/materi', [LearningController::class, 'materials'])->middleware('role:teacher,homeroom,student')->name('materials.index');
    Route::get('/tugas', [LearningController::class, 'assignments'])->middleware('role:teacher,homeroom,student')->name('assignments.index');
    Route::get('/tugas/{assignment}/pengumpulan', [LearningController::class, 'assignmentSubmissions'])->middleware('role:teacher,homeroom')->name('assignments.submissions');
    Route::get('/tugas/{assignment}', [LearningController::class, 'showAssignment'])->middleware('role:teacher,homeroom,student')->name('assignments.show');

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

// Endpoint siswa mengikuti struktur LMS lama: /siswa/materi, /siswa/tugas,
// dan /siswa/progress. Data tetap dilayani oleh modul Learning yang sama.
Route::middleware(['auth', 'throttle:sensitive', 'role:student'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/materi', [LearningController::class, 'materials'])->name('materi.index');
    Route::post('/materi/{material}/dibaca', [LearningController::class, 'markMaterialRead'])->name('materi.read');
    Route::get('/materi/{material}/download', [LearningController::class, 'downloadMaterial'])->name('materi.download');

    Route::get('/tugas', [LearningController::class, 'assignments'])->name('tugas.index');
    Route::get('/tugas/{assignment}', [LearningController::class, 'showAssignment'])->name('tugas.show');
    Route::post('/tugas/{assignment}/kumpul', [LearningController::class, 'submitAssignment'])->middleware('throttle:uploads')->name('tugas.kumpul');

    Route::get('/progress', [LearningProgressController::class, 'index'])->name('progress');
});
