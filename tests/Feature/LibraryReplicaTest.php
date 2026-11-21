<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryReplicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_replica_page_is_public_and_serves_local_assets_only(): void
    {
        $response = $this->get('/perpustakaan/man4');

        $response->assertOk();
        $response->assertSee('E-Perpustakaan Digital', false);
        $response->assertSee('/madrasah-theme/perpustakaan/', false);
        $response->assertDontSee('man4banjar.com', false);
        $response->assertDontSee('fonts.googleapis.com/css2', false);
    }

    public function test_replica_ajax_stub_never_returns_data(): void
    {
        $this->getJson('/perpustakaan/man4/aksi?pg=statistik_kunjungan')
            ->assertOk()
            ->assertJsonPath('status', 'error')
            ->assertJsonMissingPath('kpi');
    }
}
