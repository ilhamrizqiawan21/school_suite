<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Shared\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserService
{
    public function __construct(
        protected ?AuditLogger $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? new AuditLogger;
    }

    /**
     * Create a new user scoped to the actor's school.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function createUser(array $data, User $actor): User
    {
        if (Gate::forUser($actor)->denies('create', User::class)) {
            throw new AuthorizationException('Actor is not authorized to create users.');
        }

        $schoolId = $actor->school_id ?? $data['school_id'] ?? null;

        if ($schoolId === null) {
            throw new InvalidArgumentException('User must be associated with a valid school.');
        }

        $this->assertCanAssignRole(isset($data['role_id']) ? (int) $data['role_id'] : null, $actor);

        $user = User::create([
            'school_id' => $schoolId,
            'role_id' => $data['role_id'] ?? null,
            'name' => (string) $data['name'],
            'username' => isset($data['username']) ? (string) $data['username'] : null,
            'email' => (string) $data['email'],
            'phone' => isset($data['phone']) ? (string) $data['phone'] : null,
            'password' => Hash::make((string) $data['password']),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'must_change_password' => true,
        ]);

        $this->auditLogger->log(
            eventType: 'core.user.created',
            targetType: 'User',
            targetId: (string) $user->id,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'is_active' => $user->is_active,
                'password' => '[UNMODIFIED_PLAINTEXT_ATTEMPT]',
            ],
            actor: $actor,
            schoolId: (int) $schoolId
        );

        return $user;
    }

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function updateUser(User $target, array $data, User $actor): User
    {
        if (Gate::forUser($actor)->denies('update', $target)) {
            throw new AuthorizationException('Actor is not authorized to update this user.');
        }

        if (! empty($data['password']) && (int) $actor->id === (int) $target->id) {
            throw new AuthorizationException('Use the password-change flow to change your own password.');
        }

        if (array_key_exists('role_id', $data)) {
            $this->assertCanAssignRole($data['role_id'] !== null ? (int) $data['role_id'] : null, $actor, $target);
        }

        $fillable = ['name', 'username', 'email', 'phone', 'role_id', 'is_active'];
        $updates = [];
        $oldValues = [];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $oldValues[$field] = $target->{$field};
                $updates[$field] = $data[$field];
            }
        }

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make((string) $data['password']);
            $updates['must_change_password'] = true;
            $updates['password_changed_at'] = null;
            $oldValues['password'] = '[ENCRYPTED_PREVIOUS]';
        }

        $target->update($updates);

        $this->auditLogger->log(
            eventType: 'core.user.updated',
            targetType: 'User',
            targetId: (string) $target->id,
            newValues: $updates,
            oldValues: $oldValues,
            actor: $actor,
            schoolId: $target->school_id !== null ? (int) $target->school_id : null
        );

        return $target;
    }

    /**
     * Delete an existing user.
     *
     * @throws AuthorizationException
     */
    public function deleteUser(User $target, User $actor): bool
    {
        if (Gate::forUser($actor)->denies('delete', $target)) {
            throw new AuthorizationException('Actor is not authorized to delete this user.');
        }

        $targetSnapshot = [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $target->role_id,
        ];
        $targetId = (string) $target->id;
        $schoolId = $target->school_id !== null ? (int) $target->school_id : null;

        $deleted = (bool) $target->delete();

        if ($deleted) {
            $this->auditLogger->log(
                eventType: 'core.user.deleted',
                targetType: 'User',
                targetId: $targetId,
                oldValues: $targetSnapshot,
                actor: $actor,
                schoolId: $schoolId
            );
        }

        return $deleted;
    }

    private function assertCanAssignRole(?int $roleId, User $actor, ?User $target = null): void
    {
        if ($roleId === null) {
            return;
        }

        $roleName = Role::query()->whereKey($roleId)->value('name');

        if ($actor->hasRole('admin')) {
            return;
        }

        if (in_array($roleName, ['admin', 'operator', 'principal'], true)) {
            throw new AuthorizationException('Only a school administrator can assign a privileged role.');
        }

        if ($target !== null && (int) $target->role_id !== $roleId) {
            throw new AuthorizationException('Only a school administrator can change a user role.');
        }
    }
}
