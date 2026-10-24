<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['school_id', 'role_id', 'name', 'username', 'email', 'phone', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if user has specific role(s).
     *
     * @param  string|list<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->role === null) {
            return false;
        }

        if (is_string($roles)) {
            return $this->role->name === $roles;
        }

        return in_array($this->role->name, $roles, true);
    }

    /**
     * Check if user has a specific permission via their assigned role.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === null) {
            return false;
        }

        // Administrator has blanket access to all permissions
        if ($this->role->name === 'admin') {
            return true;
        }

        return $this->role->permissions()->where('name', $permission)->exists();
    }
}
