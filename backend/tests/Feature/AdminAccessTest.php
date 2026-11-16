<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);

        $customerRole = Role::where('slug', 'customer')->first();

        $this->customer = User::create([
            'name' => 'Regular Customer',
            'email' => 'regular@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);

        $this->adminUser = User::where('email', 'admin@wearday.com')->first();
    }

    public function test_unauthenticated_user_accessing_admin_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(302)
            ->assertRedirect(route('admin.login'));
    }

    public function test_customer_user_cannot_access_admin_portal(): void
    {
        // Try logging into admin with customer credentials
        $loginRes = $this->post('/admin/login', [
            'email' => 'regular@example.com',
            'password' => 'Password123!',
        ]);

        $loginRes->assertStatus(302);
        $this->assertGuest();

        // Customer cannot access web admin route and is redirected to admin.login
        $dashboardRes = $this->actingAs($this->customer)->get('/admin');
        $dashboardRes->assertStatus(302)->assertRedirect(route('admin.login'));

        // Customer calling admin JSON route is rejected with 403 Forbidden
        $jsonRes = $this->actingAs($this->customer)->getJson('/admin');
        $jsonRes->assertStatus(403);
    }

    public function test_admin_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('TOTAL REVENUE');
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $product = Product::first();
        $initialState = $product->is_active;

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/products/{$product->id}/toggle");

        $response->assertStatus(302);

        $product->refresh();
        $this->assertNotEquals($initialState, $product->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'product.status_toggle',
            'subject_id' => (string) $product->id,
        ]);
    }

    public function test_admin_can_adjust_inventory_and_logs_audit_trail(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/inventory/{$variant->id}/adjust", [
                'new_quantity' => 42,
                'reason' => 'Physical inventory count reconciliation',
            ]);

        $response->assertStatus(302);

        $variant->inventory->refresh();
        $this->assertEquals(42, $variant->inventory->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'type' => 'manual_adjustment',
            'quantity_after' => 42,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'inventory.adjusted',
        ]);
    }

    public function test_admin_can_view_activity_logs(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/activity-logs');
        $response->assertStatus(200);
        $response->assertSee('Activity Audit Logs');
    }

    public function test_admin_can_delete_product_via_delete_method(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/products/{$product->id}/delete");

        $response->assertStatus(302)->assertRedirect(route('admin.products'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_delete_product_via_post_method(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/products/{$product->id}/delete");

        $response->assertStatus(302)->assertRedirect(route('admin.products'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_delete_product_via_restful_route(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/products/{$product->id}");

        $response->assertStatus(302)->assertRedirect(route('admin.products'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_csrf_token_mismatch_on_admin_gracefully_redirects_to_login(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/login', 'POST');
        $session = app('session')->driver();
        $request->setLaravelSession($session);

        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, new \Illuminate\Session\TokenMismatchException);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('admin.login'), $response->getTargetUrl());
    }
}
