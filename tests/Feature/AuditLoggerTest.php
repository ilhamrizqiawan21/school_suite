<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Application\Services\UserService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\AuditEvent;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private AuditLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMA Audit Test',
            'npsn' => '99998888',
            'level' => 'SMA',
        ]);

        $adminRole = Role::create([
            'name' => RoleEnum::ADMIN->value,
            'label' => RoleEnum::ADMIN->label(),
            'is_system' => true,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Admin Audit',
            'email' => 'admin.audit@school.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $this->logger = new AuditLogger;
    }

    /**
     * Test sensitive keys are strictly redacted in audit logs.
     */
    public function test_audit_logger_redacts_sensitive_keys_automatically(): void
    {
        $payload = [
            'username' => 'guru.budi',
            'password' => 'super_secret_plain_text',
            'remember_token' => 'token_xyz_123',
            'nested' => [
                'api_key' => 'secret_api_key_abc',
                'description' => 'Aman dan tidak rahasia',
            ],
        ];

        $event = $this->logger->log(
            eventType: 'test.sensitive.event',
            targetType: 'User',
            targetId: '100',
            newValues: $payload,
            actor: $this->admin,
            schoolId: $this->school->id
        );

        $this->assertDatabaseHas('audit_events', [
            'id' => $event->id,
            'event_type' => 'test.sensitive.event',
        ]);

        $recorded = AuditEvent::find($event->id);
        $this->assertNotNull($recorded);

        $newValues = $recorded->new_values;

        // Sensitive fields MUST be redacted
        $this->assertSame('[REDACTED]', $newValues['password']);
        $this->assertSame('[REDACTED]', $newValues['remember_token']);
        $this->assertSame('[REDACTED]', $newValues['nested']['api_key']);

        // Non-sensitive fields remain intact
        $this->assertSame('guru.budi', $newValues['username']);
        $this->assertSame('Aman dan tidak rahasia', $newValues['nested']['description']);
    }

    /**
     * Test user service triggers audit events upon user creation, update, and deletion.
     */
    public function test_user_service_triggers_audit_events_automatically(): void
    {
        $service = new UserService($this->logger);

        // 1. Create User
        $user = $service->createUser([
            'name' => 'Guru Penjas',
            'username' => 'guru.penjas',
            'email' => 'penjas@school.test',
            'password' => 'password123',
        ], $this->admin);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'core.user.created',
            'target_type' => 'User',
            'target_id' => (string) $user->id,
            'actor_id' => $this->admin->id,
            'school_id' => $this->school->id,
        ]);

        $createdEvent = AuditEvent::where('event_type', 'core.user.created')->first();
        $this->assertNotNull($createdEvent);
        $this->assertSame('[REDACTED]', $createdEvent->new_values['password']);
        $this->assertSame('Guru Penjas', $createdEvent->new_values['name']);

        // 2. Update User
        $service->updateUser($user, [
            'name' => 'Guru Penjasorkes Senior',
        ], $this->admin);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'core.user.updated',
            'target_type' => 'User',
            'target_id' => (string) $user->id,
        ]);

        $updatedEvent = AuditEvent::where('event_type', 'core.user.updated')->first();
        $this->assertNotNull($updatedEvent);
        $this->assertSame('Guru Penjas', $updatedEvent->old_values['name']);
        $this->assertSame('Guru Penjasorkes Senior', $updatedEvent->new_values['name']);

        // 3. Delete User
        $service->deleteUser($user, $this->admin);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'core.user.deleted',
            'target_type' => 'User',
            'target_id' => (string) $user->id,
        ]);

        $deletedEvent = AuditEvent::where('event_type', 'core.user.deleted')->first();
        $this->assertNotNull($deletedEvent);
        $this->assertSame('Guru Penjasorkes Senior', $deletedEvent->old_values['name']);
    }
}
