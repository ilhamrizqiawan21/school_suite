<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicContentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::query()->create(['name' => 'Sekolah Konten', 'npsn' => '99881122', 'level' => 'SMP']);
        $role = Role::query()->create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $this->actingAs(User::query()->create(['school_id' => $this->school->id, 'role_id' => $role->id,
            'name' => 'Pengelola', 'email' => 'content-admin@example.test', 'password' => 'Password123', 'is_active' => true]));
    }

    private function save(array $content = []): TestResponse
    {
        return $this->post('/pengaturan/sekolah', [
            'name' => $this->school->name, 'npsn' => $this->school->npsn, 'level' => 'SMP',
            'public_content_expected_revision' => $this->school->fresh()->publicContentRegistry()['revision'], ...$content,
        ]);
    }

    public function test_saving_profile_preserves_unpublished_draft_and_live_content(): void
    {
        $original = $this->school->publicContentRegistry()['published'];
        $this->save(['public_content_mode' => 'draft', 'public_content_blocks' => [
            'home.hero' => ['badge_primary' => 'Belum disetujui'],
        ]])->assertSessionHasNoErrors();
        $draft = $this->school->fresh()->publicContentRegistry()['draft'];

        $this->save(['phone' => '0215559999'])->assertSessionHasNoErrors();
        $registry = $this->school->fresh()->publicContentRegistry();
        $this->assertSame($original, $registry['published']);
        $this->assertSame($draft, $registry['draft']);
        $this->assertCount(0, $registry['revisions']);
        $this->assertSame('0215559999', $this->school->fresh()->phone);
    }

    public function test_content_without_explicit_publish_action_is_only_saved_as_draft(): void
    {
        $original = $this->school->publicContentRegistry()['published'];
        $this->save(['public_content_blocks' => ['home.hero' => ['badge_primary' => 'Draf tanpa aksi terbit']]])->assertSessionHasNoErrors();
        $registry = $this->school->fresh()->publicContentRegistry();
        $this->assertSame($original, $registry['published']);
        $this->assertSame('Draf tanpa aksi terbit', $registry['draft']['home.hero']['badge_primary']);
    }

    public function test_revision_numbers_remain_unique_after_retention_and_restore_is_a_new_revision(): void
    {
        for ($version = 1; $version <= 18; $version++) {
            $this->save(['public_content_mode' => 'publish', 'public_content_blocks' => [
                'home.hero' => ['badge_primary' => 'Publikasi '.$version],
            ]])->assertSessionHasNoErrors();
        }
        $registry = $this->school->fresh()->publicContentRegistry();
        $this->assertSame(range(4, 18), array_column($registry['revisions'], 'version'));
        $this->save(['public_content_mode' => 'restore', 'public_content_restore_version' => 4])->assertSessionHasNoErrors();
        $registry = $this->school->fresh()->publicContentRegistry();
        $this->assertSame('Publikasi 4', $registry['published']['home.hero']['badge_primary']);
        $this->assertSame(19, $registry['revisions'][14]['version']);
        $this->assertSame('restore', $registry['revisions'][14]['action']);
        $this->assertSame(4, $registry['revisions'][14]['restored_from_version']);
    }

    public function test_invalid_restore_does_not_publish_draft_or_change_profile(): void
    {
        $this->save(['public_content_mode' => 'draft', 'public_content_blocks' => [
            'home.hero' => ['badge_primary' => 'Draf privat'],
        ]])->assertSessionHasNoErrors();
        $before = $this->school->fresh()->settings;
        $this->save(['public_content_mode' => 'restore', 'public_content_restore_version' => 999,
            'phone' => '0215559999'])->assertSessionHasErrors('public_content_restore_version');
        $this->assertSame($before, $this->school->fresh()->settings);
        $this->assertNull($this->school->fresh()->phone);
    }

    public function test_stale_content_revision_is_rejected_without_overwriting_another_editor(): void
    {
        $this->save(['public_content_mode' => 'draft', 'public_content_expected_revision' => 0,
            'public_content_blocks' => ['home.hero' => ['badge_primary' => 'Suntingan pertama']]])->assertSessionHasNoErrors();
        $before = $this->school->fresh()->settings;
        $this->save(['public_content_mode' => 'publish', 'public_content_expected_revision' => 0,
            'public_content_blocks' => ['home.hero' => ['badge_primary' => 'Suntingan kedaluwarsa']]])
            ->assertSessionHasErrors('public_content_blocks');
        $this->assertSame($before, $this->school->fresh()->settings);
    }

    public function test_content_actions_require_an_editor_revision_token(): void
    {
        $this->save(['public_content_mode' => 'publish', 'public_content_expected_revision' => null,
            'public_content_blocks' => ['home.hero' => ['badge_primary' => 'Tidak boleh terbit']]])
            ->assertSessionHasErrors('public_content_expected_revision');
        $this->assertCount(0, $this->school->fresh()->publicContentRegistry()['revisions']);
    }

    public function test_ambiguous_legacy_revision_cannot_restore_the_wrong_content(): void
    {
        $blocks = $this->school->publicContentRegistry()['published'];
        $this->school->update(['settings' => ['public_content' => [
            'published' => $blocks, 'draft' => $blocks,
            'revisions' => [['version' => 16, 'blocks' => $blocks], ['version' => 16, 'blocks' => $blocks]],
        ]]]);
        $before = $this->school->fresh()->settings;
        $this->save(['public_content_mode' => 'restore', 'public_content_restore_version' => 16])
            ->assertSessionHasErrors('public_content_restore_version');
        $this->assertSame($before, $this->school->fresh()->settings);
    }

    public function test_private_draft_preview_is_visible_only_to_authorized_managers_with_no_store_headers(): void
    {
        config(['public_portal.theme' => 'madrasah']);

        $this->save([
            'public_content_mode' => 'draft',
            'public_content_blocks' => [
                'home.hero' => ['badge_primary' => 'Draf Privat Hanya Admin'],
                'profile.values' => ['title' => 'Nilai Draf Privat'],
            ],
        ])->assertSessionHasNoErrors();

        // Authorized admin with ?preview_draft=1 sees draft content with private no-store headers
        $previewResponse = $this->get('/?preview_draft=1');
        $previewResponse->assertOk()
            ->assertSee('Draf Privat Hanya Admin')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', (string) $previewResponse->headers->get('Cache-Control'));

        // Public page without ?preview_draft=1 still serves published content
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Draf Privat Hanya Admin');

        // Guest requesting ?preview_draft=1 is strictly forbidden (403)
        auth()->logout();
        $this->get('/?preview_draft=1')->assertForbidden();
        $this->get('/profil-sekolah?preview_draft=1')->assertForbidden();
    }

    public function test_section_visibility_and_hide_demo_fallbacks_work_on_public_pages(): void
    {
        config(['public_portal.theme' => 'madrasah']);

        $this->save([
            'public_content_mode' => 'publish',
            'public_content_blocks' => [
                'profile.pillars' => ['visible' => false],
                'shell.options' => ['hide_demo_fallbacks' => true],
            ],
        ])->assertSessionHasNoErrors();

        $this->get('/profil-sekolah')
            ->assertOk()
            ->assertSee('5 Nilai Budaya Kerja Kementerian Agama')
            ->assertDontSee('4 Pilar Keunggulan Sekolah Konten');

        $this->get('/berita')
            ->assertOk()
            ->assertDontSee('Tim Bahasa Arab Madrasah Raih Medali Emas');

        $this->get('/galeri/video')
            ->assertOk()
            ->assertSee('Semua Video (0)');
    }
}
