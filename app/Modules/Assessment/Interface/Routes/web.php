<?php

declare(strict_types=1);

use App\Modules\Assessment\Interface\Http\Controllers\AssessmentSchemeController;
use App\Modules\Assessment\Interface\Http\Controllers\ExamController;
use App\Modules\Assessment\Interface\Http\Controllers\QuestionBankController;
use App\Modules\Assessment\Interface\Http\Controllers\ReportCardController;
use App\Modules\Assessment\Interface\Http\Controllers\ReportingController;
use App\Modules\Assessment\Interface\Http\Controllers\ScoreInputController;
use App\Modules\Assessment\Interface\Http\Controllers\StudentAttitudeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('penilaian')->name('assessment.')->group(function () {
    Route::get('bank-soal', [QuestionBankController::class, 'index'])->name('questions.index');
    Route::post('bank-soal', [QuestionBankController::class, 'store'])->middleware('throttle:sensitive')->name('questions.store');
    Route::put('bank-soal/{question}', [QuestionBankController::class, 'update'])->middleware('throttle:sensitive')->name('questions.update');
    Route::post('bank-soal/{question}/arsip', [QuestionBankController::class, 'archive'])->middleware('throttle:sensitive')->name('questions.archive');
    Route::post('bank-soal/{question}/pulihkan', [QuestionBankController::class, 'restore'])->middleware('throttle:sensitive')->name('questions.restore');
    Route::post('bank-soal/{question}/duplikasi', [QuestionBankController::class, 'duplicate'])->middleware('throttle:sensitive')->name('questions.duplicate');

    // Input Nilai Guru
    Route::get('skor', [ScoreInputController::class, 'index'])->name('scores.index');
    Route::post('skor', [ScoreInputController::class, 'storeBatch'])->name('scores.store');
    Route::get('sikap', [StudentAttitudeController::class, 'index'])->name('attitudes.index');
    Route::post('sikap', [StudentAttitudeController::class, 'store'])->middleware('throttle:sensitive')->name('attitudes.store');

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

Route::middleware(['auth', 'throttle:sensitive'])->prefix('laporan')->name('reporting.')->group(function () {
    Route::get('/', [ReportingController::class, 'index'])->name('index');
    Route::get('/nilai', [ReportingController::class, 'scores'])->name('scores');
    Route::get('/presensi', [ReportingController::class, 'attendance'])->name('attendance');
    Route::get('/tugas', [ReportingController::class, 'assignments'])->name('assignments');
    Route::get('/sikap', [ReportingController::class, 'attitudes'])->name('attitudes');
});
Route::middleware(['auth', 'throttle:sensitive'])->get('/penilaian/rapor/{report}/pdf', [ReportingController::class, 'reportPdf'])->name('assessment.reports.pdf');

Route::middleware(['auth', 'throttle:sensitive'])->prefix('ujian')->name('exams.')->group(function () {
    Route::get('/', [ExamController::class, 'index'])->name('index');
    Route::post('/', [ExamController::class, 'storeExam'])->name('store');
    Route::post('/{exam}/terbitkan', [ExamController::class, 'publish'])->name('publish');
    Route::post('/{exam}/mulai', [ExamController::class, 'start'])->name('start');
    Route::get('/{exam}/hasil', [ExamController::class, 'results'])->name('results');
    Route::post('/{exam}/hasil/terbitkan', [ExamController::class, 'publishResults'])->name('results.publish');
    Route::get('/{exam}/hasil/export', [ExamController::class, 'export'])->name('results.export');
    Route::get('/attempt/{attempt}', [ExamController::class, 'attempt'])->name('attempt');
    Route::put('/attempt/{attempt}/jawaban', [ExamController::class, 'autosave'])->name('answer');
    Route::post('/attempt/{attempt}/heartbeat', [ExamController::class, 'heartbeat'])->name('heartbeat');
    Route::post('/attempt/{attempt}/pelanggaran', [ExamController::class, 'violation'])->name('violation');
    Route::post('/attempt/{attempt}/kirim', [ExamController::class, 'submit'])->name('submit');
    Route::post('/jawaban/{answer}/nilai', [ExamController::class, 'gradeEssay'])->name('essay.grade');
});
