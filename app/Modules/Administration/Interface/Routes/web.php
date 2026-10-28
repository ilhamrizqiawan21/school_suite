<?php

declare(strict_types=1);

use App\Modules\Administration\Interface\Http\Controllers\CommunicationController;
use App\Modules\Administration\Interface\Http\Controllers\EngagementController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/staff_tasks.php';
require __DIR__.'/facilities.php';
require __DIR__.'/correspondence.php';

Route::middleware(['auth', 'role:admin,operator,principal,teacher,homeroom,student', 'throttle:sensitive'])->prefix('komunikasi')->name('communication.')->group(function () {
    Route::get('/', [CommunicationController::class, 'index'])->name('index');
    Route::post('/pengumuman', [CommunicationController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::delete('/pengumuman/{announcement}', [CommunicationController::class, 'destroyAnnouncement'])->name('announcements.destroy');
    Route::get('/pengumuman/{announcement}/lampiran', [CommunicationController::class, 'downloadAttachment'])->name('announcements.attachment');
    Route::post('/notifikasi/{notification}/baca', [CommunicationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [CommunicationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/kalender', [CommunicationController::class, 'storeEvent'])->name('calendar.store');
    Route::delete('/kalender/{event}', [CommunicationController::class, 'destroyEvent'])->name('calendar.destroy');
});

Route::middleware(['auth', 'throttle:sensitive'])->prefix('komunikasi')->name('communication.')->group(function () {
    Route::get('/chat', [EngagementController::class, 'chat'])->middleware('role:teacher,homeroom,student')->name('chat.index');
    Route::post('/chat/ruang', [EngagementController::class, 'createRoom'])->middleware('role:teacher,homeroom')->name('chat.rooms.store');
    Route::post('/chat/ruang/{room}/pesan', [EngagementController::class, 'sendMessage'])->middleware('role:teacher,homeroom,student')->name('chat.messages.store');
    Route::post('/chat/pesan/{message}/moderasi', [EngagementController::class, 'moderate'])->middleware('role:teacher,homeroom')->name('chat.messages.moderate');
});

// Keep the teacher-facing namespace aligned with lms_sekolah while preserving legacy /komunikasi URLs.
Route::middleware(['auth', 'throttle:sensitive', 'role:teacher,homeroom'])->prefix('guru')->name('guru.communication.')->group(function () {
    Route::get('/pengumuman', [CommunicationController::class, 'index'])->name('index');
    Route::post('/pengumuman', [CommunicationController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::delete('/pengumuman/{announcement}', [CommunicationController::class, 'destroyAnnouncement'])->name('announcements.destroy');
    Route::get('/pengumuman/{announcement}/lampiran', [CommunicationController::class, 'downloadAttachment'])->name('announcements.attachment');
    Route::post('/notifikasi/{notification}/baca', [CommunicationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [CommunicationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/kalender', [CommunicationController::class, 'storeEvent'])->name('calendar.store');
    Route::delete('/kalender/{event}', [CommunicationController::class, 'destroyEvent'])->name('calendar.destroy');
    Route::get('/chat', [EngagementController::class, 'chat'])->name('chat.index');
    Route::post('/chat/ruang', [EngagementController::class, 'createRoom'])->name('chat.rooms.store');
    Route::post('/chat/ruang/{room}/pesan', [EngagementController::class, 'sendMessage'])->name('chat.messages.store');
    Route::post('/chat/pesan/{message}/moderasi', [EngagementController::class, 'moderate'])->name('chat.messages.moderate');
});

Route::middleware(['auth', 'throttle:sensitive', 'role:principal'])->prefix('kepsek')->name('principal.communication.')->group(function () {
    Route::get('/pengumuman', [CommunicationController::class, 'index'])->name('announcements.index');
    Route::get('/kalender', [CommunicationController::class, 'calendar'])->name('calendar.index');
});
