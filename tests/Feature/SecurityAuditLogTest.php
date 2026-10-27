<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use App\Modules\Core\Infrastructure\Models\BlockedIp;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SecurityAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_safe_audit_fields_from_their_school(): void
    {
        $school = $this->createSchool('Sekolah Audit', '99112233');
        $otherSchool = $this->createSchool('Sekolah Lain', '99112234');
        $admin = $this->createUser($school, 'admin', 'Admin Audit', 'admin@audit.test');
        $otherActor = $this->createUser($otherSchool, 'admin', 'Admin Lain', 'other@audit.test');

        AuditEvent::create([
            'school_id' => $school->id,
            'actor_id' => $admin->id,
            'event_type' => 'core.user.updated',
            'target_type' => 'User',
            'target_id' => '42',
            'old_values' => ['password' => 'old-password-marker'],
            'new_values' => ['api_secret' => 'secret-marker'],
            'ip_address' => '203.0.113.12',
            'user_agent' => 'private-user-agent-marker',
            'created_at' => now(),
        ]);
        AuditEvent::create([
            'school_id' => $otherSchool->id,
            'actor_id' => $otherActor->id,
            'event_type' => 'core.user.deleted',
            'target_type' => 'User',
            'target_id' => '99',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/pengaturan/keamanan');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Core/Security/Index')
            ->where('can_manage', true)
            ->has('audit_events', 1)
            ->where('audit_events.0.event_type', 'core.user.updated')
            ->where('audit_events.0.actor_name', 'Admin Audit')
            ->where('audit_events.0.target_type', 'User')
            ->where('audit_events.0.target_id', '42')
            ->missing('audit_events.0.old_values')
            ->missing('audit_events.0.new_values')
            ->missing('audit_events.0.ip_address')
            ->missing('audit_events.0.user_agent')
        );

        $response->assertDontSee('old-password-marker')
            ->assertDontSee('secret-marker')
            ->assertDontSee('private-user-agent-marker')
            ->assertDontSee('core.user.deleted');
    }

    public function test_principal_keeps_security_view_without_receiving_audit_events(): void
    {
        $school = $this->createSchool('Sekolah Kepala', '88112233');
        $principal = $this->createUser($school, 'principal', 'Kepala Sekolah', 'principal@audit.test');

        AuditEvent::create([
            'school_id' => $school->id,
            'actor_id' => $principal->id,
            'event_type' => 'core.school.updated',
            'target_type' => 'School',
            'target_id' => (string) $school->id,
            'created_at' => now(),
        ]);

        $this->actingAs($principal)->get('/pengaturan/keamanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Security/Index')
                ->where('can_manage', false)
                ->has('audit_events', 0)
            );
    }

    public function test_admin_cannot_block_current_ip_and_receives_a_form_error(): void
    {
        $school = $this->createSchool('Sekolah IP', '99110001');
        $admin = $this->createUser($school, 'admin', 'Admin', 'ip@audit.test');
        $this->actingAs($admin)->from('/pengaturan/keamanan')->post('/pengaturan/keamanan/ip', [
            'ip_address' => '127.0.0.1', 'reason' => 'Percobaan blokir sendiri',
        ])->assertRedirect('/pengaturan/keamanan')->assertSessionHasErrors('ip_address');
        $this->assertDatabaseCount('security_blocked_ips', 0);
    }

    public function test_admin_can_block_again_after_own_school_block_is_disabled(): void
    {
        $school = $this->createSchool('Sekolah IP', '99110002');
        $admin = $this->createUser($school, 'admin', 'Admin', 'reblock@audit.test');
        $item = BlockedIp::create(['school_id' => $school->id, 'ip_address' => '203.0.113.10', 'reason' => 'Lama', 'blocked_by' => $admin->id, 'is_active' => false]);
        $this->actingAs($admin)->from('/pengaturan/keamanan')->post('/pengaturan/keamanan/ip', [
            'ip_address' => $item->ip_address, 'reason' => 'Percobaan ulang',
        ])->assertRedirect('/pengaturan/keamanan')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('security_blocked_ips', 1);
        $this->assertTrue($item->fresh()->is_active);
    }

    public function test_security_audit_history_is_paginated(): void
    {
        $school = $this->createSchool('Sekolah Halaman', '99110003');
        $admin = $this->createUser($school, 'admin', 'Admin', 'pages@audit.test');
        for ($i = 0; $i < 17; $i++) {
            AuditEvent::create(['school_id' => $school->id, 'actor_id' => $admin->id, 'event_type' => 'core.user.updated', 'created_at' => now()]);
        }
        $this->actingAs($admin)->get('/pengaturan/keamanan')->assertOk()->assertInertia(fn (Assert $page) => $page->has('audit_events', 15)->where('pagination.audit.total', 17)->where('pagination.audit.last', 2));
    }

    private function createSchool(string $name, string $npsn): School
    {
        return School::create(['name' => $name, 'npsn' => $npsn, 'level' => 'SMP']);
    }

    private function createUser(School $school, string $roleName, string $name, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName), 'is_system' => true]);

        return User::create([
            'school_id' => $school->id,
            'role_id' => $role->id,
            'name' => $name,
            'email' => $email,
            'password' => 'Password123',
            'is_active' => true,
        ]);
    }
}
