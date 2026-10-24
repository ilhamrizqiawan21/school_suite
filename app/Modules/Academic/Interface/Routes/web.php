<?php

declare(strict_types=1);

use App\Modules\Academic\Interface\Http\Controllers\ClassGroupController;
use App\Modules\Academic\Interface\Http\Controllers\StudentController;
use App\Modules\Academic\Interface\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('akademik')->name('academic.')->group(function () {
    // Siswa
    Route::get('siswa', [StudentController::class, 'index'])->name('students.index');
    Route::post('siswa', [StudentController::class, 'store'])->name('students.store');
    Route::put('siswa/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::post('siswa/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('siswa/template', [StudentController::class, 'template'])->name('students.template');

    // Guru / Pendidik
    Route::get('guru', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('guru', [TeacherController::class, 'store'])->name('teachers.store');
    Route::put('guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::post('guru/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::get('guru/template', [TeacherController::class, 'template'])->name('teachers.template');

    // Rombongan Belajar / Kelas
    Route::get('kelas', [ClassGroupController::class, 'index'])->name('classes.index');
    Route::post('kelas', [ClassGroupController::class, 'store'])->name('classes.store');
    Route::post('kelas/{classGroup}/wali-kelas', [ClassGroupController::class, 'assignHomeroom'])->name('classes.assign_homeroom');
});
