<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerAddressRequest;
use App\Http\Requests\Auth\CustomerChangePasswordRequest;
use App\Http\Requests\Auth\CustomerLoginRequest;
use App\Http\Requests\Auth\CustomerProfileRequest;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->authService->registerCustomer($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful! Welcome to WearDay.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->authService->loginCustomer($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Signed in successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Signed out successfully.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load('customerProfile', 'addresses', 'roles')),
        ]);
    }

    public function updateProfile(CustomerProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->authService->updateProfile($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    public function changePassword(CustomerChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->authService->changePassword($request->user(), $validated['current_password'], $validated['new_password']);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', strtolower($request->email))->first();
        if ($user) {
            Password::sendResetLink($request->only('email'));
        }

        return response()->json([
            'success' => true,
            'message' => "Password reset instructions have been dispatched to {$request->email}.",
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::reset($credentials, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            // Invalidate all existing authentication tokens after a successful password reset
            $user->tokens()->delete();

            // Invalidate database sessions if sessions table is present
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. You may now log in.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'This password reset token is invalid or has expired.',
        ], 400);
    }

    public function addAddress(CustomerAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $address = $this->authService->addAddress($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data' => $address,
        ], 201);
    }

    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'company' => 'nullable|string|max:100',
            'street' => 'sometimes|string|max:255',
            'apartment' => 'nullable|string|max:100',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'zip_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:30',
            'is_default_shipping' => 'nullable|boolean',
            'is_default_billing' => 'nullable|boolean',
        ]);

        $address = $this->authService->updateAddress($request->user(), $id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => $address,
        ]);
    }

    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        $this->authService->deleteAddress($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Address removed successfully.',
        ]);
    }

    public function requestAccountDeletion(Request $request): JsonResponse
    {
        $this->authService->requestAccountDeletion($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Account deletion request received and pending administrative review.',
        ]);
    }
}

