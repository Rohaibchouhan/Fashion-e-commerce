<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'products'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'module' => 'products'],
            ['name' => 'Edit Products', 'slug' => 'products.edit', 'module' => 'products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'module' => 'products'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'module' => 'inventory'],
            ['name' => 'Adjust Inventory', 'slug' => 'inventory.adjust', 'module' => 'inventory'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'module' => 'orders'],
            ['name' => 'Update Order Status', 'slug' => 'orders.update_status', 'module' => 'orders'],
            ['name' => 'Cancel Orders', 'slug' => 'orders.cancel', 'module' => 'orders'],

            // Shipping
            ['name' => 'Manage Shipping', 'slug' => 'shipping.manage', 'module' => 'shipping'],

            // Payments & Refunds
            ['name' => 'View Payments', 'slug' => 'payments.view', 'module' => 'payments'],
            ['name' => 'Process Refunds', 'slug' => 'refunds.process', 'module' => 'payments'],

            // Returns
            ['name' => 'View Returns', 'slug' => 'returns.view', 'module' => 'returns'],
            ['name' => 'Approve Returns', 'slug' => 'returns.approve', 'module' => 'returns'],
            ['name' => 'Inspect Returns', 'slug' => 'returns.inspect', 'module' => 'returns'],

            // Customers
            ['name' => 'View Customers', 'slug' => 'customers.view', 'module' => 'customers'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'module' => 'customers'],

            // Coupons & Discounts
            ['name' => 'Manage Coupons', 'slug' => 'coupons.manage', 'module' => 'coupons'],

            // Reviews
            ['name' => 'Moderate Reviews', 'slug' => 'reviews.moderate', 'module' => 'reviews'],

            // CMS & Settings
            ['name' => 'Manage CMS', 'slug' => 'cms.manage', 'module' => 'cms'],
            ['name' => 'Manage Store Settings', 'slug' => 'settings.manage', 'module' => 'settings'],
            ['name' => 'Manage Admin Users', 'slug' => 'admin_users.manage', 'module' => 'admin_users'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $allPermissionIds = Permission::pluck('id')->toArray();

        // Create Roles
        $rolesData = [
            UserRole::SUPER_ADMIN->value => [
                'name' => 'Super Admin',
                'description' => 'Full unrestricted system access',
                'permissions' => $allPermissionIds,
            ],
            UserRole::ADMIN->value => [
                'name' => 'Admin',
                'description' => 'Store administrator with management privileges',
                'permissions' => $allPermissionIds,
            ],
            UserRole::STORE_MANAGER->value => [
                'name' => 'Store Manager',
                'description' => 'Manages products, inventory, orders, returns, and customers',
                'permissions' => Permission::whereIn('module', ['products', 'inventory', 'orders', 'shipping', 'returns', 'customers', 'coupons', 'reviews'])->pluck('id')->toArray(),
            ],
            UserRole::PRODUCT_MANAGER->value => [
                'name' => 'Product Manager',
                'description' => 'Manages catalog, categories, and product listings',
                'permissions' => Permission::whereIn('module', ['products', 'reviews'])->pluck('id')->toArray(),
            ],
            UserRole::ORDER_MANAGER->value => [
                'name' => 'Order Manager',
                'description' => 'Processes customer orders, fulfillment, and shipping',
                'permissions' => Permission::whereIn('module', ['orders', 'shipping'])->pluck('id')->toArray(),
            ],
            UserRole::CUSTOMER_SUPPORT->value => [
                'name' => 'Customer Support',
                'description' => 'Assists customers, reviews inquiries and return requests',
                'permissions' => Permission::whereIn('module', ['orders', 'returns', 'customers', 'reviews'])->pluck('id')->toArray(),
            ],
            UserRole::INVENTORY_MANAGER->value => [
                'name' => 'Inventory Manager',
                'description' => 'Tracks stock, logs adjustments and incoming supplies',
                'permissions' => Permission::whereIn('module', ['inventory', 'products'])->pluck('id')->toArray(),
            ],
            UserRole::CUSTOMER->value => [
                'name' => 'Customer',
                'description' => 'Registered fashion shopper',
                'permissions' => [],
            ],
        ];

        foreach ($rolesData as $slug => $data) {
            $role = Role::firstOrCreate(['slug' => $slug], [
                'name' => $data['name'],
                'description' => $data['description'],
            ]);
            $role->permissions()->sync($data['permissions']);
        }

        // Seed Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@wearday.com'],
            [
                'name' => 'Super Administrator',
                'phone' => '+1 (555) 100-2000',
                'password' => Hash::make('Admin1234!'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdminRole = Role::where('slug', UserRole::SUPER_ADMIN->value)->first();
        if ($superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Seed Default Customer matching Next.js mock user
        $customer = User::firstOrCreate(
            ['email' => 'alex.morgan@example.com'],
            [
                'name' => 'Alex Morgan',
                'phone' => '+1 (555) 019-2834',
                'password' => Hash::make('Password123!'),
                'status' => 'active',
                'email_verified_at' => now(),
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80',
            ]
        );
        $customerRole = Role::where('slug', UserRole::CUSTOMER->value)->first();
        if ($customerRole) {
            $customer->roles()->syncWithoutDetaching([$customerRole->id]);
        }

        // Customer Profile & Addresses
        $customer->customerProfile()->firstOrCreate([
            'gender' => 'unspecified',
            'preferred_currency' => 'USD',
            'marketing_opt_in' => true,
        ]);

        $customer->addresses()->firstOrCreate(
            ['street' => '742 Evergreen Terrace'],
            [
                'first_name' => 'Alex',
                'last_name' => 'Morgan',
                'apartment' => 'Apt 4B',
                'city' => 'Brooklyn',
                'state' => 'NY',
                'zip_code' => '11201',
                'country' => 'United States',
                'phone' => '+1 (555) 019-2834',
                'type' => 'both',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]
        );

        $customer->addresses()->firstOrCreate(
            ['street' => '120 Broadway'],
            [
                'first_name' => 'Alex',
                'last_name' => 'Morgan',
                'apartment' => 'Suite 1500',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10271',
                'country' => 'United States',
                'phone' => '+1 (555) 019-2834',
                'type' => 'both',
                'is_default_shipping' => false,
                'is_default_billing' => false,
            ]
        );
    }
}

