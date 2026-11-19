<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPermissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularAdmin;
    protected User $customerSupport;
    protected User $inventoryManager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->superAdmin = User::where('email', 'admin@wearday.com')->first();

        // 1. Regular Admin (has admin_users.manage)
        $adminRole = Role::where('slug', UserRole::ADMIN->value)->first();
        $this->regularAdmin = User::create([
            'name' => 'Regular Admin Bob',
            'email' => 'bob.admin@wearday.com',
            'password' => Hash::make('AdminPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->regularAdmin->roles()->sync([$adminRole->id]);

        // 2. Customer Support (no admin_users.manage)
        $supportRole = Role::where('slug', UserRole::CUSTOMER_SUPPORT->value)->first();
        $this->customerSupport = User::create([
            'name' => 'Support Sarah',
            'email' => 'sarah.support@wearday.com',
            'password' => Hash::make('SupportPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customerSupport->roles()->sync([$supportRole->id]);

        // 3. Inventory Manager (no admin_users.manage)
        $invRole = Role::where('slug', UserRole::INVENTORY_MANAGER->value)->first();
        $this->inventoryManager = User::create([
            'name' => 'Inventory Ian',
            'email' => 'ian.inv@wearday.com',
            'password' => Hash::make('InventoryPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->inventoryManager->roles()->sync([$invRole->id]);

        // 4. Customer
        $customerRole = Role::where('slug', UserRole::CUSTOMER->value)->first();
        $this->customer = User::create([
            'name' => 'Charlie Shopper',
            'email' => 'charlie@example.com',
            'password' => Hash::make('CustomerPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);
    }

    public function test_super_admin_has_full_user_management_access(): void
    {
        // Web access
        $webRes = $this->actingAs($this->superAdmin)->get('/admin/users');
        $webRes->assertStatus(200);

        // API access
        $token = $this->superAdmin->createToken('test')->plainTextToken;
        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');
        $apiRes->assertStatus(200);
    }

    public function test_authorized_admin_with_permission_can_manage_users(): void
    {
        $this->assertTrue($this->regularAdmin->hasPermission('admin_users.manage'));

        // Web access
        $webRes = $this->actingAs($this->regularAdmin)->get('/admin/users');
        $webRes->assertStatus(200);

        // API access
        $token = $this->regularAdmin->createToken('test')->plainTextToken;
        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');
        $apiRes->assertStatus(200);

        // Can create a regular staff member
        $supportRoleId = Role::where('slug', 'customer_support')->first()->id;
        $createRes = $this->actingAs($this->regularAdmin)->post('/admin/users', [
            'name' => 'New Support Agent',
            'email' => 'new.agent@wearday.com',
            'password' => 'SecurePassword123!',
            'role_id' => $supportRoleId,
            'status' => 'active',
        ]);
        $createRes->assertStatus(302)->assertRedirect(route('admin.users'));
    }

    public function test_customer_support_denied_user_management_access(): void
    {
        $this->assertFalse($this->customerSupport->hasPermission('admin_users.manage'));

        // Direct web request is denied with 403
        $webRes = $this->actingAs($this->customerSupport)->get('/admin/users');
        $webRes->assertStatus(403);

        // Direct API request is denied with 403
        $token = $this->customerSupport->createToken('test')->plainTextToken;
        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');
        $apiRes->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_inventory_manager_denied_user_management_access(): void
    {
        $this->assertFalse($this->inventoryManager->hasPermission('admin_users.manage'));

        // Direct web request denied with 403
        $webRes = $this->actingAs($this->inventoryManager)->get('/admin/users');
        $webRes->assertStatus(403);

        // Direct API request denied with 403
        $token = $this->inventoryManager->createToken('test')->plainTextToken;
        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');
        $apiRes->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_customer_denied_user_management_access(): void
    {
        // Web access redirects to admin login or denies with 403
        $webRes = $this->actingAs($this->customer)->get('/admin/users');
        $this->assertTrue(in_array($webRes->getStatusCode(), [302, 403]));

        // API request is rejected with 403
        $token = $this->customer->createToken('test')->plainTextToken;
        $apiRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');
        $apiRes->assertStatus(403);
    }

    public function test_unauthorized_direct_url_operations_are_rejected(): void
    {
        // Customer support attempts direct operations
        $createGet = $this->actingAs($this->customerSupport)->get('/admin/users/create');
        $createGet->assertStatus(403);

        $createPost = $this->actingAs($this->customerSupport)->post('/admin/users', [
            'name' => 'Hacker Account',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'role_id' => 1,
            'status' => 'active',
        ]);
        $createPost->assertStatus(403);

        $editGet = $this->actingAs($this->customerSupport)->get("/admin/users/{$this->customer->id}/edit");
        $editGet->assertStatus(403);

        $togglePost = $this->actingAs($this->customerSupport)->post("/admin/users/{$this->customer->id}/toggle");
        $togglePost->assertStatus(403);

        $deleteReq = $this->actingAs($this->customerSupport)->delete("/admin/users/{$this->customer->id}");
        $deleteReq->assertStatus(403);
    }

    public function test_non_super_admin_cannot_escalate_or_create_super_admin(): void
    {
        $superAdminRoleId = Role::where('slug', UserRole::SUPER_ADMIN->value)->first()->id;

        // Regular admin tries to create a Super Admin
        $response = $this->actingAs($this->regularAdmin)->post('/admin/users', [
            'name' => 'Escalated Super Admin',
            'email' => 'escalated@wearday.com',
            'password' => 'Secret1234!',
            'role_id' => $superAdminRoleId,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'escalated@wearday.com']);
    }

    public function test_user_cannot_perform_self_privilege_escalation(): void
    {
        $superAdminRoleId = Role::where('slug', UserRole::SUPER_ADMIN->value)->first()->id;

        // Regular admin tries to change their own role to Super Admin
        $response = $this->actingAs($this->regularAdmin)->put("/admin/users/{$this->regularAdmin->id}", [
            'name' => 'Bob Trying To Elevate',
            'email' => $this->regularAdmin->email,
            'role_id' => $superAdminRoleId,
        ]);

        $response->assertSessionHasErrors(['role']);

        $this->regularAdmin->refresh();
        $this->assertFalse($this->regularAdmin->isSuperAdmin());
    }

    public function test_non_super_admin_cannot_modify_or_delete_super_admin(): void
    {
        // 1. Regular admin tries to modify Super Admin
        $updateRes = $this->actingAs($this->regularAdmin)->put("/admin/users/{$this->superAdmin->id}", [
            'name' => 'Tampered Super Admin',
            'email' => $this->superAdmin->email,
        ]);
        $updateRes->assertSessionHasErrors(['user']);

        // 2. Regular admin tries to delete Super Admin
        $deleteRes = $this->actingAs($this->regularAdmin)->delete("/admin/users/{$this->superAdmin->id}");
        $deleteRes->assertSessionHasErrors(['user']);

        // 3. Regular admin tries to suspend Super Admin
        $toggleRes = $this->actingAs($this->regularAdmin)->post("/admin/users/{$this->superAdmin->id}/toggle");
        $toggleRes->assertSessionHasErrors(['status']);

        $this->superAdmin->refresh();
        $this->assertEquals('Super Administrator', $this->superAdmin->name);
        $this->assertEquals('active', $this->superAdmin->status);
        $this->assertNull($this->superAdmin->deleted_at);
    }

    public function test_last_super_admin_protection(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();

        // 1. Super Admin cannot demote themselves if they are the only active Super Admin
        $demoteRes = $this->actingAs($this->superAdmin)->put("/admin/users/{$this->superAdmin->id}", [
            'name' => 'Demoted Self',
            'email' => $this->superAdmin->email,
            'role_id' => $customerRole->id,
        ]);
        $demoteRes->assertSessionHasErrors();
        $this->assertTrue($this->superAdmin->fresh()->isSuperAdmin());

        // 2. Super Admin cannot deactivate themselves if the only active Super Admin
        $deactivateRes = $this->actingAs($this->superAdmin)->put("/admin/users/{$this->superAdmin->id}", [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'status' => 'suspended',
        ]);
        $deactivateRes->assertSessionHasErrors();
        $this->assertEquals('active', $this->superAdmin->fresh()->status);

        // 3. Super Admin cannot delete themselves if the only active Super Admin
        $deleteRes = $this->actingAs($this->superAdmin)->delete("/admin/users/{$this->superAdmin->id}");
        $deleteRes->assertSessionHasErrors();
        $this->assertNull($this->superAdmin->fresh()->deleted_at);
    }
}
