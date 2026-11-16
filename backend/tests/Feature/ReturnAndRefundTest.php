<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\ReturnService;
use Carbon\Carbon;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnAndRefundTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $adminUser;
    protected string $customerToken;
    protected ReturnService $returnService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);

        $customerRole = Role::where('slug', 'customer')->first();
        $adminRole = Role::where('slug', 'super_admin')->first();

        $this->customer = User::create([
            'name' => 'Return Customer',
            'email' => 'ret.customer@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);
        $this->customerToken = $this->customer->createToken('test')->plainTextToken;

        $this->adminUser = User::create([
            'name' => 'Admin Specialist',
            'email' => 'staff@wearday.com',
            'password' => bcrypt('Admin1234!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->adminUser->roles()->sync([$adminRole->id]);

        $this->returnService = app(ReturnService::class);
    }

    protected function createDeliveredOrder(int $daysAgo = 5): Order
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $order = Order::create([
            'order_number' => 'WD-' . rand(10000, 99999),
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'fulfillment_status' => FulfillmentStatus::FULFILLED,
            'subtotal' => 60.00,
            'discount' => 0.00,
            'shipping' => 0.00,
            'tax' => 4.80,
            'total' => 64.80,
            'shipping_method_id' => 'standard',
            'carrier' => 'Standard Ground',
            'tracking_number' => 'WD-TRK-8821',
            'shipping_address' => ['street' => '123 Return St', 'city' => 'Anytown', 'zip' => '12345'],
            'billing_address' => ['street' => '123 Return St', 'city' => 'Anytown', 'zip' => '12345'],
            'payment_method' => 'Credit Card',
        ]);

        $order->created_at = Carbon::now()->subDays($daysAgo);
        $order->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $variant->sku,
            'product_slug' => $product->slug,
            'unit_price' => 60.00,
            'quantity' => 1,
            'subtotal' => 60.00,
            'total' => 60.00,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'card',
            'transaction_id' => 'TXN-' . rand(1000, 9999),
            'amount' => 64.80,
            'status' => 'paid',
        ]);

        return $order;
    }

    public function test_customer_can_submit_return_within_eligibility_window(): void
    {
        $order = $this->createDeliveredOrder(5); // 5 days ago (window is 30 days)
        $item = $order->items->first();

        $response = $this->withHeader('Authorization', "Bearer {$this->customerToken}")
            ->postJson('/api/v1/returns', [
                'order_id' => $order->id,
                'resolution' => 'refund',
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity' => 1,
                        'reason' => 'wrong_size',
                        'comment' => 'Slightly too small around shoulders.',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'orderNumber',
                    'status',
                    'refundAmount',
                ],
            ]);

        $this->assertDatabaseHas('returns', [
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'status' => ReturnStatus::REQUESTED->value,
        ]);
    }

    public function test_customer_cannot_return_order_older_than_30_days(): void
    {
        $order = $this->createDeliveredOrder(45); // 45 days ago!
        $item = $order->items->first();

        $response = $this->withHeader('Authorization', "Bearer {$this->customerToken}")
            ->postJson('/api/v1/returns', [
                'order_id' => $order->id,
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity' => 1,
                        'reason' => 'changed_mind',
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_admin_quality_inspection_restocks_resellable_item(): void
    {
        $order = $this->createDeliveredOrder(3);
        $item = $order->items->first();
        $variant = $item->variant;
        $initialStock = $variant->inventory->quantity;

        // Create return request
        $returnRequest = $this->returnService->createReturnRequest($this->customer, [
            'order_id' => $order->id,
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1, 'reason' => 'wrong_size'],
            ],
        ]);

        $returnItem = $returnRequest->items->first();

        // Admin completes inspection as resellable
        $this->returnService->completeInspection(
            $returnRequest,
            [$returnItem->id => ['condition' => 'resellable']],
            $this->adminUser->id
        );

        $variant->inventory->refresh();
        $this->assertEquals($initialStock + 1, $variant->inventory->quantity);

        $returnItem->refresh();
        $this->assertEquals('resellable', $returnItem->inspection_result);
        $this->assertEquals(1, $returnItem->restocked_quantity);
    }

    public function test_admin_quality_inspection_does_not_restock_damaged_item(): void
    {
        $order = $this->createDeliveredOrder(3);
        $item = $order->items->first();
        $variant = $item->variant;
        $initialStock = $variant->inventory->quantity;

        $returnRequest = $this->returnService->createReturnRequest($this->customer, [
            'order_id' => $order->id,
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1, 'reason' => 'damaged_defective'],
            ],
        ]);

        $returnItem = $returnRequest->items->first();

        // Admin marks item damaged
        $this->returnService->completeInspection(
            $returnRequest,
            [$returnItem->id => ['condition' => 'damaged']],
            $this->adminUser->id
        );

        // Inventory must NOT be restored!
        $variant->inventory->refresh();
        $this->assertEquals($initialStock, $variant->inventory->quantity);

        $returnItem->refresh();
        $this->assertEquals('damaged', $returnItem->inspection_result);
        $this->assertEquals(0, $returnItem->restocked_quantity);
    }
}
