<?php

declare(strict_types=1);

use App\Modules\Administration\Interface\Http\Controllers\ExternalLetterController;
use App\Modules\Administration\Interface\Http\Controllers\GuestbookController;
use App\Modules\Administration\Interface\Http\Controllers\LegalizationController;
use App\Modules\Administration\Interface\Http\Controllers\PtspAdminController;
use App\Modules\Administration\Interface\Http\Controllers\PtspPublicController;
use Illuminate\Support\Facades\Route;

// Public & Applicant PTSP Routes (M4-TU-01, M4-TU-02, M4-TU-03)
Route::prefix('layanan/ptsp')->name('administration.ptsp.public.')->group(function (): void {
    Route::get('/', [PtspPublicController::class, 'index'])->name('index');
    Route::post('/draf', [PtspPublicController::class, 'saveDraft'])->middleware('throttle:uploads')->name('draft.save');
    Route::delete('/draf/{ptspRequest}/lampiran/{documentId}', [PtspPublicController::class, 'removeDraftAttachment'])
        ->whereNumber(['ptspRequest', 'documentId'])
        ->middleware('throttle:sensitive')
        ->name('draft.remove-attachment');
    Route::post('/ajukan', [PtspPublicController::class, 'submit'])->middleware('throttle:uploads')->name('submit');
    Route::get('/resi/{ticketCode}', [PtspPublicController::class, 'receipt'])->name('receipt');
    Route::get('/resi/{ticketCode}/pdf', [PtspPublicController::class, 'receiptPdf'])->name('receipt.pdf');
    Route::get('/{ticketCode}/perbaiki', [PtspPublicController::class, 'showRevision'])->name('revision');
    Route::post('/{ticketCode}/perbaiki', [PtspPublicController::class, 'submitRevision'])->middleware('throttle:uploads')->name('revision.submit');
    Route::get('/{ticketCode}/dokumen/{documentId}', [PtspPublicController::class, 'downloadDocument'])
        ->whereNumber('documentId')
        ->middleware('throttle:sensitive')
        ->name('document');
});

// Public External Official Letter Submission (M4-TU-05)
Route::prefix('layanan/surat-dinas')->name('administration.external-letters.')->group(function (): void {
    Route::get('/', [ExternalLetterController::class, 'create'])->name('create');
    Route::post('/', [ExternalLetterController::class, 'store'])->middleware('throttle:uploads')->name('store');
    Route::get('/tanda-terima/{ticketCode}', [ExternalLetterController::class, 'receipt'])->name('receipt');
    Route::get('/tanda-terima/{ticketCode}/pdf', [ExternalLetterController::class, 'receiptPdf'])->name('receipt.pdf');
});

// Public Self-Registration Guestbook Kiosk (M4-TU-06, never exposes guest list)
Route::prefix('layanan/buku-tamu')->name('administration.guestbook.public.')->group(function (): void {
    Route::get('/', [GuestbookController::class, 'publicCreate'])->name('create');
    Route::post('/', [GuestbookController::class, 'publicStore'])->middleware('throttle:sensitive')->name('store');
});

// Internal Staff TU / PTSP Workspace (M4-TU-01, M4-TU-03)
Route::middleware(['auth', 'role:admin,operator,ptsp', 'throttle:sensitive'])
    ->prefix('administrasi/ptsp')
    ->name('administration.ptsp.admin.')
    ->group(function (): void {
        Route::get('/', [PtspAdminController::class, 'index'])->name('index');
        Route::post('/katalog', [PtspAdminController::class, 'storeService'])->name('services.store');
        Route::put('/katalog/{service}', [PtspAdminController::class, 'updateService'])->whereNumber('service')->name('services.update');
        Route::patch('/katalog/{service}/status', [PtspAdminController::class, 'toggleServiceStatus'])->whereNumber('service')->name('services.toggle');

        Route::post('/permohonan/{ptspRequest}/tinjau', [PtspAdminController::class, 'startReview'])->whereNumber('ptspRequest')->name('requests.review');
        Route::post('/permohonan/{ptspRequest}/revisi', [PtspAdminController::class, 'requestRevision'])->whereNumber('ptspRequest')->name('requests.revision');
        Route::post('/permohonan/{ptspRequest}/tolak', [PtspAdminController::class, 'reject'])->whereNumber('ptspRequest')->name('requests.reject');
        Route::post('/permohonan/{ptspRequest}/disposisi', [PtspAdminController::class, 'disposition'])->whereNumber('ptspRequest')->name('requests.disposition');
        Route::post('/permohonan/{ptspRequest}/terbitkan', [PtspAdminController::class, 'issueResult'])->whereNumber('ptspRequest')->name('requests.issue');
        Route::post('/permohonan/{ptspRequest}/ambil-fisik', [PtspAdminController::class, 'recordPickup'])->whereNumber('ptspRequest')->name('requests.pickup');
        Route::get('/permohonan/{ptspRequest}/dokumen/{documentId}', [PtspAdminController::class, 'downloadDocument'])->whereNumber(['ptspRequest', 'documentId'])->name('requests.document');
    });

// Public Legalisir, Surat Kuasa & SPTJM Routes (M4-LIF-05, M4-LIF-06)
Route::prefix('layanan/legalisir')->name('administration.legalization.public.')->group(function (): void {
    Route::get('/', [LegalizationController::class, 'publicIndex'])->name('index');
    Route::post('/', [LegalizationController::class, 'publicSubmit'])->middleware('throttle:uploads')->name('submit');
    Route::get('/dokumen/{document}', [LegalizationController::class, 'downloadGeneratedDocument'])->whereNumber('document')->name('document');
});

Route::prefix('layanan/surat-kuasa')->name('administration.power-of-attorney.')->group(function (): void {
    Route::get('/', [LegalizationController::class, 'powerOfAttorneyIndex'])->name('index');
    Route::post('/', [LegalizationController::class, 'generateAuthorizationDocument'])->middleware('throttle:sensitive')->name('generate');
});

// Internal Receptionist & TU Guestbook Workspace (M4-TU-06)
Route::middleware(['auth', 'role:admin,operator,ptsp,receptionist', 'throttle:sensitive'])
    ->prefix('administrasi/buku-tamu')
    ->name('administration.guestbook.admin.')
    ->group(function (): void {
        Route::get('/', [GuestbookController::class, 'index'])->name('index');
        Route::post('/', [GuestbookController::class, 'store'])->name('store');
        Route::post('/{visit}/pulang', [GuestbookController::class, 'checkOut'])->whereNumber('visit')->name('checkout');
        Route::get('/ekspor', [GuestbookController::class, 'export'])->name('export');
    });

// Internal TU Legalization Registry (M4-LIF-05)
Route::middleware(['auth', 'role:admin,operator,ptsp', 'throttle:sensitive'])
    ->prefix('administrasi/legalisir')
    ->name('administration.legalization.admin.')
    ->group(function (): void {
        Route::get('/', [LegalizationController::class, 'adminIndex'])->name('index');
        Route::post('/{legalizationRequest}/verifikasi-asli', [LegalizationController::class, 'verifyOriginal'])->whereNumber('legalizationRequest')->name('verify-original');
        Route::post('/{legalizationRequest}/sahkan', [LegalizationController::class, 'approve'])->whereNumber('legalizationRequest')->name('approve');
        Route::post('/{legalizationRequest}/keputusan', [LegalizationController::class, 'rejectOrRevision'])->whereNumber('legalizationRequest')->name('decide');
        Route::post('/{legalizationRequest}/ambil', [LegalizationController::class, 'recordPickup'])->whereNumber('legalizationRequest')->name('pickup');
    });
