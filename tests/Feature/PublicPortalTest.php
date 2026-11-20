<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\PublicDownload;
use App\Modules\Core\Infrastructure\Models\PublicIkmSurvey;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->school = School::query()->create([
            'name' => 'MAN Contoh Portal',
            'npsn' => '30109901',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'settings' => [
                'service_hours' => 'Senin–Jumat 07.30–15.00 WITA',
                'map_url' => 'https://maps.example.test/man-contoh',
            ],
        ]);

        $this->admin = User::query()->create([
            'school_id' => $this->school->id,
            'role_id' => $roles[RoleEnum::ADMIN->value]->id,
            'name' => 'Admin Portal',
            'username' => 'admin_portal',
            'email' => 'admin.portal@example.test',
            'password' => 'password123456',
            'is_active' => true,
        ]);
    }

    public function test_home_renders_shortcuts_prayer_fallback_and_anonymous_visit_counter(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/School/Home')
                ->has('shortcuts', 4)
                ->where('visitStats.today', 1)
                ->where('visitStats.total', 1)
                ->where('prayerSchedule.timezone', 'Asia/Makassar')
                ->where('prayerSchedule.is_fallback', true)
            );

        $this->get('/')->assertOk();
        $this->assertDatabaseHas('public_visit_stats', [
            'school_id' => $this->school->id,
            'hits' => 2,
        ]);
    }

    public function test_pilot_home_uses_man4_snapshot_only_in_local_environment(): void
    {
        $this->school->update(['npsn' => '20109988']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/Home')
            ->where('publicPortalTheme', null));

        $this->app->instance('env', 'local');

        $this->get('/')
            ->assertOk()
            ->assertViewIs('public.madrasah.home')
            ->assertSee('Olimpiade Bahasa Arab')
            ->assertViewHas('news', []);

        $this->get('/e-layanan')->assertInertia(fn (Assert $page) => $page
            ->component('Public/Services/Index')
            ->where('publicPortalTheme', 'madrasah'));
    }

    public function test_configured_madrasah_home_uses_local_theme_and_supports_inertia_navigation(): void
    {
        config(['public_portal.theme' => 'madrasah']);

        $this->school->update([
            'name' => 'Madrasah Contoh Nusantara',
            'npsn' => '30990001',
        ]);
        $article = SchoolNews::query()->create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Pameran Riset Madrasah',
            'slug' => 'pameran-riset-madrasah',
            'category' => 'prestasi',
            'excerpt' => 'Pameran hasil riset siswa.',
            'body' => 'Pameran menampilkan hasil riset siswa madrasah.',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->withHeader('X-Inertia', 'true')->get('/')
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('public.home'));

        $this->flushHeaders()->get('/')
            ->assertOk()
            ->assertViewIs('public.madrasah.home')
            ->assertSee('href="/layanan/ptsp"', false)
            ->assertSee('href="/ppdb"', false)
            ->assertSee($article->title)
            ->assertSee(route('core.news.public.show', $article->slug), false)
            ->assertSee('/madrasah-theme/home.css')
            ->assertDontSee('ajax_traffic.php')
            ->assertViewHas('visits', fn (array $visits): bool => $visits['today'] === 1);
    }

    public function test_e_layanan_catalog_and_public_gtk_directory_expose_only_public_fields(): void
    {
        Teacher::query()->create([
            'school_id' => $this->school->id,
            'nip' => '198012312005011001',
            'name' => 'Ustadz Ahmad Contoh',
            'is_active' => true,
        ]);
        Teacher::query()->create([
            'school_id' => $this->school->id,
            'nip' => '198012312005011002',
            'name' => 'Guru Nonaktif',
            'is_active' => false,
        ]);

        $this->get('/e-layanan?q=PTSP')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Services/Index')
                ->where('initialQuery', 'PTSP')
            );

        $this->get('/direktori-gtk')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Directory/Teachers')
                ->has('teachers', 1)
                ->where('teachers.0.name', 'Ustadz Ahmad Contoh')
                ->missing('teachers.0.nip')
            );
    }

    public function test_draft_news_never_leaks_and_published_achievements_are_visible(): void
    {
        SchoolNews::query()->create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Juara 1 KSM Provinsi',
            'slug' => 'juara-1-ksm-provinsi',
            'category' => 'prestasi',
            'excerpt' => 'Prestasi tingkat provinsi.',
            'body' => 'Konten prestasi.',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        SchoolNews::query()->create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Draf Internal Rahasia',
            'slug' => 'draf-internal-rahasia',
            'category' => 'prestasi',
            'body' => 'Belum terbit.',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->get('/prestasi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/School/Achievements')
                ->has('items', 1)
                ->where('items.0.slug', 'juara-1-ksm-provinsi')
            );

        $this->get('/berita/draf-internal-rahasia')->assertNotFound();
    }

    public function test_pusat_unduh_serves_published_only_and_blocks_unpublished_documents(): void
    {
        $pdf = UploadedFile::fake()->create('kalender-2026.pdf', 120, 'application/pdf');

        $this->actingAs($this->admin)
            ->post('/pengaturan/pusat-unduh', [
                'category' => 'kalender',
                'title' => 'Kalender Akademik 2026/2027',
                'version' => 1,
                'is_published' => true,
                'file' => $pdf,
            ])
            ->assertRedirect();

        /** @var PublicDownload $published */
        $published = PublicDownload::query()->firstOrFail();

        $unpublishedPdf = UploadedFile::fake()->create('internal.pdf', 40, 'application/pdf');
        $this->actingAs($this->admin)
            ->post('/pengaturan/pusat-unduh', [
                'category' => 'sop',
                'title' => 'SOP Internal Draf',
                'version' => 1,
                'is_published' => false,
                'file' => $unpublishedPdf,
            ])
            ->assertRedirect();

        /** @var PublicDownload $unpublished */
        $unpublished = PublicDownload::query()->where('title', 'SOP Internal Draf')->firstOrFail();

        auth()->logout();

        $this->get('/pusat-unduh')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Downloads/Index')
                ->has('downloads', 1)
                ->where('downloads.0.id', $published->id)
            );

        $this->get("/pusat-unduh/{$published->id}/unduh")->assertOk();
        $this->assertSame(1, $published->fresh()->download_count);

        $this->get("/pusat-unduh/{$unpublished->id}/unduh")->assertNotFound();
    }

    public function test_ikm_survey_requires_admin_moderation_before_public_aggregation(): void
    {
        $this->post('/zona-integritas/ikm', [
            'respondent_name' => 'Wali Murid Contoh',
            'respondent_role' => 'wali',
            'service_name' => 'PTSP & Tata Usaha',
            'rating' => 5,
            'comment' => 'Pelayanan sangat cepat dan jelas.',
        ])->assertRedirect();

        /** @var PublicIkmSurvey $survey */
        $survey = PublicIkmSurvey::query()->firstOrFail();
        $this->assertFalse($survey->is_approved);

        // Before moderation, public summary must remain 0
        $this->get('/zona-integritas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Integrity/Index')
                ->where('ikm.total_responses', 0)
            );

        $this->actingAs($this->admin)
            ->post("/pengaturan/zona-integritas/{$survey->id}/moderasi", ['approved' => true])
            ->assertRedirect();

        auth()->logout();

        $this->get('/zona-integritas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Integrity/Index')
                ->where('ikm.total_responses', 1)
                ->where('ikm.average_rating', 5)
            );

        $this->get('/jadwal-salat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Prayer/Index')
                ->where('prayerSchedule.timezone', 'Asia/Makassar')
            );

        $this->get('/kontak')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/School/Contact')
                ->where('serviceHours', 'Senin–Jumat 07.30–15.00 WITA')
            );
    }
}
