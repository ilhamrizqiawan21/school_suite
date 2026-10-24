<?php

declare(strict_types=1);

use App\Modules\Academic\Interface\Http\Controllers\AttendanceController;
use App\Modules\Academic\Interface\Http\Controllers\ClassGroupController;
use App\Modules\Academic\Interface\Http\Controllers\JournalController;
use App\Modules\Academic\Interface\Http\Controllers\ScheduleController;
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

    // Jadwal Pelajaran (Schedules)
    Route::get('jadwal', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('jadwal', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::post('jadwal/terbitkan', [ScheduleController::class, 'publish'])->name('schedules.publish');
    Route::delete('jadwal/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Presensi Siswa (Attendance)
    Route::get('presensi', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('presensi', [AttendanceController::class, 'record'])->name('attendance.record');
    Route::post('presensi/{attendanceRecord}/koreksi', [AttendanceController::class, 'correct'])->name('attendance.correct');

    // Jurnal Mengajar Guru (Journals)
    Route::get('jurnal', [JournalController::class, 'index'])->name('journals.index');
    Route::post('jurnal', [JournalController::class, 'store'])->name('journals.store');
});
