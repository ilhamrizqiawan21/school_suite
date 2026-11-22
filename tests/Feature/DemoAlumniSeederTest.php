<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Academic\Application\Services\AlumniService;
use App\Modules\Academic\Infrastructure\Models\AlumniProfile;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use Database\Seeders\DemoAlumniSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAlumniSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_alumni_are_varied_repeatable_and_preserve_existing_edits(): void
    {
        $school = School::query()->create(['name' => 'Sekolah Pilot', 'npsn' => '20109988', 'level' => 'SMP']);
        $other = School::query()->create(['name' => 'Sekolah Lain', 'npsn' => '99887766']);
        AlumniProfile::query()->create(['school_id' => $other->id, 'full_name' => 'Existing Alumni',
            'graduation_year' => 2020, 'current_status' => 'working']);

        $this->seed(DemoAlumniSeeder::class);

        $profiles = AlumniProfile::query()->where('school_id', $school->id)->get();
        $this->assertCount(180, $profiles);
        $this->assertCount(180, $profiles->pluck('full_name')->unique());
        $this->assertCount(10, $profiles->pluck('current_status')->unique());
        $this->assertGreaterThan(10, $profiles->pluck('graduation_year')->unique()->count());
        $this->assertSame(['IX A', 'IX B', 'IX C', 'IX D'], $profiles->pluck('major_or_class')->unique()->sort()->values()->all());
        $this->assertTrue($profiles->every(fn ($profile) => $profile->moderation_status === 'approved' && $profile->opt_in_directory && ! $profile->opt_in_contact));
        $this->assertDatabaseCount('school_news', 8);
        $this->assertSame(4, SchoolNews::query()->where('category', 'karir-alumni')->count());
        $this->assertSame(4, SchoolNews::query()->where('category', 'beasiswa-alumni')->count());
        $statistics = app(AlumniService::class)->publicStatistics((int) $school->id);
        $this->assertSame(180, array_sum(array_column($statistics['statuses'], 'total')));
        $this->assertCount(5, $statistics['campuses']);
        $this->assertSame(3, $statistics['campuses'][0]['total']);
        $this->assertStringContainsString('Demo', $statistics['campuses'][0]['name']);

        $profile = $profiles->first();
        $profile->update(['full_name' => 'Nama Hasil Suntingan', 'opt_in_directory' => false]);
        $news = SchoolNews::query()->firstOrFail();
        $news->update(['title' => 'Judul Hasil Suntingan', 'status' => 'draft']);
        $this->seed(DemoAlumniSeeder::class);

        $this->assertDatabaseCount('alumni_profiles', 181);
        $this->assertDatabaseCount('school_news', 8);
        $this->assertDatabaseHas('alumni_profiles', ['id' => $profile->id, 'full_name' => 'Nama Hasil Suntingan', 'opt_in_directory' => false]);
        $this->assertDatabaseHas('school_news', ['id' => $news->id, 'title' => 'Judul Hasil Suntingan', 'status' => 'draft']);
        $this->assertSame(1, AlumniProfile::query()->where('school_id', $other->id)->count());
    }

    public function test_it_skips_missing_pilot_school_and_production_environments(): void
    {
        $this->seed(DemoAlumniSeeder::class);
        $this->assertDatabaseCount('alumni_profiles', 0);
        School::query()->create(['name' => 'Sekolah Pilot', 'npsn' => '20109988', 'level' => 'SMP']);
        $this->app->instance('env', 'production');
        $this->app->make(DemoAlumniSeeder::class)->run();
        $this->assertDatabaseCount('alumni_profiles', 0);
        $this->assertDatabaseCount('school_news', 0);
    }
}
