<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function registerCustomer(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'activation_token' => Str::random(60),
        ]);

        $customerRole = Role::where('slug', UserRole::CUSTOMER->value)->first();
        if ($customerRole) {
            $user->roles()->syncWithoutDetaching([$customerRole->id]);
        }

        $user->customerProfile()->create([
            'gender' => $data['gender'] ?? 'unspecified',
            'preferred_currency' => $data['preferred_currency'] ?? 'USD',
            'marketing_opt_in' => $data['marketing_opt_in'] ?? false,
        ]);

        event(new Registered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('customerProfile', 'addresses', 'roles'),
            'token' => $token,
        ];
    }

    public function loginCustomer(string $email, string $password): array
    {
        $user = User::where('email', strtolower($email))->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            $statusMsg = match ($user->status) {
                'suspended' => 'Your account has been suspended. Please contact customer support.',
                'inactive' => 'Your account is inactive. Please contact customer support.',
                'pending_activation' => 'Your account is pending activation. Please check your email for the verification link.',
                default => 'Your account is not active.',
            };
            throw ValidationException::withMessages([
                'email' => [$statusMsg],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('customerProfile', 'addresses', 'roles'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function findOrCreateCustomerForCheckout(array $customerData): array
    {
        $email = strtolower(trim($customerData['email']));
        $user = User::where('email', $email)->first();

        if ($user) {
            throw ValidationException::withMessages([
                'email' => ["An account with email '{$email}' already exists. Please sign in to complete your order."],
            ]);
        }

        // Generate secure random password for auto-created checkout account
        $randomPassword = Str::random(16);
        $activationToken = Str::random(64);

        $user = User::create([
            'name' => trim($customerData['name']),
            'email' => $email,
            'phone' => $customerData['phone'] ?? null,
            'password' => Hash::make($randomPassword),
            'status' => 'active',
            'activation_token' => $activationToken,
        ]);

        $customerRole = Role::where('slug', UserRole::CUSTOMER->value)->first();
        if ($customerRole) {
            $user->roles()->syncWithoutDetaching([$customerRole->id]);
        }

        $user->customerProfile()->create([
            'gender' => 'unspecified',
            'preferred_currency' => 'USD',
            'marketing_opt_in' => true,
        ]);

        $token = $user->createToken('checkout-auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'is_new' => true,
            'activation_token' => $user->activation_token,
        ];
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update(array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? null,
        ]));

        if (isset($data['date_of_birth']) || isset($data['gender']) || isset($data['marketing_opt_in'])) {
            $user->customerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'marketing_opt_in' => $data['marketing_opt_in'] ?? null,
                ])
            );
        }

        return $user->load('customerProfile', 'addresses');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password entered is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all tokens except the current one if desired
        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();
    }

    public function addAddress(User $user, array $data): Address
    {
        if (!empty($data['is_default_shipping'])) {
            $user->addresses()->update(['is_default_shipping' => false]);
        }
        if (!empty($data['is_default_billing'])) {
            $user->addresses()->update(['is_default_billing' => false]);
        }

        return $user->addresses()->create($data);
    }

    public function updateAddress(User $user, int $addressId, array $data): Address
    {
        $address = $user->addresses()->findOrFail($addressId);

        if (!empty($data['is_default_shipping'])) {
            $user->addresses()->where('id', '!=', $addressId)->update(['is_default_shipping' => false]);
        }
        if (!empty($data['is_default_billing'])) {
            $user->addresses()->where('id', '!=', $addressId)->update(['is_default_billing' => false]);
        }

        $address->update($data);
        return $address;
    }

    public function deleteAddress(User $user, int $addressId): void
    {
        $address = $user->addresses()->findOrFail($addressId);
        $address->delete();
    }

    public function requestAccountDeletion(User $user): void
    {
        $user->update([
            'deletion_requested_at' => now(),
        ]);
    }
}

