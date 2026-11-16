<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /**
     * Display paginated user listing with search, filtering, and sorting.
     */
    public function index(Request $request): View
    {
        $users = $this->userService->getUsers($request->all());
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show form to create new user.
     */
    public function create(): View
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store newly created user.
     */
    public function store(CreateUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->userService->createUser($validated, Auth::user());

        return redirect()->route('admin.users')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * View detailed user record.
     */
    public function show(User $user): View
    {
        $user->load(['roles.permissions', 'customerProfile', 'addresses', 'orders.items', 'activityLogs' => fn ($q) => $q->latest()->take(20)]);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show form to edit existing user.
     */
    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update existing user record.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $this->userService->updateUser($user, $validated, Auth::user());

        return redirect()->route('admin.users')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Toggle active/suspended status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $newStatus = $this->userService->toggleUserStatus($user, Auth::user());
        return back()->with('success', "User '{$user->name}' account is now {$newStatus}.");
    }

    /**
     * Delete user safely.
     */
    public function destroy(User $user): RedirectResponse
    {
        $result = $this->userService->deleteUser($user, Auth::user());
        return redirect()->route('admin.users')->with('success', $result['message']);
    }
}
