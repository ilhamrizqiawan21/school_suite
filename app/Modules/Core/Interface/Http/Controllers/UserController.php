<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Application\Services\UserService;
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

        $currentUser = $request->user();
        $schoolId = $currentUser?->school_id;

        $query = User::with('role:id,name,label')
            ->where('school_id', $schoolId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('role', function ($q) use ($role): void {
                $q->where('name', $role);
            });
        }

        $users = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('id')->get(['id', 'name', 'label']);

        return Inertia::render('Core/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role']),
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
            'password' => ['nullable', 'string', 'min:8'],
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
