<?php

declare(strict_types=1);

use App\Modules\Library\Interface\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;

// Portal & Katalog Perpustakaan Publik / Anggota (M4-SERV-03, M4-SERV-04)
Route::prefix('perpustakaan')->name('library.')->group(function (): void {
    Route::get('/', [LibraryController::class, 'replica'])->name('index');
    Route::match(['get', 'post'], '/aksi', [LibraryController::class, 'replicaAction'])->middleware('throttle:sensitive')->name('replica.action');
    Route::get('/portal', [LibraryController::class, 'publicIndex'])->name('portal');
    Route::redirect('/man4', '/perpustakaan', 301)->name('replica');
    Route::match(['get', 'post'], '/man4/aksi', [LibraryController::class, 'replicaAction'])->middleware('throttle:sensitive')->name('replica.legacy_action');
    Route::get('/koleksi/{collection}/unduh', [LibraryController::class, 'downloadEbook'])->whereNumber('collection')->name('collections.download');
    Route::post('/kunjungan', [LibraryController::class, 'recordVisit'])->middleware('throttle:sensitive')->name('visits.store');

    Route::middleware('auth')->group(function (): void {
        Route::post('/usulan', [LibraryController::class, 'requestProcurement'])->middleware('throttle:sensitive')->name('procurement.store');
        Route::post('/bebas-pustaka', [LibraryController::class, 'requestClearance'])->middleware('throttle:sensitive')->name('clearance.store');
        Route::get('/bebas-pustaka/{clearance}/pdf', [LibraryController::class, 'downloadClearancePdf'])->whereNumber('clearance')->name('clearance.pdf');
    });

    // Ruang Kerja Pustakawan Terbatas (admin, librarian)
    Route::middleware(['auth', 'role:admin,librarian', 'throttle:sensitive'])
        ->prefix('kelola')
        ->name('manage.')
        ->group(function (): void {
            Route::get('/', [LibraryController::class, 'manageIndex'])->name('index');
            Route::post('/koleksi', [LibraryController::class, 'storeCollection'])->middleware('throttle:uploads')->name('collections.store');
            Route::post('/pinjam', [LibraryController::class, 'borrow'])->name('loans.borrow');
            Route::post('/pinjam/{loan}/konfirmasi', [LibraryController::class, 'confirmReservation'])->whereNumber('loan')->name('loans.confirm');
            Route::post('/pinjam/{loan}/kembali', [LibraryController::class, 'returnLoan'])->whereNumber('loan')->name('loans.return');
            Route::post('/usulan/{procurement}/putuskan', [LibraryController::class, 'decideProcurement'])->whereNumber('procurement')->name('procurement.decide');
        });
});
