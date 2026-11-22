<?php

declare(strict_types=1);

use App\Modules\Homeroom\Interface\Http\Controllers\BkDisciplineController;
use App\Modules\Homeroom\Interface\Http\Controllers\ComplaintController;
use App\Modules\Homeroom\Interface\Http\Controllers\HomeroomDashboardController;
use App\Modules\Homeroom\Interface\Http\Controllers\PicketGateController;
use App\Modules\Homeroom\Interface\Http\Controllers\StudentDisciplineController;
use App\Modules\Homeroom\Interface\Http\Controllers\StudentObservationController;
use App\Modules\Homeroom\Interface\Http\Controllers\StudentPermitController;
use Illuminate\Support\Facades\Route;

// Pengajuan Izin & Dispensasi Wali/Siswa/Token (M4-OPS-01, M4-OPS-02)
Route::prefix('layanan/izin-siswa')->name('homeroom.permits.public.')->group(function (): void {
    Route::get('/', [StudentPermitController::class, 'publicIndex'])->name('index');
    Route::post('/', [StudentPermitController::class, 'submit'])->middleware('throttle:uploads')->name('submit');
    Route::post('/cari-siswa', [StudentPermitController::class, 'lookupStudent'])->middleware('throttle:sensitive')->name('lookup');
    Route::post('/cek-status', [StudentPermitController::class, 'lookupStatus'])->middleware('throttle:sensitive')->name('status');
    Route::post('/cek-token', [StudentPermitController::class, 'checkToken'])->middleware('throttle:sensitive')->name('check-token');
    Route::post('/{dispensation}/perbaiki', [StudentPermitController::class, 'resubmit'])->whereNumber('dispensation')->middleware('throttle:uploads')->name('resubmit');
    Route::get('/{dispensation}/pdf', [StudentPermitController::class, 'proofPdf'])->whereNumber('dispensation')->name('pdf');
    Route::get('/{dispensation}/lampiran', [StudentPermitController::class, 'downloadAttachment'])->whereNumber('dispensation')->name('attachment');
});

// Antrean Verifikasi Izin Wali Kelas & Petugas (M4-OPS-02)
Route::middleware(['auth', 'role:admin,operator,homeroom,picket', 'throttle:sensitive'])
    ->prefix('wali-kelas/izin')
    ->name('homeroom.permits.queue.')
    ->group(function (): void {
        Route::get('/', [StudentPermitController::class, 'queueIndex'])->name('index');
        Route::post('/token', [StudentPermitController::class, 'issueToken'])->name('token');
        Route::post('/{dispensation}/putuskan', [StudentPermitController::class, 'decide'])->whereNumber('dispensation')->name('decide');
        Route::post('/{dispensation}/koreksi', [StudentPermitController::class, 'correct'])->whereNumber('dispensation')->name('correct');
    });

// Ruang Kerja Piket Izin Keluar (M4-OPS-04)
Route::middleware(['auth', 'role:admin,operator,picket', 'throttle:sensitive'])
    ->prefix('piket/izin-keluar')
    ->name('homeroom.picket.')
    ->group(function (): void {
        Route::get('/', [PicketGateController::class, 'picketIndex'])->name('index');
        Route::get('/cari-siswa', [PicketGateController::class, 'searchStudents'])->name('search');
        Route::post('/', [PicketGateController::class, 'issuePass'])->name('issue');
        Route::post('/{gatePass}/batal', [PicketGateController::class, 'cancelPass'])->whereNumber('gatePass')->name('cancel');
    });

// Layar Pos Jaga Satpam dengan Gerbang PIN Papan Angka (M4-OPS-04)
Route::middleware(['auth', 'role:admin,operator,security_guard', 'throttle:sensitive'])
    ->prefix('pos-jaga')
    ->name('homeroom.security-post.')
    ->group(function (): void {
        Route::get('/', [PicketGateController::class, 'postIndex'])->name('index');
        Route::post('/buka-akses', [PicketGateController::class, 'unlock'])->name('unlock');
        Route::post('/kunci', [PicketGateController::class, 'lock'])->name('lock');
        Route::post('/atur-pin', [PicketGateController::class, 'configurePin'])->name('configure-pin');
        Route::post('/scan', [PicketGateController::class, 'scanPass'])->name('scan');
    });

// Ruang Kerja Disiplin BK Terbatas (M4-OPS-05)
Route::middleware(['auth', 'role:admin,counselor', 'throttle:sensitive'])
    ->prefix('bk/kasus')
    ->name('homeroom.bk.')
    ->group(function (): void {
        Route::get('/', [BkDisciplineController::class, 'index'])->name('index');
        Route::get('/cari-siswa', [BkDisciplineController::class, 'searchStudents'])->name('search');
        Route::post('/', [BkDisciplineController::class, 'store'])->middleware('throttle:uploads')->name('store');
        Route::put('/{incident}', [BkDisciplineController::class, 'update'])->whereNumber('incident')->middleware('throttle:uploads')->name('update');
        Route::get('/{incident}/eviden/{documentId}', [BkDisciplineController::class, 'downloadEvidence'])->whereNumber(['incident', 'documentId'])->name('evidence');
        Route::get('/ekspor', [BkDisciplineController::class, 'export'])->name('export');
    });

// Siap Lapor (Aduan Bernama/Anonim) & Transparansi Publik (M4-SERV-01, M4-SERV-02)
Route::prefix('layanan/lapor')->name('homeroom.complaints.public.')->group(function (): void {
    Route::get('/', [ComplaintController::class, 'publicCreate'])->name('create');
    Route::post('/', [ComplaintController::class, 'publicStore'])->middleware('throttle:uploads')->name('store');
});
Route::get('/lapor/transparansi', [ComplaintController::class, 'transparencyIndex'])->name('homeroom.complaints.transparency');

// Ruang Kerja Satgas Pengaduan Terbatas (M4-SERV-02)
Route::middleware(['auth', 'role:admin,task_force,counselor', 'throttle:sensitive'])
    ->prefix('satgas/aduan')
    ->name('homeroom.complaints.satgas.')
    ->group(function (): void {
        Route::get('/', [ComplaintController::class, 'taskForceIndex'])->name('index');
        Route::post('/{report}/tangani', [ComplaintController::class, 'handle'])->whereNumber('report')->middleware('throttle:uploads')->name('handle');
        Route::get('/{report}/eviden/{documentId}', [ComplaintController::class, 'downloadEvidence'])->whereNumber(['report', 'documentId'])->name('evidence');
    });

Route::middleware(['auth', 'role:homeroom'])->prefix('wali-kelas')->name('homeroom.')->group(function () {
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
