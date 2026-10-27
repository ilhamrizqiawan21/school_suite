<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Application\Services\UserService;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of users in the current school.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $currentUser = $request->user();
        $schoolId = $currentUser?->school_id;

        $query = User::with('role:id,name,label')
            ->where('school_id', $schoolId);

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($role = $filters['role'] ?? null) {
            $query->whereHas('role', function ($q) use ($role): void {
                $q->where('name', $role);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $users = $query->select(['id', 'name', 'username', 'email', 'phone', 'is_active', 'role_id', 'school_id'])
            ->orderBy('name')->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id, 'name' => $user->name, 'username' => $user->username,
                'email' => $user->email, 'phone' => $user->phone, 'is_active' => $user->is_active,
                'role' => $user->role,
                'can_update' => Gate::allows('update', $user),
                'can_delete' => Gate::allows('delete', $user),
            ]);

        $roleQuery = Role::query()->orderBy('id');
        if (! $currentUser?->hasRole('admin')) {
            $roleQuery->whereNotIn('name', ['admin', 'operator', 'principal']);
        }
        $roles = $roleQuery->get(['id', 'name', 'label']);

        return Inertia::render('Core/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filter_roles' => Role::query()->orderBy('label')->get(['id', 'name', 'label']),
            'current_user_id' => $currentUser->id,
            'can_create' => Gate::allows('create', User::class),
            'can_update' => $currentUser?->hasRole('admin') || $currentUser?->hasPermission(PermissionEnum::USERS_UPDATE->value),
            'can_delete' => $currentUser?->hasRole('admin') || $currentUser?->hasPermission(PermissionEnum::USERS_DELETE->value),
            'can_manage_roles' => $currentUser?->hasRole('admin') ?? false,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:50', 'unique:users,username'],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->userService->createUser($validated, $request->user());

        return redirect()->route('core.users.index')->with('message', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'role_id' => ['sometimes', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [Rule::prohibitedIf((int) $request->user()->id === (int) $user->id), 'nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->userService->updateUser($user, $validated, $request->user());

        return redirect()->route('core.users.index')->with('message', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->userService->deleteUser($user, $request->user());

        return redirect()->route('core.users.index')->with('message', 'Pengguna berhasil dihapus.');
    }
}
