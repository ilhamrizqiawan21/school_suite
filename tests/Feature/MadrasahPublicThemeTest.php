<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Core\Infrastructure\Models\PublicVisitStat;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MadrasahPublicThemeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        config(['public_portal.theme' => 'madrasah']);

        $this->school = School::query()->create([
            'name' => 'MA Contoh Unggul Nusantara',
            'npsn' => '30990001',
            'level' => 'MA',
            'accreditation' => 'A (Unggul)',
            'address' => 'Jl. Pendidikan No. 10, Contoh',
            'phone' => '(0511) 555-0100',
            'email' => 'info@contoh-madrasah.sch.id',
            'principal_name' => 'Drs. H. Ahmad Hidayat, M.Pd.',
            'timezone' => 'Asia/Makassar',
        ]);

        PublicVisitStat::query()->create([
            'school_id' => $this->school->id,
            'visit_date' => now('Asia/Makassar')->toDateString(),
            'hits' => 42,
        ]);
    }

    public function test_madrasah_theme_renders_duplicated_public_pages_with_dummy_school_data(): void
    {
        foreach (['/', '/profil-sekolah', '/direktori-gtk', '/berita', '/prestasi', '/galeri'] as $uri) {
            $response = $this->get($uri);
            $response->assertOk();
            $response->assertDontSee('MAN 4 BANJAR');
            $response->assertDontSee('man4banjar.com');
        }

        $this->get('/profil-sekolah')
            ->assertSee('MA Contoh Unggul Nusantara')
            ->assertSee('43')
            ->assertSee('5 Nilai Budaya Kerja Kementerian Agama');

        $this->get('/direktori-gtk')
            ->assertSee('Direktori Guru &amp; Tenaga Kependidikan', false)
            ->assertSee('Drs. H. Ahmad Hidayat, M.Pd.');

        $this->get('/berita')
            ->assertSee('Warta &amp; Kabar Madrasah', false);

        $this->get('/prestasi')
            ->assertSee('Prestasi &amp; Tradisi Juara', false);

        $this->get('/galeri')
            ->assertSee('Galeri &amp; Kegiatan Madrasah', false);
    }

    public function test_blade_catalogues_and_inertia_services_share_the_same_public_navigation(): void
    {
        foreach (['/', '/berita', '/akademik-fasilitas', '/layanan/ptsp', '/lacak'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('data-madrasah-navigation', false)
                ->assertSee('href="/layanan/buku-tamu"', false)
                ->assertSee('href="/galeri/video"', false);
        }

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('data-madrasah-navigation', false);
    }

    public function test_inertia_navigation_keeps_madrasah_templates_and_query_parameters(): void
    {
        foreach (['/profil-sekolah', '/direktori-gtk', '/berita', '/prestasi', '/galeri', '/galeri/video'] as $path) {
            $uri = $path.'?q=contoh';

            $this->withHeader('X-Inertia', 'true')->get($uri)
                ->assertStatus(409)
                ->assertHeader('X-Inertia-Location', url($uri));
        }
    }

    public function test_video_gallery_serves_local_demo_media_without_source_school_links(): void
    {
        $this->get('/galeri/video')
            ->assertOk()
            ->assertSee('Video Contoh')
            ->assertSee('Koleksi Video &amp; Dokumentasi', false)
            ->assertSee('Semua Video (6)')
            ->assertSee('/madrasah-theme/images/videos/tur-kampus.webp')
            ->assertSee('/madrasah-theme/images/videos/tur-kampus.webm')
            ->assertDontSee('youtube.com')
            ->assertDontSee('MAN 4 BANJAR');

        $this->get('/galeri/video?kategori=Prestasi')
            ->assertOk()
            ->assertSee('Praktikum Sains')
            ->assertDontSee('Orientasi Siswa Baru');

        $this->assertFileExists(public_path('madrasah-theme/images/videos/tur-kampus.webm'));
    }
}
