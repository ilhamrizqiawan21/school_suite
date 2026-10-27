<?php

declare(strict_types=1);

use App\Modules\Academic\Interface\Http\Controllers\TeacherOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,operator,teacher,homeroom'])->prefix('akademik')->name('academic.')->group(function (): void {
    Route::get('absensi-guru', [TeacherOperationsController::class, 'attendanceIndex'])->name('teacher-attendance.index');
    Route::post('absensi-guru', [TeacherOperationsController::class, 'saveAttendance'])->middleware('throttle:sensitive')->name('teacher-attendance.store');
    Route::get('absensi-guru/rekap', [TeacherOperationsController::class, 'recap'])->name('teacher-attendance.recap');
    Route::get('absensi-guru/rekap/pdf', [TeacherOperationsController::class, 'recapPdf'])->name('teacher-attendance.recap.pdf');
    Route::get('absensi-guru/{attendanceId}/pengganti', [TeacherOperationsController::class, 'substitutes'])->whereNumber('attendanceId')->name('teacher-attendance.substitutes');
    Route::post('absensi-guru/{attendanceId}/pengganti', [TeacherOperationsController::class, 'assignSubstitute'])->whereNumber('attendanceId')->middleware('throttle:sensitive')->name('teacher-attendance.substitutes.store');

    Route::get('izin-guru', [TeacherOperationsController::class, 'leaveIndex'])->name('teacher-leave.index');
    Route::post('izin-guru', [TeacherOperationsController::class, 'submitLeave'])->middleware('throttle:sensitive')->name('teacher-leave.store');
    Route::delete('izin-guru/{leaveId}', [TeacherOperationsController::class, 'cancelLeave'])->whereNumber('leaveId')->middleware('throttle:sensitive')->name('teacher-leave.destroy');
    Route::patch('izin-guru/{leaveId}/keputusan', [TeacherOperationsController::class, 'reviewLeave'])->whereNumber('leaveId')->middleware('throttle:sensitive')->name('teacher-leave.review');
});
