<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
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
        $this->seed(ShippingMethodSeeder::class);
    }

    public function test_guest_checkout_automatically_creates_account_and_places_order(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialStock = $variant->inventory->quantity;

        $idempotencyKey = Str::uuid()->toString();

        $response = $this->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/checkout/process', [
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
                    'zip' => '94043',
                    'country' => 'US',
                ],
                'items' => [
                    [
                        'productId' => $product->id,
                        'variantId' => $variant->id,
                        'quantity' => 2,
                    ],
                ],
                'shippingMethodId' => 'standard',
                'paymentMethod' => [
                    'type' => 'card',
                    'cardNumber' => '4242424242424242',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => ['id', 'orderNumber', 'total', 'status'],
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'is_new_customer',
                ],
            ]);

        $this->assertTrue($response->json('data.is_new_customer'));
        $this->assertNotNull($response->json('data.token'));

        // Verify customer user was created
        $this->assertDatabaseHas('users', [
            'email' => 'grace.hopper@example.com',
            'name' => 'Grace Hopper',
        ]);

        // Verify inventory was deducted by 2 units
        $variant->inventory->refresh();
        $this->assertEquals($initialStock - 2, $variant->inventory->quantity);

        // Verify order exists in DB
        $orderNumber = $response->json('data.order.orderNumber');
        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
        ]);
    }

    public function test_checkout_idempotency_prevents_duplicate_orders(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'test-idempotency-key-999';

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
                'zip' => 'MK3 6EB',
                'country' => 'UK',
            ],
            'items' => [
                [
                    'productId' => $product->id,
                    'variantId' => $variant->id,
                    'quantity' => 1,
                ],
            ],
            'paymentMethod' => ['type' => 'card'],
        ];

        // 1st request
        $res1 = $this->postJson('/api/v1/checkout/process', $payload);
        $res1->assertStatus(201);
        $orderNumber1 = $res1->json('data.order.orderNumber');

        // 2nd request with exact same idempotency key
        $res2 = $this->postJson('/api/v1/checkout/process', $payload);
        $res2->assertStatus(201);
        $orderNumber2 = $res2->json('data.order.orderNumber');

        // Must return the exact same order and NOT create a new duplicate order
        $this->assertEquals($orderNumber1, $orderNumber2);
        $this->assertEquals(1, Order::where('idempotency_key', $idempotencyKey)->count());
    }
}

