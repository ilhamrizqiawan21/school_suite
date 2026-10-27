<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Administration\Infrastructure\Models\AcademicCalendarEvent;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;

class DemoCommunicationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $school = School::query()->where('npsn', '20109988')->first();
        $author = $school ? User::query()->where('school_id', $school->id)->where('email', 'admin@pilot.sch.id')->first() : null;
        if (! $author) {
            return;
        }

        Announcement::query()->firstOrCreate(
            ['school_id' => $school->id, 'author_id' => $author->id, 'title' => '[DEMO] Informasi kegiatan sekolah'],
            [
                'body' => 'Ini adalah contoh pengumuman. Periksa agenda sekolah untuk melihat jadwal kegiatan yang akan datang.',
                'published_at' => now()->subDay(),
                'expires_at' => now()->addDays(30),
            ]
        );

        AcademicCalendarEvent::query()->firstOrCreate(
            ['school_id' => $school->id, 'created_by' => $author->id, 'title' => '[DEMO] Pertemuan warga sekolah'],
            [
                'description' => 'Contoh agenda untuk memperlihatkan tanggal, jenis kegiatan, dan sasaran.',
                'event_type' => 'meeting',
                'starts_at' => now()->addDays(7)->setTime(9, 0),
                'ends_at' => now()->addDays(7)->setTime(10, 0),
            ]
        );
    }
}
