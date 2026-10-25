<?php

declare(strict_types=1);

use App\Modules\Academic\Interface\Http\Controllers\AcademicSettingsController;
use App\Modules\Academic\Interface\Http\Controllers\AttendanceController;
use App\Modules\Academic\Interface\Http\Controllers\ClassGroupController;
use App\Modules\Academic\Interface\Http\Controllers\JournalController;
use App\Modules\Academic\Interface\Http\Controllers\OnlineClassController;
use App\Modules\Academic\Interface\Http\Controllers\ScheduleController;
use App\Modules\Academic\Interface\Http\Controllers\StudentController;
use App\Modules\Academic\Interface\Http\Controllers\StudentLifecycleController;
use App\Modules\Academic\Interface\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('akademik')->name('academic.')->group(function () {
    Route::middleware('throttle:sensitive')->prefix('pengaturan')->name('settings.')->group(function () {
        Route::get('/', [AcademicSettingsController::class, 'index'])->name('index');
        Route::post('/tahun-ajaran', [AcademicSettingsController::class, 'storeYear'])->name('years.store');
        Route::put('/tahun-ajaran/{academicYear}', [AcademicSettingsController::class, 'updateYear'])->name('years.update');
        Route::post('/tahun-ajaran/{academicYear}/aktifkan', [AcademicSettingsController::class, 'activateYear'])->name('years.activate');
        Route::post('/tahun-ajaran/{academicYear}/semester', [AcademicSettingsController::class, 'storeTerm'])->name('terms.store');
        Route::post('/semester/{term}/aktifkan', [AcademicSettingsController::class, 'activateTerm'])->name('terms.activate');
        Route::post('/mata-pelajaran', [AcademicSettingsController::class, 'storeSubject'])->name('subjects.store');
        Route::put('/mata-pelajaran/{subject}', [AcademicSettingsController::class, 'updateSubject'])->name('subjects.update');
        Route::delete('/mata-pelajaran/{subject}', [AcademicSettingsController::class, 'destroySubject'])->name('subjects.destroy');
    });
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
    Route::get('presensi', [AttendanceController::class, 'index'])->middleware('role:teacher,homeroom')->name('attendance.index');
    Route::get('presensi/rekap', [AttendanceController::class, 'recap'])->name('attendance.recap');
    Route::get('presensi/rekap/export', [AttendanceController::class, 'exportRecap'])->middleware('throttle:sensitive')->name('attendance.recap.export');
    Route::post('presensi', [AttendanceController::class, 'record'])->middleware('role:teacher,homeroom')->name('attendance.record');
    Route::post('presensi/{attendanceRecord}/koreksi', [AttendanceController::class, 'correct'])->middleware('role:teacher,homeroom')->name('attendance.correct');

    // Jurnal Mengajar Guru (Journals)
    Route::get('jurnal', [JournalController::class, 'index'])->middleware('role:teacher,homeroom')->name('journals.index');
    Route::post('jurnal', [JournalController::class, 'store'])->middleware('role:teacher,homeroom')->name('journals.store');

    Route::get('kelas-daring', [OnlineClassController::class, 'index'])->middleware('role:teacher,homeroom,student')->name('online.index');
    Route::post('kelas-daring', [OnlineClassController::class, 'store'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('online.store');
    Route::put('kelas-daring/{onlineClassSession}/status', [OnlineClassController::class, 'updateStatus'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('online.status');
    Route::get('kelas-daring/{onlineClassSession}/presensi', [OnlineClassController::class, 'attendance'])->middleware('role:teacher,homeroom')->name('online.attendance');
    Route::put('kelas-daring/{onlineClassSession}/presensi', [OnlineClassController::class, 'recordAttendance'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('online.attendance.store');

    Route::get('siklus-siswa', [StudentLifecycleController::class, 'index'])->name('lifecycle.index');
    Route::post('siklus-siswa', [StudentLifecycleController::class, 'store'])->middleware('throttle:sensitive')->name('lifecycle.store');
});
