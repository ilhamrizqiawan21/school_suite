<?php

declare(strict_types=1);

use App\Modules\Core\Interface\Http\Controllers\AuthController;
use App\Modules\Core\Interface\Http\Controllers\BrandMediaController;
use App\Modules\Core\Interface\Http\Controllers\ExternalApplicantAuthController;
use App\Modules\Core\Interface\Http\Controllers\PasswordController;
use App\Modules\Core\Interface\Http\Controllers\ProfileController;
use App\Modules\Core\Interface\Http\Controllers\PublicPortalAdminController;
use App\Modules\Core\Interface\Http\Controllers\PublicPortalController;
use App\Modules\Core\Interface\Http\Controllers\SchoolController;
use App\Modules\Core\Interface\Http\Controllers\SchoolGalleryController;
use App\Modules\Core\Interface\Http\Controllers\SchoolNewsController;
use App\Modules\Core\Interface\Http\Controllers\SchoolOrganizationController;
use App\Modules\Core\Interface\Http\Controllers\SchoolProfileController;
use App\Modules\Core\Interface\Http\Controllers\SecurityOperationsController;
use App\Modules\Core\Interface\Http\Controllers\UnifiedTrackingController;
use App\Modules\Core\Interface\Http\Controllers\UserController;
use App\Modules\Core\Interface\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/branding/sekolah/{school}/{kind}', [BrandMediaController::class, 'school'])->where('kind', 'logo|favicon')->name('branding.school');
Route::get('/profil-sekolah', [SchoolProfileController::class, 'index'])->name('core.school.public');
Route::get('/akademik-fasilitas', [SchoolProfileController::class, 'programs'])->name('core.school.public.programs');
Route::get('/kesiswaan-prestasi', [SchoolProfileController::class, 'studentLife'])->name('core.school.public.student-life');
Route::get('/kontak', [SchoolProfileController::class, 'contact'])->name('core.school.public.contact');
Route::get('/struktur-organisasi', [SchoolOrganizationController::class, 'publicIndex'])->name('core.organization.public');
Route::get('/galeri', [SchoolGalleryController::class, 'publicIndex'])->name('core.gallery.public.index');
Route::get('/galeri/video', [SchoolGalleryController::class, 'publicVideos'])->name('core.gallery.public.videos');
Route::get('/galeri/foto/{photo}', [SchoolGalleryController::class, 'photo'])->whereNumber('photo')->name('core.gallery.photo');
Route::get('/struktur-organisasi/foto/{member}', [SchoolOrganizationController::class, 'photo'])->whereNumber('member')->name('core.organization.photo');
Route::get('/berita', [SchoolNewsController::class, 'publicIndex'])->name('core.news.public.index');
Route::get('/berita/{slug}/foto/{photo}', [SchoolNewsController::class, 'photo'])->whereNumber('photo')->name('core.news.photo');
Route::get('/berita/{slug}', [SchoolNewsController::class, 'publicShow'])->name('core.news.public.show');
Route::get('/e-layanan', [PublicPortalController::class, 'services'])->name('core.public.services');
Route::get('/direktori-gtk', [PublicPortalController::class, 'teachers'])->name('core.public.teachers');
Route::get('/prestasi', [PublicPortalController::class, 'achievements'])->name('core.public.achievements');
Route::get('/pusat-unduh', [PublicPortalController::class, 'downloads'])->name('core.public.downloads');
Route::get('/pusat-unduh/{download}/unduh', [PublicPortalController::class, 'downloadFile'])->whereNumber('download')->name('core.public.downloads.file');
Route::get('/zona-integritas', [PublicPortalController::class, 'integrityZone'])->name('core.public.integrity');
Route::post('/zona-integritas/ikm', [PublicPortalController::class, 'submitIkm'])->middleware('throttle:sensitive')->name('core.public.ikm.store');
Route::get('/jadwal-salat', [PublicPortalController::class, 'prayerAndStats'])->name('core.public.prayer');

// Pelacakan Terpadu & Verifikasi Dokumen/Siswa Publik (M4-TU-01, M4-TU-04, M4-LIF-04)
Route::get('/lacak', [UnifiedTrackingController::class, 'index'])->name('core.tracking.index');
Route::post('/lacak', [UnifiedTrackingController::class, 'lookup'])->middleware('throttle:sensitive')->name('core.tracking.lookup');
Route::get('/data-siswa', [VerificationController::class, 'studentData'])->name('core.public.students');
Route::post('/data-siswa/cek', [VerificationController::class, 'checkStudentData'])->middleware('throttle:sensitive')->name('core.public.students.check');
Route::get('/verifikasi', [VerificationController::class, 'index'])->name('core.verification.index');
Route::post('/verifikasi', [VerificationController::class, 'verify'])->middleware('throttle:sensitive')->name('core.verification.check');

Route::prefix('layanan/ptsp/akun')->name('core.ptsp.account.')->group(function (): void {
    Route::get('/', [ExternalApplicantAuthController::class, 'show'])->name('show');
    Route::post('/daftar', [ExternalApplicantAuthController::class, 'register'])->middleware('throttle:sensitive')->name('register');
    Route::post('/masuk', [ExternalApplicantAuthController::class, 'login'])->middleware('throttle:sensitive')->name('login');
    Route::post('/keluar', [ExternalApplicantAuthController::class, 'logout'])->name('logout');
    Route::patch('/profil', [ExternalApplicantAuthController::class, 'updateProfile'])->middleware('throttle:sensitive')->name('profile');
    Route::post('/pulihkan', [ExternalApplicantAuthController::class, 'requestRecovery'])->middleware('throttle:sensitive')->name('recovery');
    Route::post('/reset-sandi', [ExternalApplicantAuthController::class, 'resetPassword'])->middleware('throttle:sensitive')->name('reset');
});

// Guest authentication routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

// Authenticated Core routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/ganti-password', [PasswordController::class, 'edit'])->name('core.password.edit');
    Route::post('/ganti-password/periksa', [PasswordController::class, 'check'])->middleware('throttle:sensitive')->name('core.password.check');
    Route::put('/ganti-password', [PasswordController::class, 'update'])->middleware('throttle:sensitive')->name('core.password.update');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('core.profile.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->middleware('throttle:uploads')->name('core.profile.update');
    Route::get('/profil/avatar', [BrandMediaController::class, 'avatar'])->name('core.profile.avatar');

    Route::prefix('pengaturan')->name('core.')->group(function (): void {
        // User management
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // School profile management
        Route::get('/sekolah', [SchoolController::class, 'edit'])->name('school.edit');
        Route::put('/sekolah', [SchoolController::class, 'update'])->name('school.update');
        Route::post('/sekolah', [SchoolController::class, 'update'])->middleware('throttle:uploads')->name('school.upload');

        // School organization management
        Route::get('/struktur-organisasi', [SchoolOrganizationController::class, 'index'])->middleware('role:admin,operator')->name('organization.index');
        Route::get('/struktur-organisasi/{member}/file', [SchoolOrganizationController::class, 'adminPhoto'])->whereNumber('member')->middleware('role:admin,operator')->name('organization.admin-photo');
        Route::post('/struktur-organisasi', [SchoolOrganizationController::class, 'store'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('organization.store');
        Route::put('/struktur-organisasi/{member}', [SchoolOrganizationController::class, 'update'])->whereNumber('member')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('organization.update');
        Route::delete('/struktur-organisasi/{member}', [SchoolOrganizationController::class, 'destroy'])->whereNumber('member')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('organization.destroy');

        // Public portfolio gallery & video management
        Route::get('/galeri', [SchoolGalleryController::class, 'index'])->middleware('role:admin,operator')->name('gallery.index');
        Route::get('/galeri/{photo}/file', [SchoolGalleryController::class, 'adminPhoto'])->whereNumber('photo')->middleware('role:admin,operator')->name('gallery.admin-photo');
        Route::post('/galeri', [SchoolGalleryController::class, 'store'])->middleware(['role:admin,operator', 'throttle:uploads'])->name('gallery.store');
        Route::put('/galeri/{album}', [SchoolGalleryController::class, 'update'])->whereNumber('album')->middleware(['role:admin,operator', 'throttle:uploads'])->name('gallery.update');
        Route::delete('/galeri/{album}', [SchoolGalleryController::class, 'destroy'])->whereNumber('album')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('gallery.destroy');
        Route::post('/galeri/video', [SchoolGalleryController::class, 'storeVideo'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('gallery.video.store');
        Route::put('/galeri/video/{videoId}', [SchoolGalleryController::class, 'updateVideo'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('gallery.video.update');
        Route::delete('/galeri/video/{videoId}', [SchoolGalleryController::class, 'destroyVideo'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('gallery.video.destroy');

        // Public school news management
        Route::get('/berita', [SchoolNewsController::class, 'index'])->middleware('role:admin,operator')->name('news.index');
        Route::post('/berita', [SchoolNewsController::class, 'store'])->middleware(['role:admin,operator', 'throttle:uploads'])->name('news.store');
        Route::put('/berita/{news}', [SchoolNewsController::class, 'update'])->middleware(['role:admin,operator', 'throttle:uploads'])->name('news.update');
        Route::delete('/berita/{news}', [SchoolNewsController::class, 'destroy'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('news.destroy');
        Route::delete('/berita/{news}/foto/{photo}', [SchoolNewsController::class, 'destroyPhoto'])->whereNumber(['news', 'photo'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('news.photo.destroy');

        // Public downloads & Zona Integritas / IKM moderation
        Route::get('/pusat-unduh', [PublicPortalAdminController::class, 'downloadsIndex'])->middleware('role:admin,operator')->name('downloads.index');
        Route::post('/pusat-unduh', [PublicPortalAdminController::class, 'storeDownload'])->middleware(['role:admin,operator', 'throttle:uploads'])->name('downloads.store');
        Route::put('/pusat-unduh/{download}', [PublicPortalAdminController::class, 'updateDownload'])->whereNumber('download')->middleware(['role:admin,operator', 'throttle:uploads'])->name('downloads.update');
        Route::post('/pusat-unduh/{download}', [PublicPortalAdminController::class, 'updateDownload'])->whereNumber('download')->middleware(['role:admin,operator', 'throttle:uploads']);
        Route::post('/pusat-unduh/{download}/publikasi', [PublicPortalAdminController::class, 'toggleDownloadPublish'])->whereNumber('download')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('downloads.toggle');
        Route::delete('/pusat-unduh/{download}', [PublicPortalAdminController::class, 'destroyDownload'])->whereNumber('download')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('downloads.destroy');

        Route::get('/zona-integritas', [PublicPortalAdminController::class, 'integrityIndex'])->middleware('role:admin,operator')->name('integrity.index');
        Route::put('/zona-integritas/konten', [PublicPortalAdminController::class, 'updateIntegrityContent'])->middleware(['role:admin,operator', 'throttle:sensitive'])->name('integrity.content.update');
        Route::post('/zona-integritas/{survey}/moderasi', [PublicPortalAdminController::class, 'moderateIkm'])->whereNumber('survey')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('ikm.moderate');
        Route::delete('/zona-integritas/{survey}', [PublicPortalAdminController::class, 'destroyIkm'])->whereNumber('survey')->middleware(['role:admin,operator', 'throttle:sensitive'])->name('ikm.destroy');

        Route::get('/keamanan', [SecurityOperationsController::class, 'index'])->name('security.index');
        Route::post('/keamanan/ip', [SecurityOperationsController::class, 'block'])->middleware('throttle:sensitive')->name('security.block');
        Route::delete('/keamanan/ip/{blockedIp}', [SecurityOperationsController::class, 'unblock'])->middleware('throttle:sensitive')->name('security.unblock');
        Route::post('/keamanan/error/{error}/selesai', [SecurityOperationsController::class, 'resolveError'])->middleware('throttle:sensitive')->name('security.errors.resolve');
    });
});
