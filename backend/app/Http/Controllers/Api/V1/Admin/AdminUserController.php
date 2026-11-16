<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /**
     * List users with search, role/status filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->getUsers($request->all());

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    /**
     * Get all available system roles.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::all(['id', 'name', 'slug', 'description']);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Create a new user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->userService->createUser($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => "User '{$user->name}' created successfully.",
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Get individual user details.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['roles.permissions', 'customerProfile', 'addresses', 'orders.items']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update an existing user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $updatedUser = $this->userService->updateUser($user, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => "User '{$updatedUser->name}' updated successfully.",
            'data' => new UserResource($updatedUser),
        ]);
    }

    /**
     * Update user account status.
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,pending_activation,suspended',
        ]);

        $updatedUser = $this->userService->updateUser($user, ['status' => $validated['status']], $request->user());

        return response()->json([
            'success' => true,
            'message' => "User status set to '{$updatedUser->status}'.",
            'data' => new UserResource($updatedUser),
        ]);
    }

    /**
     * Delete user safely.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $result = $this->userService->deleteUser($user, $request->user());

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Get user address list.
     */
    public function addresses(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $user->addresses,
        ]);
    }

    /**
     * Add address for user.
     */
    public function addAddress(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'street' => 'required|string|max:255',
            'apartment' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'is_default_shipping' => 'nullable|boolean',
            'is_default_billing' => 'nullable|boolean',
        ]);

        $address = $this->userService->addAddress($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data' => $address,
        ], 201);
    }

    /**
     * Delete address for user.
     */
    public function deleteAddress(User $user, int $addressId): JsonResponse
    {
        $this->userService->deleteAddress($user, $addressId);

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }
}
