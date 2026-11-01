<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SchoolInstallCommand extends Command
{
    protected $signature = 'school:install
                            {--name= : Nama sekolah}
                            {--npsn= : NPSN sekolah}
                            {--level=SMA : Jenjang sekolah}
                            {--admin-name= : Nama administrator pertama}
                            {--admin-email= : Email administrator pertama}
                            {--admin-username= : Nama pengguna administrator pertama}';

    protected $description = 'Siapkan satu instalasi School Suite untuk sebuah sekolah';

    public function handle(): int
    {
        if (School::query()->exists()) {
            $this->error('Instalasi ini sudah memiliki sekolah. Command school:install hanya dapat dijalankan sekali.');

            return self::FAILURE;
        }

        $adminEmail = Str::lower(trim((string) ($this->option('admin-email') ?: $this->ask('Email Admin pertama'))));
        $data = [
            'name' => trim((string) ($this->option('name') ?: $this->ask('Nama sekolah'))),
            'npsn' => trim((string) ($this->option('npsn') ?: $this->ask('NPSN sekolah'))),
            'level' => Str::upper(trim((string) ($this->option('level') ?: 'SMA'))),
            'admin_name' => trim((string) ($this->option('admin-name') ?: $this->ask('Nama Admin pertama'))),
            'admin_email' => $adminEmail,
            'admin_username' => Str::lower(trim((string) ($this->option('admin-username') ?: Str::before($adminEmail, '@')))),
        ];

        $password = $this->resolveAdminPassword();
        if ($password === null) {
            $this->error('Password Admin wajib diberikan melalui prompt interaktif atau environment SCHOOL_INSTALL_ADMIN_PASSWORD.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            [...$data, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'npsn' => ['required', 'string', 'max:20', 'unique:schools,npsn'],
                'level' => ['required', 'in:SD,SMP,SMA,SMK,MI,MTs,MA'],
                'admin_name' => ['required', 'string', 'max:255'],
                'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'admin_username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = DB::transaction(function () use ($data, $password): User {
            $roles = $this->ensureSystemRoles();
            $this->ensureSystemPermissions($roles);

            $school = School::query()->create([
                'name' => $data['name'],
                'npsn' => $data['npsn'],
                'level' => $data['level'],
            ]);

            return User::query()->create([
                'school_id' => $school->id,
                'role_id' => $roles[RoleEnum::ADMIN->value]->id,
                'name' => $data['admin_name'],
                'username' => $data['admin_username'],
                'email' => $data['admin_email'],
                'password' => Hash::make($password),
                'is_active' => true,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]);
        });

        $this->info('Instalasi School Suite selesai.');
        $this->line('Akun Admin: '.$admin->email);
        $this->line('Silakan lanjutkan konfigurasi sekolah dari menu Pengaturan.');

        return self::SUCCESS;
    }

    private function resolveAdminPassword(): ?string
    {
        $configured = getenv('SCHOOL_INSTALL_ADMIN_PASSWORD');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        if (! $this->input->isInteractive()) {
            return null;
        }

        $password = $this->secret('Password Admin pertama');
        $confirmation = $this->secret('Ulangi password Admin pertama');

        if ($password !== $confirmation) {
            $this->error('Konfirmasi password tidak cocok.');

            return null;
        }

        return is_string($password) ? $password : null;
    }

    /**
     * @return array<string, Role>
     */
    private function ensureSystemRoles(): array
    {
        $roles = [];

        foreach (RoleEnum::cases() as $role) {
            $roles[$role->value] = Role::query()->updateOrCreate(
                ['name' => $role->value],
                ['label' => $role->label(), 'is_system' => true],
            );
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     */
    private function ensureSystemPermissions(array $roles): void
    {
        $permissions = [];

        foreach (PermissionEnum::cases() as $permission) {
            $permissions[$permission->value] = Permission::query()->updateOrCreate(
                ['name' => $permission->value],
                [
                    'label' => Str::of($permission->value)->replace(['.', '_'], ' ')->title()->toString(),
                    'module' => Str::before($permission->value, '.'),
                ],
            );
        }

        $roles[RoleEnum::OPERATOR->value]->permissions()->syncWithoutDetaching([
            $permissions[PermissionEnum::USERS_VIEW->value]->id,
            $permissions[PermissionEnum::USERS_CREATE->value]->id,
            $permissions[PermissionEnum::SCHOOL_VIEW->value]->id,
            $permissions[PermissionEnum::SCHOOL_UPDATE->value]->id,
        ]);

        $roles[RoleEnum::PRINCIPAL->value]->permissions()->syncWithoutDetaching([
            $permissions[PermissionEnum::USERS_VIEW->value]->id,
            $permissions[PermissionEnum::SCHOOL_VIEW->value]->id,
        ]);
    }
}
