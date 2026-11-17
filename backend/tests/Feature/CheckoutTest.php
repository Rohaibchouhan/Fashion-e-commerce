<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer1;
    protected User $customer2;
    protected string $token1;
    protected string $token2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(CouponSeeder::class);
        $this->seed(ShippingMethodSeeder::class);

        // Customer 1
        $this->customer1 = User::factory()->create([
            'name' => 'Alice Customer',
            'email' => 'alice@example.com',
            'status' => 'active',
        ]);
        $this->token1 = $this->customer1->createToken('test-token')->plainTextToken;

        // Customer 2
        $this->customer2 = User::factory()->create([
            'name' => 'Bob Customer',
            'email' => 'bob@example.com',
            'status' => 'active',
        ]);
        $this->token2 = $this->customer2->createToken('test-token')->plainTextToken;
    }

    // ==========================================
    // 1. Authentication Tests
    // ==========================================

    public function test_unauthenticated_checkout_without_customer_info_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->postJson('/api/v1/checkout/process', [
            'shippingAddress' => [
                'firstName' => 'Anonymous',
                'lastName' => 'User',
                'street' => '123 Ghost St',
                'city' => 'Anytown',
                'state' => 'NY',
                'zipCode' => '10001',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_customer_checkout_succeeds(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'alice@example.com');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer1->id,
            'payment_status' => PaymentStatus::PENDING->value,
            'status' => OrderStatus::PLACED->value,
        ]);
    }

    public function test_guest_checkout_with_customer_info_creates_account_and_places_order(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialStock = $variant->inventory->quantity;

        $response = $this->postJson('/api/v1/checkout/process', [
            'customer' => [
                'name' => 'Grace Hopper',
                'email' => 'grace.hopper@example.com',
                'phone' => '+15559876543',
            ],
            'shippingAddress' => [
                'firstName' => 'Grace',
                'lastName' => 'Hopper',
                'street' => '100 Computer History Way',
                'city' => 'Mountain View',
                'state' => 'CA',
                'zipCode' => '94043',
                'country' => 'US',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('data.is_new_customer'));
        $this->assertNotNull($response->json('data.token'));

        $this->assertDatabaseHas('users', ['email' => 'grace.hopper@example.com']);
        // Physical quantity is preserved on checkout; reserved_quantity is incremented
        $this->assertEquals($initialStock, $variant->inventory->fresh()->quantity);
        $this->assertEquals(2, $variant->inventory->fresh()->reserved_quantity);
        $this->assertEquals($initialStock - 2, $variant->fresh()->available_stock);
    }

    // ==========================================
    // 2. Addresses & Ownership Tests
    // ==========================================

    public function test_customer_can_select_existing_saved_address(): void
    {
        $address = Address::create([
            'user_id' => $this->customer1->id,
            'first_name' => 'Alice',
            'last_name' => 'Customer',
            'street' => '10 Downing St',
            'city' => 'London',
            'state' => 'Westminster',
            'zip_code' => 'SW1A 2AA',
            'country' => 'United Kingdom',
            'phone' => '+442079250918',
        ]);

        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shipping_address_id' => $address->id,
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $orderNumber = $response->json('data.order.orderNumber');
        $order = Order::where('order_number', $orderNumber)->first();

        $this->assertEquals('10 Downing St', $order->shipping_address['street']);
    }

    public function test_customer_cannot_use_another_customers_saved_address(): void
    {
        // Address belongs to Customer 2 (Bob)
        $bobsAddress = Address::create([
            'user_id' => $this->customer2->id,
            'first_name' => 'Bob',
            'last_name' => 'Customer',
            'street' => '999 Secret Hideout',
            'city' => 'Gotham',
            'state' => 'NJ',
            'zip_code' => '07001',
            'country' => 'United States',
        ]);

        $product = Product::first();
        $variant = $product->variants->first();

        // Customer 1 (Alice) attempts to pass Bob's address ID
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shipping_address_id' => $bobsAddress->id,
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_separate_billing_address_works_and_creates_independent_snapshot(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'billing_same_as_shipping' => false,
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Delivery',
                    'street' => '100 Shipping Way',
                    'city' => 'ShipCity',
                    'state' => 'CA',
                    'zipCode' => '90001',
                ],
                'billingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Billing',
                    'street' => '200 Bank Street',
                    'city' => 'FinanceCity',
                    'state' => 'NY',
                    'zipCode' => '10005',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $order = Order::where('order_number', $response->json('data.order.orderNumber'))->first();

        $this->assertEquals('100 Shipping Way', $order->shipping_address['street']);
        $this->assertEquals('200 Bank Street', $order->billing_address['street']);
    }

    public function test_editing_saved_address_does_not_modify_historical_order_snapshot(): void
    {
        $address = Address::create([
            'user_id' => $this->customer1->id,
            'first_name' => 'Alice',
            'last_name' => 'Original',
            'street' => '500 Old Lane',
            'city' => 'Pastville',
            'state' => 'TX',
            'zip_code' => '75001',
            'country' => 'United States',
        ]);

        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shipping_address_id' => $address->id,
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $order = Order::where('order_number', $response->json('data.order.orderNumber'))->first();
        $this->assertEquals('500 Old Lane', $order->shipping_address['street']);

        // Now customer updates their saved address
        $address->update(['street' => '999 Brand New Boulevard']);

        // Historical order snapshot MUST remain '500 Old Lane'
        $order->refresh();
        $this->assertEquals('500 Old Lane', $order->shipping_address['street']);
    }

    // ==========================================
    // 3. Cart & Pricing Integrity Tests
    // ==========================================

    public function test_empty_cart_checkout_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_inactive_product_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $product->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_inactive_variant_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_variant_belonging_to_another_product_is_rejected(): void
    {
        $productA = Product::first();
        $productB = Product::skip(1)->first();
        $variantB = $productB->variants->first();

        // Client maliciously pairs Product A with Variant belonging to Product B
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $productA->id, 'variantId' => $variantB->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_frontend_price_tampering_is_completely_ignored(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $authoritativePrice = (float) $variant->price;

        // Attacker attempts to submit price = 0.01
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    [
                        'productId' => $product->id,
                        'variantId' => $variant->id,
                        'quantity' => 1,
                        'price' => 0.01,
                    ],
                ],
            ]);

        $response->assertStatus(201);
        $order = Order::where('order_number', $response->json('data.order.orderNumber'))->first();

        $this->assertEquals($authoritativePrice, (float) $order->items->first()->unit_price);
        $this->assertEquals($authoritativePrice, (float) $order->subtotal);
    }

    public function test_insufficient_stock_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $available = $variant->available_stock;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => $available + 10],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ==========================================
    // 4. Coupons & Shipping Calculations
    // ==========================================

    public function test_expired_coupon_is_strictly_rejected(): void
    {
        $coupon = Coupon::create([
            'code' => 'EXPIRED50',
            'type' => 'percentage',
            'value' => 50,
            'is_active' => true,
            'end_date' => Carbon::now()->subDays(5),
        ]);

        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'coupon_code' => 'EXPIRED50',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_coupon_usage_limit_per_customer_is_enforced(): void
    {
        $coupon = Coupon::create([
            'code' => 'ONCEONLY',
            'type' => 'fixed',
            'value' => 10,
            'is_active' => true,
            'usage_limit_per_customer' => 1,
        ]);

        // Record 1 prior usage by Customer 1
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $this->customer1->id,
            'order_number' => 'WD-HISTORIC-1',
            'discount_amount' => 10,
        ]);

        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'coupon_code' => 'ONCEONLY',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_coupon_min_spend_requirement_is_enforced(): void
    {
        $coupon = Coupon::create([
            'code' => 'BIGSPENDER',
            'type' => 'fixed',
            'value' => 20,
            'min_spend' => 500,
            'is_active' => true,
        ]);

        $product = Product::first();
        $variant = $product->variants->first();

        // 1 item does not reach $500
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'coupon_code' => 'BIGSPENDER',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_free_shipping_threshold_applied_when_subtotal_reaches_50(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['price' => 60.00]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingMethodId' => 'standard',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertEquals(0.00, (float) $response->json('data.order.shipping'));
    }

    public function test_flat_shipping_applied_when_subtotal_is_below_50(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['price' => 30.00]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingMethodId' => 'standard',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertEquals(10.00, (float) $response->json('data.order.shipping'));
    }

    // ==========================================
    // 5. Inventory & Transaction Safety Tests
    // ==========================================

    public function test_successful_checkout_clears_customer_cart(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Customer has items in cart
        $cart = Cart::create(['user_id' => $this->customer1->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->price,
            'subtotal' => $variant->price,
        ]);

        $this->assertEquals(1, $cart->items()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '123 Main St',
                    'city' => 'City',
                    'state' => 'ST',
                    'zipCode' => '12345',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertEquals(0, $cart->fresh()->items()->count());
    }

    public function test_failed_checkout_preserves_customer_cart(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $cart = Cart::create(['user_id' => $this->customer1->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->price,
            'subtotal' => $variant->price,
        ]);

        // Checkout fails due to missing address details
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        // Cart must remain intact
        $this->assertEquals(1, $cart->fresh()->items()->count());
    }

    public function test_checkout_idempotency_prevents_duplicate_orders(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'unique-checkout-idempotency-' . Str::random(10);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'customer' => [
                'name' => 'Alan Turing',
                'email' => 'alan.turing@example.com',
                'phone' => '+15551112233',
            ],
            'shippingAddress' => [
                'firstName' => 'Alan',
                'lastName' => 'Turing',
                'street' => 'Bletchley Park',
                'city' => 'Milton Keynes',
                'state' => 'Bucks',
                'zipCode' => 'MK3 6EB',
                'country' => 'UK',
            ],
            'items' => [
                [
                    'productId' => $product->id,
                    'variantId' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ];

        // 1st request
        $res1 = $this->postJson('/api/v1/checkout/process', $payload);
        $res1->assertStatus(201);
        $orderNumber1 = $res1->json('data.order.orderNumber');

        // 2nd request with exact same idempotency key
        $res2 = $this->postJson('/api/v1/checkout/process', $payload);
        $res2->assertStatus(201);
        $orderNumber2 = $res2->json('data.order.orderNumber');

        $this->assertEquals($orderNumber1, $orderNumber2);
        $this->assertEquals(1, Order::where('idempotency_key', $idempotencyKey)->count());
    }

    // ==========================================
    // 6. Security & Isolation Tests
    // ==========================================

    public function test_customer_cannot_access_another_customers_order(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Customer 1 places order
        $order = Order::create([
            'order_number' => 'WD-SEC-101',
            'customer_id' => $this->customer1->id,
            'status' => OrderStatus::PLACED,
            'payment_status' => PaymentStatus::PENDING,
            'fulfillment_status' => \App\Enums\FulfillmentStatus::UNFULFILLED,
            'subtotal' => 50.00,
            'total' => 60.00,
            'shipping_address' => ['street' => '123 Main St'],
            'billing_address' => ['street' => '123 Main St'],
            'payment_method' => 'Credit Card',
        ]);

        // Customer 2 attempts to fetch Customer 1's order
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token2)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_checkout_preview_endpoint_returns_authoritative_calculations(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $variant->update(['price' => 100.00]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/preview', [
                'shippingMethodId' => 'standard',
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(100.0, (float) $response->json('data.subtotal'));
        $this->assertEquals(0.0, (float) $response->json('data.shipping'));
        $this->assertEquals(8.0, (float) $response->json('data.tax'));
        $this->assertEquals(108.0, (float) $response->json('data.total'));
    }

    // ==========================================
    // 7. Security Hardening & Audit Verification Tests
    // ==========================================

    public function test_unauthenticated_checkout_with_existing_email_is_rejected_and_requires_auth(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Alice is an existing registered user ($this->customer1->email)
        $response = $this->postJson('/api/v1/checkout/process', [
            'customer' => [
                'name' => 'Alice Impersonator',
                'email' => $this->customer1->email,
                'phone' => '+15559998877',
            ],
            'shippingAddress' => [
                'firstName' => 'Alice',
                'lastName' => 'Impersonator',
                'street' => '999 Rogue Road',
                'city' => 'Anytown',
                'state' => 'NY',
                'zipCode' => '10001',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'requires_auth' => true,
        ]);
        $this->assertNull($response->json('data.token'));
        $this->assertStringContainsString('sign in', $response->json('message'));
    }

    public function test_guest_checkout_account_takeover_does_not_leak_customer_token_or_profile(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->postJson('/api/v1/checkout/process', [
            'customer' => [
                'name' => 'Alice Customer',
                'email' => $this->customer1->email,
            ],
            'shippingAddress' => [
                'firstName' => 'Alice',
                'lastName' => 'Customer',
                'street' => '123 Fake St',
                'city' => 'Metropolis',
                'state' => 'NY',
                'zipCode' => '10001',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertNull($response->json('data.token'));
        $this->assertNull($response->json('data.user'));
    }

    public function test_checkout_reserves_stock_without_deducting_physical_inventory(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;
        $initialReserved = $variant->inventory->reserved_quantity;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 3],
                ],
            ]);

        $response->assertStatus(201);
        $orderId = $response->json('data.order.id');

        $inv = $variant->inventory->fresh();
        // Physical stock remains unchanged on pending checkout
        $this->assertEquals($initialPhysical, $inv->quantity);
        // Reserved quantity is incremented by 3
        $this->assertEquals($initialReserved + 3, $inv->reserved_quantity);
        // Available stock is correctly reduced
        $this->assertEquals($initialPhysical - ($initialReserved + 3), $variant->fresh()->available_stock);

        // Verify reservation record
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $orderId,
            'variant_id' => $variant->id,
            'quantity' => 3,
            'status' => 'reserved',
        ]);
    }

    public function test_verified_payment_confirmation_commits_reservation_and_deducts_stock_exactly_once(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2],
                ],
            ]);

        $orderId = $response->json('data.order.id');
        $order = Order::findOrFail($orderId);

        // Confirm payment via PaymentService
        $paymentService = app(\App\Services\PaymentService::class);
        $paymentService->confirmPayment($order, 'TXN_GATEWAY_SUCCESS_123', 'stripe');

        $order->refresh();
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);

        $inv = $variant->inventory->fresh();
        // Now physical stock is permanently deducted
        $this->assertEquals($initialPhysical - 2, $inv->quantity);
        // Reserved quantity is cleared back to 0
        $this->assertEquals(0, $inv->reserved_quantity);

        // Stock reservation status is committed
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $orderId,
            'variant_id' => $variant->id,
            'status' => 'committed',
        ]);
    }

    public function test_duplicate_payment_confirmation_is_idempotent_and_does_not_deduct_stock_twice(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2],
                ],
            ]);

        $order = Order::findOrFail($response->json('data.order.id'));
        $paymentService = app(\App\Services\PaymentService::class);

        // Confirm 1st time
        $paymentService->confirmPayment($order, 'TXN_IDEMPOTENT_1', 'stripe');
        $this->assertEquals($initialPhysical - 2, $variant->inventory->fresh()->quantity);

        // Confirm 2nd time (duplicate webhook / gateway retry)
        $paymentService->confirmPayment($order, 'TXN_IDEMPOTENT_1', 'stripe');
        // Stock must NOT be deducted twice!
        $this->assertEquals($initialPhysical - 2, $variant->inventory->fresh()->quantity);
    }

    public function test_order_cancellation_releases_reservation_without_altering_physical_stock(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2],
                ],
            ]);

        $order = Order::findOrFail($response->json('data.order.id'));
        $orderService = app(\App\Services\OrderService::class);

        // Customer cancels unpaid pending order
        $orderService->cancelOrder($order, 'Changed my mind', $this->customer1);

        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        $inv = $variant->inventory->fresh();
        // Physical stock remains unchanged (not inflated!)
        $this->assertEquals($initialPhysical, $inv->quantity);
        // Reserved quantity released back to 0
        $this->assertEquals(0, $inv->reserved_quantity);
        // Reservation record marked released
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
    }

    public function test_expired_pending_orders_are_automatically_cancelled_and_reservations_released(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2],
                ],
            ]);

        $order = Order::findOrFail($response->json('data.order.id'));

        // Manually simulate order placed 65 minutes ago
        $order->created_at = Carbon::now()->subMinutes(65);
        $order->save();

        $this->assertEquals(2, $variant->inventory->fresh()->reserved_quantity);

        // Run scheduled command
        \Illuminate\Support\Facades\Artisan::call('ecommerce:cancel-expired-orders');

        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertEquals(0, $variant->inventory->fresh()->reserved_quantity);
        $this->assertEquals($initialPhysical, $variant->inventory->fresh()->quantity);
    }

    public function test_product_restricted_coupon_applies_discount_only_to_eligible_products(): void
    {
        $products = Product::with('variants')->take(2)->get();
        $productA = $products[0];
        $variantA = $productA->variants->first();
        $variantA->update(['price' => 100.00]);

        $productB = $products[1];
        $variantB = $productB->variants->first();
        $variantB->update(['price' => 50.00]);

        // Create coupon restricted ONLY to Product A
        $coupon = Coupon::create([
            'code' => 'ONLYPRODA',
            'type' => 'percentage',
            'value' => 20.00, // 20% off
            'is_active' => true,
        ]);
        $coupon->products()->attach($productA->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'couponCode' => 'ONLYPRODA',
                'items' => [
                    ['productId' => $productA->id, 'variantId' => $variantA->id, 'quantity' => 1],
                    ['productId' => $productB->id, 'variantId' => $variantB->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        // Subtotal = $100 + $50 = $150
        // Discount is 20% of Product A ($100) = $20, NOT 20% of $150 ($30)
        $this->assertEquals(20.00, (float) $response->json('data.order.discount'));
        $this->assertEquals(150.00, (float) $response->json('data.order.subtotal'));
    }

    public function test_product_restricted_coupon_fails_if_cart_has_no_eligible_products(): void
    {
        $products = Product::with('variants')->take(2)->get();
        $productA = $products[0];
        $productB = $products[1];
        $variantB = $productB->variants->first();

        $coupon = Coupon::create([
            'code' => 'ONLYPRODA2',
            'type' => 'fixed',
            'value' => 10.00,
            'is_active' => true,
        ]);
        $coupon->products()->attach($productA->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'couponCode' => 'ONLYPRODA2',
                'items' => [
                    ['productId' => $productB->id, 'variantId' => $variantB->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('not applicable to any items', $response->json('message'));
    }

    public function test_category_restricted_coupon_applies_discount_only_to_eligible_category(): void
    {
        $products = Product::with('variants')->take(2)->get();
        $productA = $products[0];
        $variantA = $productA->variants->first();
        $variantA->update(['price' => 80.00]);

        $productB = $products[1];
        $otherCategory = \App\Models\Category::where('id', '!=', $productA->category_id)->first();
        if ($otherCategory) {
            $productB->update(['category_id' => $otherCategory->id]);
        }
        $variantB = $productB->variants->first();
        $variantB->update(['price' => 50.00]);

        $coupon = Coupon::create([
            'code' => 'CATRESTRICTED',
            'type' => 'percentage',
            'value' => 25.00, // 25% off
            'is_active' => true,
        ]);
        $coupon->categories()->attach($productA->category_id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'couponCode' => 'CATRESTRICTED',
                'items' => [
                    ['productId' => $productA->id, 'variantId' => $variantA->id, 'quantity' => 1],
                    ['productId' => $productB->id, 'variantId' => $variantB->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        // 25% of Product A ($80) = $20
        $this->assertEquals(20.00, (float) $response->json('data.order.discount'));
    }

    public function test_concurrent_coupon_usage_respects_usage_limit(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $coupon = Coupon::create([
            'code' => 'ONETIMEUSE',
            'type' => 'fixed',
            'value' => 15.00,
            'usage_limit_total' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        // First checkout succeeds
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'couponCode' => 'ONETIMEUSE',
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $res1->assertStatus(201);
        $this->assertEquals(1, $coupon->fresh()->used_count);

        // Second checkout fails
        auth()->forgetGuards();
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->token2)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Bob',
                    'lastName' => 'Customer',
                    'street' => '123 Fake Street',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'couponCode' => 'ONETIMEUSE',
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $res2->assertStatus(422);
        $this->assertStringContainsString('maximum total usage limit', $res2->json('message'));
    }

    public function test_same_idempotency_key_with_different_payload_returns_409_conflict(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'conflict-key-' . Str::random(12);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'shippingAddress' => [
                'firstName' => 'Alice',
                'lastName' => 'Customer',
                'street' => '742 Evergreen Terrace',
                'city' => 'Springfield',
                'state' => 'OR',
                'zipCode' => '97477',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
            ],
        ];

        // 1st request succeeds
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', $payload);
        $res1->assertStatus(201);

        // 2nd request uses the SAME idempotency key but with CHANGED items (quantity = 2)
        $modifiedPayload = $payload;
        $modifiedPayload['items'][0]['quantity'] = 2;

        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', $modifiedPayload);

        $res2->assertStatus(409);
        $res2->assertJson([
            'success' => false,
            'error_type' => 'idempotency_conflict',
        ]);
        $this->assertStringContainsString('previously used with different order details', $res2->json('message'));
    }

    public function test_idempotency_key_cannot_be_replayed_by_another_customer(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'customer-isolation-' . Str::random(12);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'shippingAddress' => [
                'firstName' => 'Alice',
                'lastName' => 'Customer',
                'street' => '742 Evergreen Terrace',
                'city' => 'Springfield',
                'state' => 'OR',
                'zipCode' => '97477',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
            ],
        ];

        // Customer 1 places order
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', $payload);
        $res1->assertStatus(201);

        // Customer 2 attempts to use Customer 1's idempotency key
        auth()->forgetGuards();
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->token2)
            ->postJson('/api/v1/checkout/process', $payload);

        $res2->assertStatus(422);
        $this->assertStringContainsString('Access denied', $res2->json('message'));
    }

    public function test_idempotency_key_cannot_be_replayed_by_another_guest_session(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'guest-isolation-' . Str::random(12);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'customer' => [
                'name' => 'Charlie Guest',
                'email' => 'charlie.guest@example.com',
                'phone' => '+15554443322',
            ],
            'shippingAddress' => [
                'firstName' => 'Charlie',
                'lastName' => 'Guest',
                'street' => '123 Harbor View',
                'city' => 'Seattle',
                'state' => 'WA',
                'zipCode' => '98101',
            ],
            'items' => [
                ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
            ],
        ];

        // Guest 1 places order with session id sess_alpha
        $res1 = $this->withHeader('X-Session-Id', 'sess_alpha')
            ->postJson('/api/v1/checkout/process', $payload);
        $res1->assertStatus(201);

        // Guest 2 attempts to use Guest 1's idempotency key with different session id sess_beta
        $res2 = $this->withHeader('X-Session-Id', 'sess_beta')
            ->postJson('/api/v1/checkout/process', $payload);

        $res2->assertStatus(422);
        $this->assertStringContainsString('Access denied', $res2->json('message'));
    }

    public function test_unknown_shipping_method_code_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingMethodId' => 'hyperloop_instant',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Shipping method', $response->json('message'));
    }

    public function test_inactive_shipping_method_is_rejected(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Deactivate standard shipping method
        $shipping = \App\Models\ShippingMethod::where('code', 'standard')->first();
        $shipping->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token1)
            ->postJson('/api/v1/checkout/process', [
                'shippingMethodId' => 'standard',
                'shippingAddress' => [
                    'firstName' => 'Alice',
                    'lastName' => 'Customer',
                    'street' => '742 Evergreen Terrace',
                    'city' => 'Springfield',
                    'state' => 'OR',
                    'zipCode' => '97477',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('currently unavailable', $response->json('message'));
    }
}
