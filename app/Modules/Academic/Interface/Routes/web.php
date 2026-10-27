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
use App\Modules\Academic\Interface\Http\Controllers\TeachingPlanController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/buku_induk.php';
require __DIR__.'/teacher_operations.php';

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
    Route::get('siswa/export/excel', [StudentController::class, 'exportExcel'])->name('students.export.excel');
    Route::get('siswa/export/pdf', [StudentController::class, 'exportPdf'])->name('students.export.pdf');
    Route::post('siswa/{student}/reset-password', [StudentController::class, 'resetPassword'])->middleware('throttle:sensitive')->name('students.reset-password');
    Route::delete('siswa/{student}', [StudentController::class, 'destroy'])->middleware('throttle:sensitive')->name('students.destroy');
    Route::put('siswa/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::post('siswa/import/preview', [StudentController::class, 'previewImport'])->middleware('throttle:sensitive')->name('students.import.preview');
    Route::post('siswa/import/{token}/confirm', [StudentController::class, 'confirmImport'])->middleware('throttle:sensitive')->name('students.import.confirm');
    Route::post('siswa/import', [StudentController::class, 'import'])->middleware('throttle:sensitive')->name('students.import');
    Route::get('siswa/template', [StudentController::class, 'template'])->name('students.template');

    // Guru / Pendidik
    Route::get('guru', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('guru', [TeacherController::class, 'store'])->name('teachers.store');
    Route::get('guru/export/excel', [TeacherController::class, 'exportExcel'])->name('teachers.export.excel');
    Route::post('guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->middleware('throttle:sensitive')->name('teachers.reset-password');
    Route::delete('guru/{teacher}', [TeacherController::class, 'destroy'])->middleware('throttle:sensitive')->name('teachers.destroy');
    Route::put('guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::post('guru/import/preview', [TeacherController::class, 'previewImport'])->middleware('throttle:sensitive')->name('teachers.import.preview');
    Route::post('guru/import/{token}/confirm', [TeacherController::class, 'confirmImport'])->middleware('throttle:sensitive')->name('teachers.import.confirm');
    Route::post('guru/import', [TeacherController::class, 'import'])->middleware('throttle:sensitive')->name('teachers.import');
    Route::get('guru/template', [TeacherController::class, 'template'])->name('teachers.template');

    // Rombongan Belajar / Kelas
    Route::get('kelas', [ClassGroupController::class, 'index'])->name('classes.index');
    Route::post('kelas', [ClassGroupController::class, 'store'])->name('classes.store');
    Route::put('kelas/{classGroup}', [ClassGroupController::class, 'update'])->name('classes.update');
    Route::delete('kelas/{classGroup}', [ClassGroupController::class, 'destroy'])->middleware('throttle:sensitive')->name('classes.destroy');
    Route::post('kelas/{classGroup}/wali-kelas', [ClassGroupController::class, 'assignHomeroom'])->name('classes.assign_homeroom');

    // Jadwal Pelajaran (Schedules)
    Route::get('jadwal', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('jadwal', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::put('jadwal/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::post('jadwal/terbitkan', [ScheduleController::class, 'publish'])->name('schedules.publish');
    Route::delete('jadwal/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Presensi Siswa (Attendance)
    Route::get('presensi', [AttendanceController::class, 'index'])->middleware('role:teacher,homeroom')->name('attendance.index');
    Route::get('presensi/rekap', [AttendanceController::class, 'recap'])->name('attendance.recap');
    Route::get('presensi/rekap/export', [AttendanceController::class, 'exportRecap'])->middleware('throttle:sensitive')->name('attendance.recap.export');
    Route::post('presensi', [AttendanceController::class, 'record'])->middleware('role:teacher,homeroom')->name('attendance.record');
    Route::post('presensi/{attendanceRecord}/koreksi', [AttendanceController::class, 'correct'])->middleware('role:teacher,homeroom')->name('attendance.correct');

    // Jurnal Harian Guru (Journals)
    Route::get('jurnal', [JournalController::class, 'index'])->middleware('role:teacher,homeroom')->name('journals.index');
    Route::post('jurnal/sesi', [JournalController::class, 'createSession'])->middleware('role:teacher,homeroom')->name('journals.sessions.store');
    Route::post('jurnal', [JournalController::class, 'store'])->middleware('role:teacher,homeroom')->name('journals.store');
    Route::delete('jurnal/{lessonSession}', [JournalController::class, 'destroy'])->middleware('role:teacher,homeroom')->name('journals.destroy');
    Route::get('jurnal/{lessonSession}/word', [JournalController::class, 'exportWord'])->middleware('role:teacher,homeroom')->name('journals.export.word');
    Route::get('jurnal/{lessonSession}/pdf', [JournalController::class, 'exportPdf'])->middleware('role:teacher,homeroom')->name('journals.export.pdf');

    // Perangkat Pembelajaran (ATP / Silabus)
    Route::get('perangkat/atp', [TeachingPlanController::class, 'index'])->middleware('role:teacher,homeroom')->name('plans.index');
    Route::post('perangkat/atp', [TeachingPlanController::class, 'store'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('plans.store');
    Route::put('perangkat/atp/{plan}', [TeachingPlanController::class, 'update'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('plans.update');
    Route::delete('perangkat/atp/{plan}', [TeachingPlanController::class, 'destroy'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('plans.destroy');
    Route::get('perangkat/dokumen', [TeachingPlanController::class, 'documents'])->middleware('role:teacher,homeroom')->name('documents.index');
    Route::post('perangkat/dokumen', [TeachingPlanController::class, 'saveDocument'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('documents.store');
    Route::put('perangkat/dokumen/{document}', [TeachingPlanController::class, 'updateDocument'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('documents.update');
    Route::delete('perangkat/dokumen/{document}', [TeachingPlanController::class, 'destroyDocument'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('documents.destroy');
    Route::get('perangkat/paket-administrasi', [TeachingPlanController::class, 'packagePage'])->middleware('role:teacher,homeroom')->name('documents.package');
    Route::get('perangkat/paket-administrasi/unduh', [TeachingPlanController::class, 'exportPackage'])->middleware('role:teacher,homeroom')->name('documents.package.download');
    Route::get('perangkat/modul-ajar', [TeachingPlanController::class, 'moduleAjar'])->middleware('role:teacher,homeroom')->name('module-ajar.index');
    Route::post('perangkat/modul-ajar', [TeachingPlanController::class, 'saveModuleAjar'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('module-ajar.store');
    Route::put('perangkat/modul-ajar/{plan}', [TeachingPlanController::class, 'updateModuleAjar'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('module-ajar.update');

    Route::get('kelas-daring', [OnlineClassController::class, 'index'])->middleware('role:teacher,homeroom,student')->name('online.index');
    Route::post('kelas-daring', [OnlineClassController::class, 'store'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('online.store');
    Route::put('kelas-daring/{onlineClassSession}/status', [OnlineClassController::class, 'updateStatus'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('online.status');
    Route::get('kelas-daring/{onlineClassSession}/presensi', [OnlineClassController::class, 'attendance'])->middleware('role:teacher,homeroom')->name('online.attendance');
    Route::put('kelas-daring/{onlineClassSession}/presensi', [OnlineClassController::class, 'recordAttendance'])->middleware(['throttle:sensitive', 'role:teacher,homeroom'])->name('online.attendance.store');

    Route::get('siklus-siswa', [StudentLifecycleController::class, 'index'])->name('lifecycle.index');
    Route::post('siklus-siswa', [StudentLifecycleController::class, 'store'])->middleware('throttle:sensitive')->name('lifecycle.store');
});

Route::middleware(['auth', 'role:principal'])->prefix('kepsek/laporan')->name('principal.reports.')->group(function (): void {
    Route::get('rekap-absensi', [AttendanceController::class, 'recap'])->name('attendance');
});
