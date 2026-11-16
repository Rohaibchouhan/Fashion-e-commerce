<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $staffUser;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);

        $this->superAdmin = User::where('email', 'admin@wearday.com')->first();

        // Create secondary staff member
        $staffRole = Role::where('slug', 'store_manager')->first();
        $this->staffUser = User::create([
            'name' => 'Store Manager John',
            'email' => 'manager.john@wearday.com',
            'phone' => '+1 (555) 234-5678',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->staffUser->roles()->sync([$staffRole->id]);

        // Create standard customer
        $customerRole = Role::where('slug', 'customer')->first();
        $this->customer = User::create([
            'name' => 'Alice Customer',
            'email' => 'alice@example.com',
            'phone' => '+1 (555) 876-5432',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);
    }

    public function test_admin_can_view_user_listing_with_pagination(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee('Alice Customer');
        $response->assertSee('Store Manager John');
    }

    public function test_admin_can_search_users_by_name_email_and_phone(): void
    {
        // Search by name
        $resName = $this->actingAs($this->superAdmin)->get('/admin/users?search=Alice');
        $resName->assertStatus(200);
        $resName->assertSee('Alice Customer');
        $resName->assertDontSee('Store Manager John');

        // Search by email
        $resEmail = $this->actingAs($this->superAdmin)->get('/admin/users?search=manager.john');
        $resEmail->assertStatus(200);
        $resEmail->assertSee('Store Manager John');
        $resEmail->assertDontSee('Alice Customer');

        // Search by phone
        $resPhone = $this->actingAs($this->superAdmin)->get('/admin/users?search=876-5432');
        $resPhone->assertStatus(200);
        $resPhone->assertSee('Alice Customer');
    }

    public function test_admin_can_filter_users_by_role_and_status(): void
    {
        // Filter customers
        $resCustomer = $this->actingAs($this->superAdmin)->get('/admin/users?role=customer');
        $resCustomer->assertStatus(200);
        $resCustomer->assertSee('Alice Customer');
        $resCustomer->assertDontSee('Store Manager John');

        // Filter staff
        $resStaff = $this->actingAs($this->superAdmin)->get('/admin/users?role=staff');
        $resStaff->assertStatus(200);
        $resStaff->assertSee('Store Manager John');
        $resStaff->assertDontSee('Alice Customer');

        // Filter by active status
        $resActive = $this->actingAs($this->superAdmin)->get('/admin/users?status=active');
        $resActive->assertStatus(200);
        $resActive->assertSee('Alice Customer');
    }

    public function test_admin_can_create_new_user_with_role_and_hashed_password(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();

        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'first_name' => 'David',
            'last_name' => 'Miller',
            'email' => 'david.miller@example.com',
            'phone' => '+1 (555) 999-1122',
            'password' => 'SecurePass123!',
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $response->assertStatus(302)->assertRedirect(route('admin.users'));

        $this->assertDatabaseHas('users', [
            'name' => 'David Miller',
            'email' => 'david.miller@example.com',
            'phone' => '+1 (555) 999-1122',
            'status' => 'active',
        ]);

        $created = User::where('email', 'david.miller@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('SecurePass123!', $created->password));
        $this->assertTrue($created->roles()->where('slug', 'customer')->exists());
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();

        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'name' => 'Duplicate Email',
            'email' => 'alice@example.com', // Already taken
            'password' => 'Password123!',
            'role_id' => $customerRole->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_view_user_details_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get("/admin/users/{$this->customer->id}");
        $response->assertStatus(200);
        $response->assertSee('Alice Customer');
        $response->assertSee('Identity Overview');
        $response->assertSee('Customer Address Book');
    }

    public function test_admin_can_edit_and_update_user(): void
    {
        $orderManagerRole = Role::where('slug', 'order_manager')->first();

        $response = $this->actingAs($this->superAdmin)->put("/admin/users/{$this->staffUser->id}", [
            'name' => 'John Senior Manager',
            'email' => 'john.senior@wearday.com',
            'phone' => '+1 (555) 777-8899',
            'role_id' => $orderManagerRole->id,
            'status' => 'active',
        ]);

        $response->assertStatus(302)->assertRedirect(route('admin.users'));

        $this->staffUser->refresh();
        $this->assertEquals('John Senior Manager', $this->staffUser->name);
        $this->assertEquals('john.senior@wearday.com', $this->staffUser->email);
        $this->assertEquals('+1 (555) 777-8899', $this->staffUser->phone);
        $this->assertTrue($this->staffUser->roles()->where('slug', 'order_manager')->exists());
    }

    public function test_admin_can_toggle_account_status_and_deactivated_user_cannot_login(): void
    {
        // 1. Toggle status to suspended
        $toggleRes = $this->actingAs($this->superAdmin)->post("/admin/users/{$this->customer->id}/toggle");
        $toggleRes->assertStatus(302);

        $this->customer->refresh();
        $this->assertEquals('suspended', $this->customer->status);

        // 2. Suspended customer cannot login via API
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@example.com',
            'password' => 'Password123!',
        ]);
        $loginRes->assertStatus(422);
        $loginRes->assertJsonFragment(['email' => ['Your account has been suspended. Please contact customer support.']]);

        // 3. Reactivate customer
        $this->actingAs($this->superAdmin)->post("/admin/users/{$this->customer->id}/toggle");
        $this->customer->refresh();
        $this->assertEquals('active', $this->customer->status);

        // 4. Active customer can now login
        $goodLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@example.com',
            'password' => 'Password123!',
        ]);
        $goodLogin->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_super_admin_cannot_be_deleted_if_last_one(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete("/admin/users/{$this->superAdmin->id}");
        // Self-delete or last super admin delete blocked
        $response->assertSessionHasErrors(['user']);
        $this->assertNull($this->superAdmin->fresh()->deleted_at);
    }

    public function test_safe_user_deletion_soft_deletes_and_preserves_orders(): void
    {
        // 1. Create a customer with an order
        $customerRole = Role::where('slug', 'customer')->first();
        $buyer = User::create([
            'name' => 'Buyer With Order',
            'email' => 'buyer.order@example.com',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $buyer->roles()->sync([$customerRole->id]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-99999',
            'customer_id' => $buyer->id,
            'subtotal' => 100.00,
            'tax' => 10.00,
            'shipping' => 5.00,
            'discount' => 0.00,
            'total' => 115.00,
            'currency' => 'USD',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'shipping_address' => ['street' => '123 Test St'],
            'billing_address' => ['street' => '123 Test St'],
        ]);

        // 2. Admin deletes buyer
        $response = $this->actingAs($this->superAdmin)->delete("/admin/users/{$buyer->id}");
        $response->assertStatus(302);

        // 3. Buyer is soft-deleted and marked suspended
        $buyerFresh = User::withTrashed()->find($buyer->id);
        $this->assertNotNull($buyerFresh->deleted_at);
        $this->assertEquals('suspended', $buyerFresh->status);

        // 4. Order and order relationship remain intact
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $buyer->id,
            'total' => 115.00,
        ]);
    }

    public function test_non_admin_customer_cannot_access_administrative_user_apis(): void
    {
        $token = $this->customer->createToken('customer-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_manage_users_via_rest_api(): void
    {
        $token = $this->superAdmin->createToken('admin-token')->plainTextToken;

        // 1. List users via REST API
        $listRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users?role=customer');
        $listRes->assertStatus(200)->assertJson(['success' => true]);

        // 2. Create user via REST API
        $createRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/users', [
                'name' => 'API Created Customer',
                'email' => 'api.customer@example.com',
                'password' => 'Password123!',
                'role' => 'customer',
                'status' => 'active',
            ]);
        $createRes->assertStatus(201)->assertJson(['success' => true]);
        $newUserId = $createRes->json('data.id');

        // 3. Update status via REST API
        $statusRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/users/{$newUserId}/status", [
                'status' => 'suspended',
            ]);
        $statusRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('suspended', User::find($newUserId)->status);

        // 4. Safe delete via REST API
        $deleteRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/users/{$newUserId}");
        $deleteRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotNull(User::withTrashed()->find($newUserId)->deleted_at);
    }

    public function test_create_user_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', []);

        $response->assertSessionHasErrors(['email', 'password', 'status']);
    }

    public function test_create_user_with_invalid_email(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'first_name' => 'Invalid',
            'last_name' => 'Email',
            'email' => 'not-an-email-format',
            'password' => 'Password123!',
            'role_id' => 1,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_create_user_with_invalid_phone_number(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'first_name' => 'Invalid',
            'last_name' => 'Phone',
            'email' => 'valid.phone.test@example.com',
            'phone' => '123-abc-INVALID-###',
            'password' => 'Password123!',
            'role_id' => 1,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_create_user_with_invalid_role(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'first_name' => 'Invalid',
            'last_name' => 'Role',
            'email' => 'valid.role.test@example.com',
            'password' => 'Password123!',
            'role_id' => 99999, // Nonexistent role
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['role_id']);
    }

    public function test_create_user_with_password_confirmation_mismatch(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'first_name' => 'Password',
            'last_name' => 'Mismatch',
            'email' => 'password.mismatch@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'CompletelyDifferentPassword!',
            'role_id' => 1,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_update_user_with_invalid_data(): void
    {
        $response = $this->actingAs($this->superAdmin)->put("/admin/users/{$this->customer->id}", [
            'name' => '',
            'email' => 'not-a-valid-email',
            'phone' => 'invalid-chars-@@@',
            'status' => 'nonexistent_status',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'phone', 'status']);
    }

    public function test_handle_deletion_of_nonexistent_user(): void
    {
        // 1. Web admin deletion of nonexistent user returns 404 Not Found
        $response = $this->actingAs($this->superAdmin)->delete('/admin/users/99999');
        $response->assertStatus(404);

        // 2. API deletion of nonexistent user returns JSON 404 Not Found
        $token = $this->superAdmin->createToken('admin-token')->plainTextToken;
        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/admin/users/99999');
        $apiRes->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
