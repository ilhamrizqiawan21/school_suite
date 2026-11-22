<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_views_offer_recovery_without_rendering_exception_details(): void
    {
        foreach ([403, 404, 500, 503] as $status) {
            $html = view('errors.'.$status, [
                'exception' => new \RuntimeException('PRIVATE_INTERNAL_EXCEPTION'),
            ])->render();

            $this->assertStringContainsString('ERROR '.$status, $html);
            $this->assertStringContainsString('Kembali ke dashboard', $html);
            $this->assertStringContainsString('noindex, nofollow', $html);
            $this->assertStringNotContainsString('PRIVATE_INTERNAL_EXCEPTION', $html);
        }
    }

    public function test_unknown_web_page_returns_the_custom_not_found_view(): void
    {
        $this->get('/missing-blueprint-page')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke dashboard');
    }
}
