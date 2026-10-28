<?php

declare(strict_types=1);

use App\Modules\Administration\Interface\Http\Controllers\CorrespondenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,operator,principal', 'throttle:sensitive'])
    ->prefix('administrasi/persuratan')
    ->name('administration.correspondence.')
    ->group(function (): void {
        Route::get('/', [CorrespondenceController::class, 'index'])->name('index');
        Route::post('/surat', [CorrespondenceController::class, 'store'])->name('store');
        Route::patch('/surat/{record}/draf', [CorrespondenceController::class, 'updateDraft'])->name('draft');
        Route::get('/surat/{record}/pdf', [CorrespondenceController::class, 'pdf'])->name('pdf');
        Route::patch('/surat/{record}/status', [CorrespondenceController::class, 'updateStatus'])->name('status');
        Route::patch('/surat/{record}/verifikasi/revoke', [CorrespondenceController::class, 'revokeVerification'])->name('verification.revoke');
        Route::get('/surat/{record}/lampiran', [CorrespondenceController::class, 'downloadAttachment'])->name('attachment');
        Route::post('/penandatangan', [CorrespondenceController::class, 'storeSignatory'])->name('signatories.store');
        Route::post('/surat/{record}/ajukan-persetujuan', [CorrespondenceController::class, 'submitApproval'])->name('approval.submit');
        Route::post('/surat/{record}/tanda-tangan-manual', [CorrespondenceController::class, 'prepareManual'])->name('manual.prepare');
        Route::post('/surat/{record}/hasil-tanda-tangan-manual', [CorrespondenceController::class, 'uploadManual'])->name('manual.upload');
        Route::post('/surat/{record}/tte-bsre', [CorrespondenceController::class, 'requestBsre'])->name('bsre.request');
        Route::post('/tte-bsre/{signatureRequest}/ulangi', [CorrespondenceController::class, 'retryBsre'])->name('bsre.retry');
    });

Route::get('/verifikasi-surat/{token}', [CorrespondenceController::class, 'verify'])
    ->middleware('throttle:60,1')->name('administration.correspondence.verify');

Route::middleware(['auth', 'role:principal', 'throttle:sensitive'])
    ->patch('/administrasi/persuratan/surat/{record}/persetujuan', [CorrespondenceController::class, 'approve'])
    ->name('administration.correspondence.approve');
