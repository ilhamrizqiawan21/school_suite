<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::create(['name' => 'Sekolah Branding', 'npsn' => '99887766', 'level' => 'SMP']);
        $role = Role::create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $this->admin = User::create(['school_id' => $this->school->id, 'role_id' => $role->id, 'name' => 'Admin', 'email' => 'admin@brand.test', 'password' => 'Password123', 'is_active' => true]);
    }

    public function test_admin_can_upload_branding_and_choose_theme(): void
    {
        Storage::fake('local');
        $response = $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name, 'npsn' => $this->school->npsn, 'level' => 'SMP',
            'theme_primary' => '#277450', 'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
        ]);
        $response->assertRedirect('/pengaturan/sekolah');
        $this->school->refresh();
        $this->assertSame('#277450', $this->school->settings['theme_primary']);
        Storage::disk('local')->assertExists($this->school->logo_path);
        Storage::disk('local')->assertExists($this->school->favicon_path);
    }

    public function test_partial_school_update_preserves_existing_public_theme_copy(): void
    {
        $this->school->update([
            'settings' => [
                'letterhead' => [
                    'parent_institution' => 'Yayasan Pendidikan',
                    'city' => 'Bandung',
                    'postal_code' => '40123',
                ],
                'public_theme' => [
                    'hero_title' => 'Belajar dengan Berani',
                    'hero_accent' => 'Tumbuh bersama',
                    'hero_primary_label' => 'Lihat program',
                    'hero_secondary_label' => 'Hubungi kami',
                ],
            ],
        ]);

        $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name,
            'npsn' => $this->school->npsn,
            'level' => 'SMP',
            'theme_primary' => '#277450',
        ])->assertRedirect('/pengaturan/sekolah');

        $theme = $this->school->fresh()->settings['public_theme'];
        $letterhead = $this->school->fresh()->settings['letterhead'];
        $this->assertSame('Yayasan Pendidikan', $letterhead['parent_institution']);
        $this->assertSame('Bandung', $letterhead['city']);
        $this->assertSame('40123', $letterhead['postal_code']);
        $this->assertSame('Belajar dengan Berani', $theme['hero_title']);
        $this->assertSame('Tumbuh bersama', $theme['hero_accent']);
        $this->assertSame('Lihat program', $theme['hero_primary_label']);
        $this->assertSame('Hubungi kami', $theme['hero_secondary_label']);
    }

    public function test_user_can_update_own_profile_and_avatar(): void
    {
        Storage::fake('local');
        $response = $this->actingAs($this->admin)->post('/profil', [
            'name' => 'Admin Baru', 'email' => 'baru@brand.test', 'phone' => '08123456789',
            'avatar' => UploadedFile::fake()->image('avatar.webp', 200, 200),
        ]);
        $response->assertRedirect();
        $this->admin->refresh();
        $this->assertSame('Admin Baru', $this->admin->name);
        Storage::disk('local')->assertExists($this->admin->avatar_path);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'core.profile.updated', 'actor_id' => $this->admin->id]);
    }

    public function test_invalid_school_details_are_returned_as_field_errors_without_changing_profile(): void
    {
        $this->actingAs($this->admin)->from('/pengaturan/sekolah')->post('/pengaturan/sekolah', [
            'name' => 'Nama tidak boleh tersimpan', 'npsn' => $this->school->npsn, 'level' => 'SMP',
            'website' => 'bukan-url', 'phone' => str_repeat('1', 31), 'school_status' => 'Tidak dikenal',
        ])->assertRedirect('/pengaturan/sekolah')->assertSessionHasErrors(['website', 'phone', 'school_status']);

        $this->assertSame('Sekolah Branding', $this->school->fresh()->name);
    }

    public function test_favicon_must_be_square_and_failed_upload_keeps_current_branding(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('branding/old.png', 'existing');
        $this->school->update(['favicon_path' => 'branding/old.png']);

        $this->actingAs($this->admin)->from('/pengaturan/sekolah')->post('/pengaturan/sekolah', [
            'name' => $this->school->name, 'npsn' => $this->school->npsn, 'level' => 'SMP',
            'favicon' => UploadedFile::fake()->image('favicon.png', 100, 50),
        ])->assertRedirect('/pengaturan/sekolah')->assertSessionHasErrors('favicon');

        $this->assertSame('branding/old.png', $this->school->fresh()->favicon_path);
        Storage::disk('local')->assertExists('branding/old.png');
    }

    public function test_duplicate_email_does_not_partially_update_own_profile(): void
    {
        User::create(['school_id' => $this->school->id, 'role_id' => $this->admin->role_id,
            'name' => 'Other Admin', 'email' => 'taken@brand.test', 'password' => 'Password123', 'is_active' => true]);

        $this->actingAs($this->admin)->from('/profil')->post('/profil', [
            'name' => 'Should not persist', 'email' => 'taken@brand.test',
        ])->assertRedirect('/profil')->assertSessionHasErrors('email');

        $this->assertSame('Admin', $this->admin->fresh()->name);
    }

    public function test_current_password_can_be_checked_without_changing_it(): void
    {
        $this->actingAs($this->admin)->postJson('/ganti-password/periksa', ['current_password' => 'wrong'])
            ->assertOk()->assertJson(['valid' => false]);
        $this->actingAs($this->admin)->postJson('/ganti-password/periksa', ['current_password' => 'Password123'])
            ->assertOk()->assertJson(['valid' => true]);
        $this->assertTrue(Hash::check('Password123', $this->admin->fresh()->password));
    }

    public function test_password_change_rejects_incorrect_current_password_and_weak_password(): void
    {
        $this->actingAs($this->admin)->from('/ganti-password')->put('/ganti-password', [
            'current_password' => 'wrong', 'password' => 'weak', 'password_confirmation' => 'weak',
        ])->assertSessionHasErrors(['current_password', 'password']);
        $this->assertTrue(Hash::check('Password123', $this->admin->fresh()->password));
    }

    public function test_password_change_invalidates_other_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'other-device-session', 'user_id' => $this->admin->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => 'payload', 'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->admin)->put('/ganti-password', [
            'current_password' => 'Password123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertTrue(Hash::check('PasswordBaru123', $this->admin->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'core.password.changed', 'actor_id' => $this->admin->id]);
    }

    public function test_admin_can_configure_global_service_hours_social_channels_reset_theme_and_manage_public_content_blocks(): void
    {
        // 1. Save draft content blocks + global channels + custom theme color
        $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name,
            'npsn' => $this->school->npsn,
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'service_hours' => 'Senin – Jumat, 07.30 – 16.00 WITA',
            'holiday_notice' => 'Tutup pada hari libur nasional',
            'emergency_contact' => '0811-2233-4455',
            'map_url' => 'https://maps.example.test/lokasi',
            'whatsapp_url' => 'https://wa.me/6281122334455',
            'social_facebook' => 'https://facebook.com/madrasah.contoh',
            'social_instagram' => 'https://instagram.com/madrasah.contoh',
            'theme_primary' => '#277450',
            'public_theme_preset' => 'institutional',
            'public_content_mode' => 'draft',
            'public_content_expected_revision' => 0,
            'public_content_blocks' => [
                'home.hero' => [
                    'badge_primary' => 'Draf Badge Baru <script>alert(1)</script>',
                    'headline_highlight' => 'Draf Riset Unggulan',
                ],
            ],
        ])->assertRedirect('/pengaturan/sekolah')->assertSessionHasNoErrors();

        $this->school->refresh();
        $this->assertSame('Asia/Makassar', $this->school->timezone);
        $this->assertSame('Senin – Jumat, 07.30 – 16.00 WITA', data_get($this->school->settings, 'service_hours'));
        $this->assertSame('https://facebook.com/madrasah.contoh', data_get($this->school->settings, 'social_links.facebook'));

        $registryAfterDraft = $this->school->publicContentRegistry();
        // Script tag is stripped; draft is updated while published remains unchanged
        $this->assertSame('Draf Badge Baru alert(1)', $registryAfterDraft['draft']['home.hero']['badge_primary']);
        $this->assertSame('Madrasah Aliyah Negeri Riset & Adiwiyata Mandiri', $registryAfterDraft['published']['home.hero']['badge_primary']);
        $this->assertCount(0, $registryAfterDraft['revisions']);

        // 2. Publish content blocks -> creates revision v1 and updates published
        $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name,
            'npsn' => $this->school->npsn,
            'level' => 'MA',
            'public_content_mode' => 'publish',
            'public_content_expected_revision' => 1,
            'public_content_blocks' => [
                'home.hero' => [
                    'badge_primary' => 'Madrasah Riset Terpublikasi v1',
                    'headline_highlight' => 'Sains & Tahfidz v1',
                ],
            ],
        ])->assertRedirect('/pengaturan/sekolah');

        $this->school->refresh();
        $registryAfterPublish1 = $this->school->publicContentRegistry();
        $this->assertSame('Madrasah Riset Terpublikasi v1', $registryAfterPublish1['published']['home.hero']['badge_primary']);
        $this->assertCount(1, $registryAfterPublish1['revisions']);

        // 3. Publish v2 then restore v1
        $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name,
            'npsn' => $this->school->npsn,
            'level' => 'MA',
            'public_content_mode' => 'publish',
            'public_content_expected_revision' => 2,
            'public_content_blocks' => [
                'home.hero' => [
                    'badge_primary' => 'Madrasah Riset Terpublikasi v2',
                ],
            ],
        ])->assertRedirect('/pengaturan/sekolah');

        $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name,
            'npsn' => $this->school->npsn,
            'level' => 'MA',
            'public_content_mode' => 'restore',
            'public_content_expected_revision' => 3,
            'public_content_restore_version' => 1,
        ])->assertRedirect('/pengaturan/sekolah');

        $this->school->refresh();
        $this->assertSame('Madrasah Riset Terpublikasi v1', $this->school->publicContentRegistry()['published']['home.hero']['badge_primary']);

        // 4. Reset theme defaults returns primary color to #3157a4 and preset to editorial
        $this->actingAs($this->admin)->post('/pengaturan/sekolah', [
            'name' => $this->school->name,
            'npsn' => $this->school->npsn,
            'level' => 'MA',
            'reset_theme_defaults' => true,
        ])->assertRedirect('/pengaturan/sekolah');

        $this->school->refresh();
        $this->assertSame('#3157a4', data_get($this->school->settings, 'theme_primary'));
        $this->assertSame('editorial', data_get($this->school->settings, 'public_theme.preset'));
    }
}
