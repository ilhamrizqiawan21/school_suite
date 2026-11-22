<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEditableContentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::query()->create(['name' => 'Madrasah Ilmu dan Literasi', 'npsn' => '12340003', 'level' => 'MA']);
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $this->admin = User::query()->create(['school_id' => $this->school->id, 'role_id' => $role->id,
            'name' => 'Pengelola Konten', 'email' => 'pengelola@example.test', 'password' => 'test-password', 'is_active' => true]);
    }

    private function save(string $mode, array $blocks)
    {
        return $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name, 'npsn' => $this->school->npsn, 'level' => 'MA',
            'public_content_mode' => $mode, 'public_content_blocks' => $blocks,
            'public_content_expected_revision' => $this->school->fresh()->publicContentRegistry()['revision'],
        ]);
    }

    public function test_administrator_changes_public_service_copy_and_empty_text_without_editing_code(): void
    {
        $this->save('publish', ['copy.vue_services_ptsp' => ['text_001' => 'Layanan Mandiri Administrasi Madrasah']])->assertSessionHasNoErrors();
        $this->get('/layanan/ptsp')->assertOk()->assertInertia(fn ($page) => $page
            ->where('contentBlocks', fn ($blocks) => $blocks['copy.vue_services_ptsp']['text_001'] === 'Layanan Mandiri Administrasi Madrasah'));
        $this->save('publish', ['copy.vue_services_ptsp' => ['text_001' => '']])->assertSessionHasNoErrors();
        $this->get('/layanan/ptsp')->assertInertia(fn ($page) => $page
            ->where('contentBlocks', fn ($blocks) => $blocks['copy.vue_services_ptsp']['text_001'] === ''));
    }

    public function test_private_preview_works_on_services_and_does_not_leak_into_public_props(): void
    {
        $this->save('draft', ['copy.vue_services_ptsp' => ['text_001' => 'DRAF PRIVAT LAYANAN LITERASI']])->assertSessionHasNoErrors();
        $this->get('/layanan/ptsp?preview_draft=1')->assertOk()->assertSee('DRAF PRIVAT LAYANAN LITERASI')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/layanan/ptsp')->assertDontSee('DRAF PRIVAT LAYANAN LITERASI');
        $this->app['auth']->forgetGuards();
        $this->get('/layanan/ptsp?preview_draft=1')->assertForbidden();
    }

    public function test_public_payload_contains_only_relevant_page_copy_and_history_stores_overrides(): void
    {
        $this->save('publish', [
            'copy.vue_services_ptsp' => ['text_001' => 'Layanan PTSP Sekolah'],
            'copy.vue_alumni_home' => ['text_001' => 'KHUSUS HALAMAN ALUMNI'],
        ])->assertSessionHasNoErrors();
        $this->get('/layanan/ptsp')->assertDontSee('KHUSUS HALAMAN ALUMNI');
        $stored = data_get($this->school->fresh()->settings, 'public_content.published');
        $this->assertCount(2, $stored['copy.vue_services_ptsp']);
        $this->assertArrayNotHasKey('copy.blade_perpustakaan', $stored);
        $this->get('/pengaturan/sekolah')->assertInertia(fn ($page) => $page
            ->has('publicContentLabels')->has('publicMedia')
            ->where('publicContentRegistry', fn ($registry) => ! isset($registry['revisions'][0]['blocks'])));
    }

    public function test_unsafe_content_urls_and_non_boolean_visibility_are_rejected_without_publication(): void
    {
        $defaults = config('public_content.blocks')['copy.vue_services_ptsp'];
        $href = collect(array_keys($defaults))->first(fn ($key) => str_starts_with($key, 'href_'));
        $this->assertNotNull($href);
        foreach (['javascript:alert(1)', '//outside.example/path', '/../private/record.pdf'] as $url) {
            $this->save('publish', ['copy.vue_services_ptsp' => [$href => $url]])->assertSessionHasErrors('public_content_blocks');
        }
        $this->save('publish', ['home.hero' => ['visible' => 'false']])->assertSessionHasErrors('public_content_blocks');
        $this->assertSame(0, $this->school->fresh()->publicContentRegistry()['revision']);
    }

    public function test_home_section_visibility_matches_the_published_configuration(): void
    {
        config(['public_portal.theme' => 'madrasah']);
        $blocks = [];
        foreach (['programs', 'facilities', 'news', 'achievements', 'gallery', 'ppdb', 'contact'] as $section) {
            $blocks['home.'.$section] = ['visible' => false];
        }
        $this->save('publish', $blocks)->assertSessionHasNoErrors();
        $response = $this->get('/')->assertOk();
        foreach (['akademik', 'fasilitas', 'berita', 'prestasi', 'galeri', 'ppdb', 'kontak'] as $id) {
            $response->assertDontSee('<section id="'.$id.'"', false);
        }
    }

    public function test_structured_program_student_life_and_notice_blocks_are_published_to_their_public_pages(): void
    {
        $this->save('publish', [
            'school.programs' => [
                'items' => [['id' => 'science-club', 'title' => 'Klub Sains', 'description' => 'Riset terapan siswa.', 'image_url' => '/images/science.webp', 'link' => '/berita']],
            ],
            'school.student_life' => [
                'items' => [['id' => 'literacy', 'title' => 'Komunitas Literasi', 'description' => 'Pembinaan membaca dan menulis.', 'image_url' => '', 'link' => '/galeri']],
            ],
            'home.notice' => [
                'visible' => true, 'title' => 'Jadwal layanan', 'message' => 'Layanan tatap muka dibuka kembali.',
                'starts_at' => '', 'ends_at' => '', 'link_url' => '/kontak', 'link_label' => 'Lihat kontak',
            ],
        ])->assertSessionHasNoErrors();

        $this->get('/akademik-fasilitas')->assertInertia(fn ($page) => $page
            ->where('contentBlocks', fn ($blocks) => $blocks['school.programs']['items'][0]['title'] === 'Klub Sains'));
        $this->get('/kesiswaan-prestasi')->assertInertia(fn ($page) => $page
            ->where('contentBlocks', fn ($blocks) => $blocks['school.student_life']['items'][0]['title'] === 'Komunitas Literasi'));
        $this->get('/')->assertOk()->assertSee('Jadwal layanan');

        $this->save('publish', ['home.notice' => ['link_url' => 'javascript:alert(1)']])
            ->assertSessionHasErrors('public_content_blocks');
    }

    public function test_hidden_demo_fallbacks_do_not_publish_fictional_news_or_gallery(): void
    {
        config(['public_portal.theme' => 'madrasah']);
        $this->save('publish', ['shell.options' => ['hide_demo_fallbacks' => true]])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()
            ->assertDontSee('Olimpiade Bahasa Arab (OBA) ke-9 Tingkat Nasional')
            ->assertDontSee('data-image="/madrasah-theme/images/robotics.webp"', false);
    }

    public function test_page_metadata_is_versioned_and_private_draft_metadata_is_not_public(): void
    {
        config(['public_portal.theme' => 'madrasah']);
        $this->save('publish', ['seo.vue_school_home' => ['title' => 'Portal Resmi Literasi',
            'description' => 'Informasi resmi layanan dan pembelajaran.', 'canonical_url' => 'https://madrasah.example/']])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('<title>Portal Resmi Literasi</title>', false)
            ->assertSee('name="description" content="Informasi resmi layanan dan pembelajaran."', false)
            ->assertSee('rel="canonical" href="https://madrasah.example/"', false);
        $this->save('draft', ['seo.vue_school_home' => ['title' => 'METADATA DRAF PRIVAT']])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('METADATA DRAF PRIVAT');
        $this->get('/?preview_draft=1')->assertSee('METADATA DRAF PRIVAT')
            ->assertSee('name="robots" content="noindex, nofollow"', false)->assertDontSee('rel="canonical"', false);
        $this->save('publish', ['seo.vue_services_ptsp' => ['description' => 'Layanan administrasi terpadu']])->assertSessionHasNoErrors();
        $this->get('/layanan/ptsp')->assertInertia(fn ($page) => $page
            ->where('contentBlocks', fn ($blocks) => $blocks['seo.current']['description'] === 'Layanan administrasi terpadu'
                && ! isset($blocks['seo.vue_school_home'])));
    }

    public function test_blade_home_uses_managed_text_and_the_real_school_identity(): void
    {
        config(['public_portal.theme' => 'madrasah']);
        $this->save('publish', ['copy.blade_home' => ['text_001' => 'Status Akreditasi Resmi Sekolah']])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('Status Akreditasi Resmi Sekolah')->assertSee($this->school->name);
        $this->get('/perpustakaan')->assertOk()->assertSee($this->school->name)->assertDontSee('MADRASAH ALIYAH NEGERI 4 BANJAR');
    }
}
