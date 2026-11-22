<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Library\Infrastructure\Models\LibraryCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryReplicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_replica_page_is_public_and_serves_local_assets_only(): void
    {
        $response = $this->get('/perpustakaan');

        $response->assertOk();
        $response->assertSee('E-Perpustakaan Digital', false);
        $response->assertSee('/madrasah-theme/perpustakaan/', false);
        $response->assertDontSee('/perpustakaan/man4', false);
        $response->assertDontSee('man4banjar.com', false);
        $response->assertDontSee('fonts.googleapis.com/css2', false);

        $this->get('/perpustakaan/man4')
            ->assertRedirect('/perpustakaan');
    }

    public function test_replica_endpoints_use_live_library_service_without_pii_leak(): void
    {
        School::query()->create([
            'name' => 'MAN 4 Banjar',
            'npsn' => '30315219',
            'level' => 'MA',
        ]);

        $this->getJson('/perpustakaan/aksi?pg=statistik_kunjungan')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'kpi' => ['collections_count', 'visits_today', 'active_loans']])
            ->assertJsonMissingPath('visitors');

        $this->postJson('/perpustakaan/aksi?pg=buku_tamu', [
            'visitor_name' => 'Tamu Publik',
            'visitor_role' => 'guest',
            'purpose' => 'Membaca',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('library_visits', [
            'visitor_name' => 'Tamu Publik',
            'visitor_role' => 'guest',
        ]);

        $this->postJson('/perpustakaan/aksi?pg=usulkan_buku', [
            'title' => 'Buku Baru',
            'reason' => 'Referensi',
        ])->assertStatus(401);
    }

    public function test_catalog_does_not_advertise_missing_private_ebook_as_downloadable(): void
    {
        $school = School::query()->create([
            'name' => 'MAN 4 Banjar',
            'npsn' => '30315219',
            'level' => 'MA',
        ]);

        LibraryCollection::query()->create([
            'school_id' => $school->id,
            'code' => 'EBOOK-MISSING-001',
            'title' => 'E-Book yang Berkasnya Hilang',
            'category' => 'ebook',
            'format' => 'ebook',
            'total_copies' => 0,
            'available_copies' => 0,
            'ebook_document_id' => 999999,
            'access_level' => 'public',
            'is_active' => true,
        ]);

        $this->get('/perpustakaan/portal')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('collections.0.has_ebook_file', false)
                ->where('collections.0.ebook_url', null)
                ->where('sourceStatus.source_of_truth', 'school_suite_local_catalog')
                ->where('sourceStatus.fallback_active', true)
                ->where('sourceStatus.missing_local_files', 1)
            );
    }
}
