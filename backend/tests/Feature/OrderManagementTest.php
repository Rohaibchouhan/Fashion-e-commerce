<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
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

        $customerRole = Role::where('slug', 'customer')->first();

        $this->customer1 = User::create([
            'name' => 'Customer One',
            'email' => 'cust1@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer1->roles()->sync([$customerRole->id]);
        $this->token1 = $this->customer1->createToken('test')->plainTextToken;

        $this->customer2 = User::create([
            'name' => 'Customer Two',
            'email' => 'cust2@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer2->roles()->sync([$customerRole->id]);
        $this->token2 = $this->customer2->createToken('test')->plainTextToken;
    }

    protected function createSampleOrder(User $customer, OrderStatus $status = OrderStatus::PLACED): Order
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $order = Order::create([
            'order_number' => 'WD-' . rand(10000, 99999),
            'customer_id' => $customer->id,
            'status' => $status,
            'payment_status' => PaymentStatus::PAID,
            'fulfillment_status' => FulfillmentStatus::UNFULFILLED,
            'subtotal' => 50.00,
            'discount' => 0.00,
            'shipping' => 10.00,
            'tax' => 4.00,
            'total' => 64.00,
            'shipping_method_id' => 'standard',
            'carrier' => 'Standard Ground',
            'tracking_number' => 'WD-TRK-' . rand(1000, 9999),
            'shipping_address' => ['street' => '123 Main St', 'city' => 'Anytown', 'zip' => '12345'],
            'billing_address' => ['street' => '123 Main St', 'city' => 'Anytown', 'zip' => '12345'],
            'payment_method' => 'Credit Card',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $variant->sku,
            'product_slug' => $product->slug,
            'unit_price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
            'total' => 50.00,
        ]);

        return $order;
    }

    public function test_customer_can_list_own_orders(): void
    {
        $this->createSampleOrder($this->customer1);
        $this->createSampleOrder($this->customer1);
        $this->createSampleOrder($this->customer2); // Belongs to customer 2

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $order2 = $this->createSampleOrder($this->customer2);

        // Customer 1 tries to view Customer 2's order
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/v1/orders/{$order2->order_number}");

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_order_tracking_endpoint_returns_status_and_timeline(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PROCESSING);

        // Without email_or_zip verification credential, tracking is rejected
        $unauthorized = $this->getJson("/api/v1/orders/{$order->order_number}/track");
        $unauthorized->assertStatus(403);

        // With valid verification credential
        $response = $this->getJson("/api/v1/orders/{$order->order_number}/track?email_or_zip={$this->customer1->email}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'tracking_number',
                    'carrier',
                ],
            ]);

        $this->assertEquals(OrderStatus::PROCESSING->value, $response->json('data.status'));
    }

    public function test_customer_can_cancel_placed_order_and_restore_inventory(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);
        $item = $order->items->first();
        $variant = $item->variant;
        $initialStock = $variant->inventory->quantity;

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson("/api/v1/orders/{$order->order_number}/cancel", [
                'reason' => 'Changed my mind before dispatch',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // Verify inventory restoration
        $variant->inventory->refresh();
        $this->assertEquals($initialStock + 1, $variant->inventory->quantity);
    }

    public function test_customer_cannot_cancel_shipped_order(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::DISPATCHED);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson("/api/v1/orders/{$order->order_number}/cancel", [
                'reason' => 'Too late to cancel',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $order->refresh();
        $this->assertEquals(OrderStatus::DISPATCHED, $order->status);
    }
}
