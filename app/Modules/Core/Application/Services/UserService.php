<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserService
{
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

        return User::create([
            'school_id' => $schoolId,
            'role_id' => $data['role_id'] ?? null,
            'name' => (string) $data['name'],
            'username' => isset($data['username']) ? (string) $data['username'] : null,
            'email' => (string) $data['email'],
            'phone' => isset($data['phone']) ? (string) $data['phone'] : null,
            'password' => Hash::make((string) $data['password']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
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

        $fillable = ['name', 'username', 'email', 'phone', 'role_id', 'is_active'];
        $updates = [];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make((string) $data['password']);
        }

        $target->update($updates);

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

        return (bool) $target->delete();
    }
}
