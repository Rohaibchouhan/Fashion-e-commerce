<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(CouponSeeder::class);
    }

    public function test_can_fetch_empty_cart(): void
    {
        $response = $this->withHeader('X-Session-Id', 'test-session-123')
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'items',
                    'itemsCount',
                    'subtotal',
                    'total',
                ],
            ]);

        $this->assertEquals(0, $response->json('data.itemsCount'));
    }

    public function test_can_add_item_to_cart_with_variant(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('X-Session-Id', 'test-session-123')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $cartRes = $this->withHeader('X-Session-Id', 'test-session-123')
            ->getJson('/api/v1/cart');

        $this->assertEquals(2, $cartRes->json('data.itemsCount'));
        $item = $cartRes->json('data.items.0');
        $this->assertEquals($variant->sku, $item['sku']);
        $this->assertEquals((float) $variant->price, (float) $item['price']);
        $this->assertEquals(round($variant->price * 2, 2), (float) $item['subtotal']);
    }

    public function test_add_duplicate_variant_updates_existing_line_quantity(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Add 1 unit
        $this->withHeader('X-Session-Id', 'dup-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ])->assertStatus(200);

        // Add 2 more units of the exact same variant
        $secondRes = $this->withHeader('X-Session-Id', 'dup-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $secondRes->assertStatus(200);

        $cartRes = $this->withHeader('X-Session-Id', 'dup-session')
            ->getJson('/api/v1/cart');

        // Must still be 1 unique line item with combined quantity of 3
        $this->assertCount(1, $cartRes->json('data.items'));
        $this->assertEquals(3, $cartRes->json('data.itemsCount'));
        $this->assertEquals(3, $cartRes->json('data.items.0.quantity'));
    }

    public function test_update_cart_item_quantity(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $addRes = $this->withHeader('X-Session-Id', 'update-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $itemId = $addRes->json('data.items.0.id');

        $updateRes = $this->withHeader('X-Session-Id', 'update-session')
            ->putJson("/api/v1/cart/items/{$itemId}", [
                'quantity' => 4,
            ]);

        $updateRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(4, $updateRes->json('data.items.0.quantity'));
    }

    public function test_remove_cart_item(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $addRes = $this->withHeader('X-Session-Id', 'remove-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $itemId = $addRes->json('data.items.0.id');

        $removeRes = $this->withHeader('X-Session-Id', 'remove-session')
            ->deleteJson("/api/v1/cart/items/{$itemId}");

        $removeRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(0, $removeRes->json('data.items'));
        $this->assertEquals(0, $removeRes->json('data.itemsCount'));
    }

    public function test_cannot_add_more_quantity_than_available_inventory(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['stock' => 2]);

        $response = $this->withHeader('X-Session-Id', 'test-session-123')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_add_more_than_available_inventory_accounting_for_reserved_stock(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['stock' => 5]);

        // If 3 units are reserved by ongoing checkouts, only 2 units are available
        $inv = $variant->inventory;
        if ($inv) {
            $inv->update(['quantity' => 5, 'reserved_quantity' => 3]);
        }

        // Attempting to add 3 should fail because only 2 are available
        $response = $this->withHeader('X-Session-Id', 'reserved-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 3,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        // Adding 2 should succeed
        $responseValid = $this->withHeader('X-Session-Id', 'reserved-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $responseValid->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_cannot_add_inactive_product_to_cart(): void
    {
        $product = Product::first();
        $product->update(['is_active' => false]);
        $variant = $product->variants->first();

        $response = $this->withHeader('X-Session-Id', 'inactive-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertStringContainsString('inactive', strtolower($response->json('message')));
    }

    public function test_cannot_add_inactive_variant_to_cart(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['is_active' => false]);

        $response = $this->withHeader('X-Session-Id', 'inactive-var-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertStringContainsString('unavailable', strtolower($response->json('message')));
    }

    public function test_cannot_add_variant_belonging_to_another_product(): void
    {
        $products = Product::take(2)->get();
        $prod1 = $products[0];
        $prod2 = $products[1];
        $variantOfProd2 = $prod2->variants->first();

        // Attempt to add prod1 with prod2's variant ID
        $response = $this->withHeader('X-Session-Id', 'mismatch-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $prod1->id,
                'variant_id' => $variantOfProd2->id,
                'quantity' => 1,
            ]);

        // Must fail with 404 or 422
        $this->assertTrue(in_array($response->status(), [404, 422]));
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('X-Session-Id', 'neg-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => -2,
            ]);

        $response->assertStatus(422);
    }

    public function test_update_quantity_rejects_exceeding_stock(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['stock' => 5]);

        $addRes = $this->withHeader('X-Session-Id', 'exceed-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        $itemId = $addRes->json('data.items.0.id');

        $updateRes = $this->withHeader('X-Session-Id', 'exceed-session')
            ->putJson("/api/v1/cart/items/{$itemId}", [
                'quantity' => 50,
            ]);

        $updateRes->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cart_recalculates_authoritative_prices_ignoring_frontend_tampering(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $authoritativePrice = (float) $variant->price;

        // Frontend attempts to submit item with tampered price $0.01
        $addRes = $this->withHeader('X-Session-Id', 'tamper-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
                'unit_price' => 0.01,
                'price' => 0.01,
            ]);

        $addRes->assertStatus(200);

        // Price in cart MUST remain authoritative database price
        $itemPrice = (float) $addRes->json('data.items.0.price');
        $this->assertEquals($authoritativePrice, $itemPrice);
    }

    public function test_cart_persists_across_sessions_in_database(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $this->withHeader('X-Session-Id', 'persist-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 3,
            ]);

        // Direct database verification
        $cart = Cart::where('session_id', 'persist-session')->first();
        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->items()->count());
        $this->assertEquals(3, $cart->items()->first()->quantity);
    }

    public function test_guest_cart_merges_into_customer_cart_on_login(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // 1. Guest adds 2 items
        $this->withHeader('X-Session-Id', 'guest-merge-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ])->assertStatus(200);

        // 2. Customer logs in (has an existing cart with 1 unit of same variant)
        $customer = User::factory()->create();
        $customerCart = Cart::create(['user_id' => $customer->id]);
        CartItem::create([
            'cart_id' => $customerCart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->price,
        ]);

        // Customer accesses cart with guest session header
        $mergedRes = $this->actingAs($customer, 'sanctum')
            ->withHeader('X-Session-Id', 'guest-merge-session')
            ->getJson('/api/v1/cart');

        $mergedRes->assertStatus(200);

        // Total quantity should now be 2 (guest) + 1 (customer) = 3
        $this->assertEquals(3, $mergedRes->json('data.itemsCount'));
        $this->assertEquals(3, $mergedRes->json('data.items.0.quantity'));

        // Old guest cart must be cleaned up from database
        $this->assertNull(Cart::where('session_id', 'guest-merge-session')->whereNull('user_id')->first());
    }

    public function test_customer_cannot_access_or_modify_another_customers_cart(): void
    {
        $customerA = User::factory()->create();
        $customerB = User::factory()->create();

        $product = Product::first();
        $variant = $product->variants->first();

        // Customer A adds item
        $cartA = Cart::create(['user_id' => $customerA->id]);
        $itemA = CartItem::create([
            'cart_id' => $cartA->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => $variant->price,
        ]);

        // Customer B attempts to update itemA belonging to customer A
        $response = $this->actingAs($customerB, 'sanctum')
            ->putJson("/api/v1/cart/items/{$itemA->id}", [
                'quantity' => 10,
            ]);

        // Must fail with 404/422 because item does not belong to Customer B's cart
        $this->assertTrue(in_array($response->status(), [404, 422]));

        // Customer A's item remains unchanged
        $this->assertEquals(2, $itemA->fresh()->quantity);
    }

    public function test_can_apply_valid_coupon_and_discount_cart(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Add 2 items so subtotal >= $30 min_spend
        $this->withHeader('X-Session-Id', 'test-session-123')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ]);

        // Apply coupon WELCOME10 (10% off)
        $couponRes = $this->withHeader('X-Session-Id', 'test-session-123')
            ->postJson('/api/v1/cart/coupon', [
                'code' => 'WELCOME10',
            ]);

        $couponRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $cart = $couponRes->json('data');
        $this->assertGreaterThan(0, $cart['discount']);
        $this->assertEquals('WELCOME10', $cart['couponCode']);
    }

    public function test_can_clear_cart(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $this->withHeader('X-Session-Id', 'test-session-123')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $clearRes = $this->withHeader('X-Session-Id', 'test-session-123')
            ->deleteJson('/api/v1/cart');

        $clearRes->assertStatus(200);

        $cart = $this->withHeader('X-Session-Id', 'test-session-123')
            ->getJson('/api/v1/cart');

        $this->assertEquals(0, $cart->json('data.itemsCount'));
    }
}
