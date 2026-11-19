<?php

use App\Modules\Academic\Application\Services\GraduationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Pemeliharaan & Cadangan Mandiri Sekolah (SS-F-009, S08-T04)
Schedule::command('school:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup_cron.log'));

Schedule::command('queue:restart')
    ->dailyAt('04:00');

Artisan::command('school:release-graduations', function (GraduationService $graduation): void {
    $graduation->releaseDueGraduations();
})->purpose('Apply due graduation decisions to student and enrollment status');

Schedule::command('school:release-graduations')->everyMinute()->withoutOverlapping();
