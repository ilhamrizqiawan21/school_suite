<?php

declare(strict_types=1);

use App\Modules\Academic\Interface\Http\Controllers\StudentLedgerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,operator'])
    ->prefix('akademik/buku-induk')
    ->name('academic.student-ledger.')
    ->group(function (): void {
        Route::get('/', [StudentLedgerController::class, 'index'])->name('index');
        Route::put('/{studentId}/detail', [StudentLedgerController::class, 'updateDetails'])->whereNumber('studentId')->name('details.update');
        Route::post('/{studentId}/foto', [StudentLedgerController::class, 'uploadPhoto'])->whereNumber('studentId')->middleware('throttle:sensitive')->name('photo.store');
        Route::get('/{studentId}/foto', [StudentLedgerController::class, 'showPhoto'])->whereNumber('studentId')->middleware('throttle:sensitive')->name('photo.show');
        Route::delete('/{studentId}/foto', [StudentLedgerController::class, 'deletePhoto'])->whereNumber('studentId')->middleware('throttle:sensitive')->name('photo.destroy');
        Route::post('/{studentId}/dokumen', [StudentLedgerController::class, 'uploadDocument'])->whereNumber('studentId')->middleware('throttle:sensitive')->name('documents.store');
        Route::get('/{studentId}/dokumen/{documentId}', [StudentLedgerController::class, 'downloadDocument'])->whereNumber('studentId')->whereNumber('documentId')->middleware('throttle:sensitive')->name('documents.download');
        Route::delete('/{studentId}/dokumen/{documentId}', [StudentLedgerController::class, 'deleteDocument'])->whereNumber('studentId')->whereNumber('documentId')->middleware('throttle:sensitive')->name('documents.destroy');
        Route::get('/{studentId}/pdf', [StudentLedgerController::class, 'exportPdf'])->whereNumber('studentId')->middleware('throttle:sensitive')->name('pdf');
        Route::get('/{studentId}', [StudentLedgerController::class, 'show'])->whereNumber('studentId')->name('show');
    });
