<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuspendedAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $customer;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);

        $this->superAdmin = User::where('email', 'admin@wearday.com')->first();

        // 1. Customer
        $customerRole = Role::where('slug', UserRole::CUSTOMER->value)->first();
        $this->customer = User::create([
            'name' => 'Active Customer Alice',
            'email' => 'alice.active@example.com',
            'password' => Hash::make('SecretPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);

        // 2. Staff User (Store Manager)
        $staffRole = Role::where('slug', UserRole::STORE_MANAGER->value)->first();
        $this->staffUser = User::create([
            'name' => 'Manager Mike',
            'email' => 'mike.mgr@wearday.com',
            'password' => Hash::make('ManagerPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->staffUser->roles()->sync([$staffRole->id]);
    }

    public function test_active_user_has_normal_access(): void
    {
        $token = $this->customer->createToken('alice-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'alice.active@example.com',
                ],
            ]);
    }

    public function test_suspended_user_with_existing_token_is_rejected_and_tokens_purged(): void
    {
        // 1. Issue token while active
        $token = $this->customer->createToken('alice-token')->plainTextToken;
        $this->assertEquals(1, $this->customer->tokens()->count());

        // 2. Admin suspends customer
        $this->actingAs($this->superAdmin)->post("/admin/users/{$this->customer->id}/toggle");
        $this->customer->refresh();
        $this->assertEquals('suspended', $this->customer->status);

        // Tokens must be revoked in database immediately
        $this->assertEquals(0, DB::table('personal_access_tokens')->where('tokenable_id', $this->customer->id)->count());

        // 3. User attempts to use pre-existing token
        app('auth')->forgetGuards();
        $this->flushSession();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        // Token is gone, rejected with 401
        $this->assertTrue(in_array($response->getStatusCode(), [401, 403]));
    }

    public function test_suspended_admin_session_is_invalidated_immediately(): void
    {
        // 1. Staff logs in to admin web portal
        $this->actingAs($this->staffUser);
        $dashboardRes = $this->get('/admin');
        $dashboardRes->assertStatus(200);

        // 2. Super Admin suspends the staff member
        $this->staffUser->update(['status' => 'suspended']);

        // 3. Staff member attempts subsequent administrative request
        $subsequentRes = $this->get('/admin');

        // Must be rejected and redirected to admin login with session destroyed
        $subsequentRes->assertStatus(302)
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_suspended_customer_cannot_access_protected_endpoints(): void
    {
        $this->customer->update(['status' => 'suspended']);
        $token = $this->customer->createToken('test-token')->plainTextToken;

        // Reset memory guards
        app('auth')->forgetGuards();

        // Orders endpoint
        $ordersRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/orders');
        $this->assertTrue(in_array($ordersRes->getStatusCode(), [401, 403]));

        // Returns endpoint
        $returnsRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/returns');
        $this->assertTrue(in_array($returnsRes->getStatusCode(), [401, 403]));

        // Wishlist endpoint
        $wishlistRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/wishlist');
        $this->assertTrue(in_array($wishlistRes->getStatusCode(), [401, 403]));

        // Notifications endpoint
        $notifRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications');
        $this->assertTrue(in_array($notifRes->getStatusCode(), [401, 403]));
    }

    public function test_suspended_customer_cannot_checkout(): void
    {
        $this->customer->update(['status' => 'suspended']);
        $token = $this->customer->createToken('checkout-token')->plainTextToken;

        app('auth')->forgetGuards();

        // 1. Checkout Preview
        $previewRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/checkout/preview', [
                'items' => [['variant_id' => 1, 'quantity' => 1]],
            ]);
        $previewRes->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact customer support.',
            ]);

        // 2. Checkout Process
        $processRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/checkout', [
                'items' => [['variant_id' => 1, 'quantity' => 1]],
                'shipping_address' => ['street' => '123 Main St', 'city' => 'New York', 'state' => 'NY', 'zip_code' => '10001', 'country' => 'US'],
                'billing_address' => ['street' => '123 Main St', 'city' => 'New York', 'state' => 'NY', 'zip_code' => '10001', 'country' => 'US'],
                'payment_method' => 'card',
            ]);
        $processRes->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact customer support.',
            ]);
    }

    public function test_reactivated_user_cannot_use_old_token_and_requires_fresh_login(): void
    {
        // 1. User has token while active
        $oldToken = $this->customer->createToken('old-token')->plainTextToken;

        // 2. Account is suspended (revoking token)
        $this->actingAs($this->superAdmin)->post("/admin/users/{$this->customer->id}/toggle");
        $this->customer->refresh();
        $this->assertEquals('suspended', $this->customer->status);
        $this->assertEquals(0, DB::table('personal_access_tokens')->where('tokenable_id', $this->customer->id)->count());

        // 3. Admin reactivates account
        $this->actingAs($this->superAdmin)->post("/admin/users/{$this->customer->id}/toggle");
        $this->customer->refresh();
        $this->assertEquals('active', $this->customer->status);

        // 4. Old token MUST NOT work even after reactivation
        app('auth')->forgetGuards();
        $this->flushSession();

        $oldTokenRes = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me');
        $oldTokenRes->assertStatus(401);

        // 5. Fresh login succeeds and yields brand new active token
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice.active@example.com',
            'password' => 'SecretPass123!',
        ]);
        $loginRes->assertStatus(200)->assertJson(['success' => true]);
        $newToken = $loginRes->json('data.token');
        $this->assertNotEmpty($newToken);

        // 6. New token works
        $newTokenRes = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/auth/me');
        $newTokenRes->assertStatus(200)->assertJson(['success' => true]);
    }
}
