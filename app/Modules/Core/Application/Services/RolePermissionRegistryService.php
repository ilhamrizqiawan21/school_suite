<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionRegistryService
{
    /**
     * Synchronize all canonical system roles and permissions idempotently.
     *
     * @return array<string, Role>
     */
    public function syncSystemRolesAndPermissions(): array
    {
        return DB::transaction(function (): array {
            $roles = [];
            foreach (RoleEnum::cases() as $roleEnum) {
                $roles[$roleEnum->value] = Role::query()->updateOrCreate(
                    ['name' => $roleEnum->value],
                    ['label' => $roleEnum->label(), 'is_system' => true],
                );
            }

            $permissions = [];
            foreach (PermissionEnum::cases() as $permissionEnum) {
                $permissions[$permissionEnum->value] = Permission::query()->updateOrCreate(
                    ['name' => $permissionEnum->value],
                    [
                        'label' => Str::of($permissionEnum->value)->replace(['.', '_'], ' ')->title()->toString(),
                        'module' => Str::before($permissionEnum->value, '.'),
                    ],
                );
            }

            foreach (RoleEnum::cases() as $roleEnum) {
                if ($roleEnum === RoleEnum::ADMIN) {
                    continue;
                }

                $permissionIds = array_map(
                    static fn (PermissionEnum $perm): int => (int) $permissions[$perm->value]->id,
                    PermissionEnum::defaultForRole($roleEnum),
                );

                if ($permissionIds !== []) {
                    $roles[$roleEnum->value]->permissions()->syncWithoutDetaching($permissionIds);
                }
            }

            return $roles;
        });
    }
}
