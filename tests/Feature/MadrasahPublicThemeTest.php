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

        $this->school = School::query()->create([
            'name' => 'MA Contoh Unggul Nusantara',
            'npsn' => '30208801',
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
}
