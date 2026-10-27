<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['school_id', 'role_id', 'name', 'username', 'email', 'phone', 'avatar_path', 'is_active', 'password', 'must_change_password', 'password_changed_at'])]
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
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
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
     * Profil guru tambahan yang ditautkan ke akun ini.
     *
     * Akun dengan peran utama Operator/TU tetap bisa memiliki profil guru.
     * Dengan relasi ini, satu akun dapat menjalankan pekerjaan administrasi
     * sekaligus pekerjaan mengajar tanpa mengganti peran utamanya.
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    /**
     * Check if user has specific role(s).
     *
     * @param  string|list<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $requestedRoles = is_string($roles) ? [$roles] : $roles;
        $primaryRole = $this->role?->name;

        if ($primaryRole !== null && in_array($primaryRole, $requestedRoles, true)) {
            return true;
        }

        // Operator/TU dapat ditautkan ke profil guru aktif. Profil tambahan
        // ini memberi akses guru tanpa mengubah akses administrasi akun.
        return $primaryRole === 'operator'
            && in_array('teacher', $requestedRoles, true)
            && $this->school_id !== null
            && DB::table('teachers')
                ->where('user_id', $this->id)
                ->where('school_id', $this->school_id)
                ->where('is_active', true)
                ->exists();
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
