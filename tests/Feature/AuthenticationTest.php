<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMA Auth Test',
            'npsn' => '77778888',
            'level' => 'SMA',
        ]);

        $role = Role::create([
            'name' => RoleEnum::TEACHER->value,
            'label' => RoleEnum::TEACHER->label(),
            'is_system' => true,
        ]);

        $this->user = User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => 'Ahmad Guru',
            'username' => 'ahmad.guru',
            'email' => 'ahmad@school.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_user_can_authenticate_using_email(): void
    {
        $response = $this->post('/login', [
            'identity' => 'ahmad@school.test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($this->user);
        $response->assertRedirect('/');

        // Verify audit event
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.login',
            'actor_id' => $this->user->id,
        ]);
    }

    public function test_user_can_authenticate_using_username(): void
    {
        $response = $this->post('/login', [
            'identity' => 'ahmad.guru',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($this->user);
        $response->assertRedirect('/');
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'identity' => 'ahmad.guru',
            'password' => 'wrong_password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identity');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->post('/login', [
            'identity' => 'ahmad.guru',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identity');
    }

    public function test_login_rate_limiting_triggers_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'identity' => 'ahmad.guru',
                'password' => 'wrong_attempt',
            ]);
        }

        // 6th attempt should be blocked by rate limiter
        $response = $this->post('/login', [
            'identity' => 'ahmad.guru',
            'password' => 'password123', // even with correct password
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identity');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');

        // Verify logout audit event
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.logout',
            'actor_id' => $this->user->id,
        ]);
    }
}
