<?php

declare(strict_types=1);

use App\Modules\Admissions\Interface\Http\Controllers\AdmissionsController;
use Illuminate\Support\Facades\Route;

Route::get('/ppdb', [AdmissionsController::class, 'publicIndex'])->name('public.admissions');

Route::middleware(['auth', 'role:admin,operator,principal,teacher', 'throttle:sensitive'])
    ->prefix('spmb')
    ->name('admissions.')
    ->group(function (): void {
        Route::get('/', [AdmissionsController::class, 'index'])->name('index');
        Route::get('/pengaturan', [AdmissionsController::class, 'section'])->defaults('section', 'settings')->name('settings.index');
        Route::get('/periode', [AdmissionsController::class, 'section'])->defaults('section', 'periods')->name('periods.index');
        Route::get('/jalur', [AdmissionsController::class, 'section'])->defaults('section', 'tracks')->name('tracks.index');
        Route::get('/panitia', [AdmissionsController::class, 'section'])->defaults('section', 'committee')->name('committee.index');
        Route::get('/pendaftar', [AdmissionsController::class, 'section'])->defaults('section', 'applicants')->name('applicants.index');
        Route::get('/berkas', [AdmissionsController::class, 'section'])->defaults('section', 'documents')->name('documents.index');
        Route::get('/seleksi', [AdmissionsController::class, 'section'])->defaults('section', 'selection')->name('selection.index');
        Route::get('/pengumuman', [AdmissionsController::class, 'section'])->defaults('section', 'announcement')->name('announcement.index');
        Route::get('/daftar-ulang', [AdmissionsController::class, 'section'])->defaults('section', 're-registration')->name('re-registration.index');
        Route::get('/laporan', [AdmissionsController::class, 'section'])->defaults('section', 'reports')->name('reports.index');

        Route::post('/periode', [AdmissionsController::class, 'storePeriod'])->name('periods.store');
        Route::post('/periode/{period}/status', [AdmissionsController::class, 'updatePeriodStatus'])->name('periods.status');
        Route::post('/jalur', [AdmissionsController::class, 'storeTrack'])->name('tracks.store');
        Route::post('/persyaratan', [AdmissionsController::class, 'storeRequirement'])->name('requirements.store');
        Route::post('/kriteria', [AdmissionsController::class, 'storeCriterion'])->name('criteria.store');
        Route::post('/pendaftar', [AdmissionsController::class, 'store'])->name('applicants.store');
        Route::put('/pendaftar/{applicant}', [AdmissionsController::class, 'update'])->name('applicants.update');
        Route::post('/pendaftar/{applicant}/berkas', [AdmissionsController::class, 'storeDocument'])->name('documents.store');
        Route::put('/berkas/{document}/verifikasi', [AdmissionsController::class, 'verifyDocument'])->name('documents.verify');
        Route::get('/berkas/{document}/unduh', [AdmissionsController::class, 'downloadDocument'])->name('documents.download');
        Route::post('/pendaftar/{applicant}/nilai', [AdmissionsController::class, 'storeScore'])->name('scores.store');
        Route::post('/periode/{period}/ranking', [AdmissionsController::class, 'rank'])->name('selection.rank');
        Route::put('/pendaftar/{applicant}/keputusan', [AdmissionsController::class, 'finalize'])->name('selection.finalize');
        Route::post('/pendaftar/{applicant}/daftar-ulang', [AdmissionsController::class, 'confirmReRegistration'])->name('re_registration.confirm');
        Route::post('/panitia', [AdmissionsController::class, 'assign'])->name('committee.assign');
        Route::delete('/panitia/{member}', [AdmissionsController::class, 'removeCommitteeMember'])->name('committee.remove');
    });
