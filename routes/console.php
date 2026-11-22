<?php

use App\Modules\Academic\Application\Services\GraduationService;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Modules\Core\Infrastructure\Models\SchoolNewsPhoto;
use App\Modules\Core\Infrastructure\Models\SchoolOrganizationMember;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

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

Artisan::command('school:publish-public-content', function (AuditLogger $audit): void {
    $published = 0;
    foreach (School::query()->select('id')->cursor() as $candidate) {
        $published += DB::transaction(function () use ($candidate, $audit): int {
            $school = School::query()->whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $registry = $school->publicContentRegistry();
            $scheduled = $registry['scheduled'];
            if ($scheduled === null || CarbonImmutable::parse($scheduled['publish_at'])->isFuture()) {
                return 0;
            }
            $revisions = $registry['revisions'];
            $revisions[] = ['version' => max([0, ...array_column($revisions, 'version')]) + 1,
                'action' => 'scheduled_publish', 'published_at' => now()->toIso8601String(),
                'actor' => $scheduled['actor'], 'blocks' => $scheduled['blocks']];
            $settings = $school->settings ?? [];
            $settings['public_content'] = [...$registry, 'published' => $scheduled['blocks'],
                'revision' => $registry['revision'] + 1, 'revisions' => array_slice($revisions, -15),
                'status' => 'published', 'scheduled' => null];
            $school->update(['settings' => $settings]);
            $audit->log(eventType: 'core.public_content.scheduled_publish', targetType: 'School', targetId: (string) $school->id,
                newValues: ['revision' => $registry['revision'] + 1], schoolId: (int) $school->id);

            return 1;
        });
    }
    $this->info($published.' jadwal konten diterbitkan.');
})->purpose('Publish approved public content snapshots when their scheduled time is due');

Schedule::command('school:publish-public-content')->everyMinute()->withoutOverlapping();

Artisan::command('school:media-orphans {school : School ID} {--delete : Delete after interactive confirmation}', function (AuditLogger $audit): int {
    $schoolId = filter_var($this->argument('school'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($schoolId === false || ! School::query()->whereKey($schoolId)->exists()) {
        $this->error('Sekolah tidak ditemukan.');

        return 1;
    }
    if ($this->option('delete') && ! $this->confirm('Hapus berkas media berusia lebih dari 24 jam yang tidak memiliki referensi? Pastikan cadangan tersedia.')) {
        return 1;
    }

    return DB::transaction(function () use ($schoolId, $audit): int {
        $school = School::query()->lockForUpdate()->findOrFail($schoolId);
        $referenced = array_fill_keys(array_filter([
            $school->logo_path, $school->favicon_path,
            ...SchoolGalleryPhoto::query()->where('school_id', $schoolId)->pluck('path')->all(),
            ...SchoolNewsPhoto::query()->whereHas('news', fn ($query) => $query->where('school_id', $schoolId))->pluck('path')->all(),
            ...SchoolOrganizationMember::query()->where('school_id', $schoolId)->pluck('photo_path')->all(),
        ]), true);
        $settings = json_encode($school->settings, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $disk = Storage::disk('local');
        $count = 0;
        foreach (['branding', 'school-gallery', 'school-news', 'school-organization'] as $directory) {
            foreach ($disk->allFiles($directory.'/'.$schoolId) as $path) {
                if (isset($referenced[$path]) || str_contains($settings, $path) || $disk->lastModified($path) > now()->subDay()->timestamp) {
                    continue;
                }
                if ($this->option('delete') && ! $disk->delete($path)) {
                    $this->error('Pembersihan berhenti karena berkas gagal dihapus.');

                    return 1;
                }
                $this->line($path);
                $count++;
            }
        }
        if ($this->option('delete') && $count > 0) {
            $audit->log(eventType: 'core.media.orphans_deleted', targetType: 'School', targetId: (string) $schoolId,
                newValues: ['count' => $count], schoolId: $schoolId);
        }
        $this->info($count.' berkas '.($this->option('delete') ? 'dibersihkan.' : 'tanpa referensi ditemukan; tidak ada berkas dihapus.'));

        return 0;
    });
})->purpose('Preview unreferenced public media; deletion requires explicit interactive confirmation');
