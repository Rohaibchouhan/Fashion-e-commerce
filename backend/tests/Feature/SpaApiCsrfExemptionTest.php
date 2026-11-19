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

class SpaApiCsrfExemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);
    }

    public function test_api_post_from_spa_origin_does_not_fail_with_419_csrf(): void
    {
        $product = Product::first();

        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
            'X-Session-Id' => 'spa-session-test-1',
        ])->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertNotEquals(419, $response->getStatusCode(), 'API cart items must not be rejected with HTTP 419 CSRF.');
        $response->assertStatus(200);
    }

    public function test_auth_login_from_spa_origin_does_not_fail_with_419_csrf(): void
    {
        $user = User::factory()->create([
            'email' => 'spa.customer@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $user->roles()->sync([Role::where('slug', 'customer')->first()->id]);

        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/login', [
            'email' => 'spa.customer@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertNotEquals(419, $response->getStatusCode(), 'API auth login must not be rejected with HTTP 419 CSRF.');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_order_track_from_spa_origin_does_not_fail_with_419_csrf(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/orders/track', [
            'order_number' => 'NONEXISTENT',
            'email_or_zip' => 'customer@example.com',
        ]);

        $this->assertNotEquals(419, $response->getStatusCode(), 'API order tracking must not be rejected with HTTP 419 CSRF.');
        // Expecting 404 since order does not exist, not 419
        $response->assertStatus(404);
    }
}
