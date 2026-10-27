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

        $response->assertRedirect('/');
        $this->assertTrue(Hash::check('PasswordBaru123', $this->admin->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'core.password.changed', 'actor_id' => $this->admin->id]);
    }
}
