<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Get paginated users with multi-parameter search, role/status filtering, and sorting.
     */
    public function getUsers(array $params): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'customerProfile', 'addresses'])
            ->withCount('orders')
            ->withSum('orders', 'total');

        // 1. Search by name, email, or phone
        if (!empty($params['search'])) {
            $term = trim($params['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        // 2. Filter by role
        if (!empty($params['role']) && $params['role'] !== 'all') {
            $roleSlug = $params['role'];
            if ($roleSlug === 'staff') {
                $query->whereHas('roles', fn ($q) => $q->where('slug', '!=', UserRole::CUSTOMER->value));
            } else {
                $query->whereHas('roles', fn ($q) => $q->where('slug', $roleSlug));
            }
        }

        // 3. Filter by account status
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $query->where('status', $params['status']);
        }

        // 4. Filter by registration date
        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        // 5. Sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'email', 'created_at', 'status', 'orders_count'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = min(max((int) ($params['per_page'] ?? 15), 5), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new user with role assignment and validation.
     */
    public function createUser(array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $name = trim($data['name'] ?? '');
            if (empty($name) && (!empty($data['first_name']) || !empty($data['last_name']))) {
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            }

            $user = User::create([
                'name' => $name,
                'email' => strtolower(trim($data['email'])),
                'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
                'email_verified_at' => ($data['status'] ?? 'active') === 'active' ? now() : null,
            ]);

            // Assign Role
            if (!empty($data['role_id'])) {
                $user->roles()->sync([$data['role_id']]);
            } elseif (!empty($data['role'])) {
                $role = Role::where('slug', $data['role'])->first();
                if ($role) {
                    $user->roles()->sync([$role->id]);
                }
            } else {
                $customerRole = Role::where('slug', UserRole::CUSTOMER->value)->first();
                if ($customerRole) {
                    $user->roles()->sync([$customerRole->id]);
                }
            }

            // Customer profile if customer role
            if ($user->isCustomer()) {
                $user->customerProfile()->create([
                    'gender' => $data['gender'] ?? 'unspecified',
                    'preferred_currency' => $data['preferred_currency'] ?? 'USD',
                    'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
                ]);
            }

            // Optional Address
            if (!empty($data['street']) && !empty($data['city']) && !empty($data['country'])) {
                $user->addresses()->create([
                    'first_name' => $data['first_name'] ?? $user->first_name,
                    'last_name' => $data['last_name'] ?? $user->last_name,
                    'street' => $data['street'],
                    'apartment' => $data['apartment'] ?? null,
                    'city' => $data['city'],
                    'state' => $data['state'] ?? '',
                    'zip_code' => $data['zip_code'] ?? '',
                    'country' => $data['country'],
                    'phone' => $user->phone ?? '',
                    'is_default_shipping' => true,
                    'is_default_billing' => true,
                ]);
            }

            ActivityLog::log(
                'user.created',
                "Created user '{$user->name}' ({$user->email}) with role '{$user->role}' by " . ($actor?->name ?? 'System'),
                $user
            );

            return $user->load(['roles', 'customerProfile', 'addresses']);
        });
    }

    /**
     * Update an existing user with security checks.
     */
    public function updateUser(User $user, array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            // Check self-modification restrictions
            if ($actor && $actor->id === $user->id) {
                if (isset($data['status']) && $data['status'] !== 'active') {
                    throw ValidationException::withMessages([
                        'status' => ['You cannot deactivate or suspend your own account.'],
                    ]);
                }
            }

            // Super Admin protection against accidental removal/demotion
            if ($user->isSuperAdmin()) {
                $otherActiveSuperAdmins = User::whereHas('roles', fn ($q) => $q->where('slug', UserRole::SUPER_ADMIN->value))
                    ->where('id', '!=', $user->id)
                    ->where('status', 'active')
                    ->exists();

                if (isset($data['status']) && $data['status'] !== 'active' && !$otherActiveSuperAdmins) {
                    throw ValidationException::withMessages([
                        'status' => ['Cannot deactivate the only active Super Administrator in the system.'],
                    ]);
                }

                if (!empty($data['role_id']) || !empty($data['role'])) {
                    $newRoleSlug = null;
                    if (!empty($data['role_id'])) {
                        $newRole = Role::find($data['role_id']);
                        $newRoleSlug = $newRole?->slug;
                    } elseif (!empty($data['role'])) {
                        $newRoleSlug = $data['role'];
                    }

                    if ($newRoleSlug && $newRoleSlug !== UserRole::SUPER_ADMIN->value && !$otherActiveSuperAdmins) {
                        throw ValidationException::withMessages([
                            'role' => ['Cannot demote the only Super Administrator in the system.'],
                        ]);
                    }
                }
            }

            // Prepare update attributes
            $update = [];

            if (isset($data['first_name']) || isset($data['last_name'])) {
                $firstName = $data['first_name'] ?? $user->first_name;
                $lastName = $data['last_name'] ?? $user->last_name;
                $update['name'] = trim("{$firstName} {$lastName}");
            } elseif (isset($data['name'])) {
                $update['name'] = trim($data['name']);
            }

            if (isset($data['email'])) {
                $update['email'] = strtolower(trim($data['email']));
            }

            if (array_key_exists('phone', $data)) {
                $update['phone'] = !empty($data['phone']) ? trim($data['phone']) : null;
            }

            if (!empty($data['status'])) {
                $update['status'] = $data['status'];
            }

            if (!empty($data['password'])) {
                $update['password'] = Hash::make($data['password']);
            }

            if (!empty($update)) {
                $user->update($update);
            }

            // Update role if supplied
            if (!empty($data['role_id'])) {
                $user->roles()->sync([$data['role_id']]);
            } elseif (!empty($data['role'])) {
                $role = Role::where('slug', $data['role'])->first();
                if ($role) {
                    $user->roles()->sync([$role->id]);
                }
            }

            // Update customer profile if applicable
            if (isset($data['gender']) || isset($data['preferred_currency']) || isset($data['marketing_opt_in'])) {
                $user->customerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_filter([
                        'gender' => $data['gender'] ?? null,
                        'preferred_currency' => $data['preferred_currency'] ?? null,
                        'marketing_opt_in' => $data['marketing_opt_in'] ?? null,
                    ])
                );
            }

            ActivityLog::log(
                'user.updated',
                "Updated user '{$user->name}' ({$user->email}) by " . ($actor?->name ?? 'System'),
                $user
            );

            return $user->fresh(['roles', 'customerProfile', 'addresses']);
        });
    }

    /**
     * Delete user safely, verifying historical orders and protecting super admins.
     */
    public function deleteUser(User $user, ?User $actor = null): array
    {
        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        if ($user->isSuperAdmin()) {
            $otherSuperAdmins = User::whereHas('roles', fn ($q) => $q->where('slug', UserRole::SUPER_ADMIN->value))
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->exists();

            if (!$otherSuperAdmins) {
                throw ValidationException::withMessages([
                    'user' => ['Cannot delete the only Super Administrator in the system.'],
                ]);
            }
        }

        $hasOrders = $user->orders()->exists();

        // Always soft delete to preserve database foreign key integrity
        $user->update(['status' => 'suspended']);
        $user->delete();

        ActivityLog::log(
            'user.deleted',
            "Deleted user '{$user->name}' (" . ($hasOrders ? 'Archived with historical orders' : 'Safe soft delete') . ") by " . ($actor?->name ?? 'System'),
            $user
        );

        if ($hasOrders) {
            return [
                'status' => 'archived',
                'message' => "User '{$user->name}' is associated with customer order history. Account has been safely deactivated and archived without breaking historical purchase or financial records.",
            ];
        }

        return [
            'status' => 'deleted',
            'message' => "User '{$user->name}' has been safely deleted.",
        ];
    }

    /**
     * Toggle user status between active and suspended.
     */
    public function toggleUserStatus(User $user, ?User $actor = null): string
    {
        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'status' => ['You cannot deactivate or suspend your own account.'],
            ]);
        }

        if ($user->isSuperAdmin() && $user->status === 'active') {
            $otherSuperAdmins = User::whereHas('roles', fn ($q) => $q->where('slug', UserRole::SUPER_ADMIN->value))
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->exists();

            if (!$otherSuperAdmins) {
                throw ValidationException::withMessages([
                    'status' => ['Cannot suspend the only Super Administrator in the system.'],
                ]);
            }
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLog::log(
            'user.status_toggled',
            "Toggled user '{$user->name}' status to '{$newStatus}' by " . ($actor?->name ?? 'System'),
            $user
        );

        return $newStatus;
    }

    /**
     * Add address for user.
     */
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

    /**
     * Update address for user.
     */
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

    /**
     * Delete address for user.
     */
    public function deleteAddress(User $user, int $addressId): void
    {
        $address = $user->addresses()->findOrFail($addressId);
        $address->delete();
    }
}
