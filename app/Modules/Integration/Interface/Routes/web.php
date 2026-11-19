<?php

declare(strict_types=1);

use App\Modules\Integration\Interface\Http\Controllers\IntegrationSettingsController;
use App\Modules\Integration\Interface\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;

// M4-INT-01: Teacher work assignment activity proposals
Route::middleware(['auth', 'role:admin,teacher,homeroom', 'throttle:sensitive'])
    ->prefix('guru/proposal')
    ->name('integration.teacher-proposals.')
    ->group(function (): void {
        Route::get('/', [ProposalController::class, 'teacherIndex'])->name('index');
        Route::post('/', [ProposalController::class, 'storeTeacher'])->name('store');
    });

// M4-INT-02: Independent organization/student proposal & LPJ portal
Route::middleware(['auth', 'role:student,teacher,homeroom,admin,operator,committee', 'throttle:sensitive'])
    ->prefix('layanan/proposal')
    ->name('integration.independent-proposals.')
    ->group(function (): void {
        Route::get('/', [ProposalController::class, 'independentIndex'])->name('index');
        Route::post('/', [ProposalController::class, 'storeIndependent'])->name('store');
    });

// Shared revision resubmit, LPJ realization proof upload, and attachment download
Route::middleware(['auth', 'throttle:sensitive'])
    ->prefix('proposal/{proposal}')
    ->whereNumber('proposal')
    ->name('integration.proposals.')
    ->group(function (): void {
        Route::post('/revisi', [ProposalController::class, 'resubmit'])->name('resubmit');
        Route::post('/lpj', [ProposalController::class, 'submitLpj'])->name('lpj');
        Route::get('/dokumen/{documentId}', [ProposalController::class, 'downloadDocument'])
            ->whereNumber('documentId')
            ->name('document');
    });

// Reviewer queue & decision workflow (M4-INT-01 & M4-INT-02)
Route::middleware(['auth', 'role:admin,operator,principal,committee', 'throttle:sensitive'])
    ->prefix('administrasi/proposal')
    ->name('integration.proposals.review.')
    ->group(function (): void {
        Route::get('/', [ProposalController::class, 'reviewQueue'])->name('index');
        Route::post('/{proposal}/keputusan', [ProposalController::class, 'review'])
            ->whereNumber('proposal')
            ->name('decision');
    });

// M4-INT-03 & M4-INT-04: EMIS, RDM, SLiMS & external channel settings
Route::middleware(['auth', 'role:admin,operator', 'throttle:sensitive'])
    ->prefix('pengaturan/integrasi')
    ->name('integration.settings.')
    ->group(function (): void {
        Route::get('/', [IntegrationSettingsController::class, 'index'])->name('index');
        Route::put('/koneksi/{systemKey}', [IntegrationSettingsController::class, 'updateConnection'])->name('connection.update');
        Route::post('/koneksi/{systemKey}/uji', [IntegrationSettingsController::class, 'testConnection'])->name('connection.test');
        Route::post('/sinkronisasi/{systemKey}/pratinjau', [IntegrationSettingsController::class, 'previewImport'])->name('sync.preview');
        Route::post('/sinkronisasi/{systemKey}/impor', [IntegrationSettingsController::class, 'commitImport'])->name('sync.import');
        Route::get('/sinkronisasi/{systemKey}/ekspor', [IntegrationSettingsController::class, 'exportDataset'])->name('sync.export');
        Route::post('/log/{log}/ulang', [IntegrationSettingsController::class, 'retryLog'])
            ->whereNumber('log')
            ->name('sync.retry');
    });
