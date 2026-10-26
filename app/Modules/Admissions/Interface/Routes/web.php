<?php

declare(strict_types=1);

use App\Modules\Admissions\Interface\Http\Controllers\AdmissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,teacher', 'throttle:sensitive'])
    ->prefix('spmb')
    ->name('admissions.')
    ->group(function (): void {
        Route::get('/', [AdmissionsController::class, 'index'])->name('index');
        Route::post('/pendaftar', [AdmissionsController::class, 'store'])->name('applicants.store');
        Route::put('/pendaftar/{applicant}', [AdmissionsController::class, 'update'])->name('applicants.update');
        Route::post('/panitia', [AdmissionsController::class, 'assign'])->name('committee.assign');
        Route::delete('/panitia/{member}', [AdmissionsController::class, 'removeCommitteeMember'])->name('committee.remove');
    });
