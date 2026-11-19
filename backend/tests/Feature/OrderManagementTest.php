<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
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
    protected User $adminUser;
    protected User $orderManager;
    protected User $inventoryManager;
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

        $adminRole = Role::where('slug', 'admin')->first();
        $this->adminUser = User::create([
            'name' => 'Store Admin',
            'email' => 'admin_orders@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->adminUser->roles()->sync([$adminRole->id]);

        $orderManagerRole = Role::where('slug', 'order_manager')->first();
        $this->orderManager = User::create([
            'name' => 'Order Manager',
            'email' => 'ordermanager@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->orderManager->roles()->sync([$orderManagerRole->id]);

        $inventoryManagerRole = Role::where('slug', 'inventory_manager')->first();
        $this->inventoryManager = User::create([
            'name' => 'Inventory Manager',
            'email' => 'invmanager@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->inventoryManager->roles()->sync([$inventoryManagerRole->id]);
    }

    protected function createSampleOrder(
        User $customer,
        OrderStatus $status = OrderStatus::PLACED,
        PaymentStatus $paymentStatus = PaymentStatus::PAID,
        float $total = 64.00,
        bool $isTest = false
    ): Order {
        $product = Product::first();
        $variant = $product->variants->first();

        $order = Order::create([
            'order_number' => 'WD-' . rand(10000, 99999),
            'customer_id' => $customer->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => FulfillmentStatus::UNFULFILLED,
            'subtotal' => 50.00,
            'discount' => 0.00,
            'shipping' => 10.00,
            'tax' => 4.00,
            'total' => $total,
            'shipping_method_id' => 'standard',
            'carrier' => 'Standard Ground',
            'tracking_number' => 'WD-TRK-' . rand(1000, 9999),
            'shipping_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'NY',
                'zipCode' => '12345',
                'country' => 'United States',
                'phone' => '+15551234567',
            ],
            'billing_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'street' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'NY',
                'zipCode' => '12345',
                'country' => 'United States',
                'phone' => '+15551234567',
            ],
            'payment_method' => 'Credit Card',
            'payment_last4' => '8821',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $variant->sku,
            'product_slug' => $product->slug,
            'selected_size' => $variant->size,
            'selected_color' => $variant->color,
            'unit_price' => 50.00,
            'quantity' => 1,
            'subtotal' => 50.00,
            'total' => 50.00,
        ]);

        // Create payment record
        Payment::create([
            'order_id' => $order->id,
            'payment_method' => $isTest ? 'test' : 'card',
            'transaction_id' => 'txn_' . rand(100000, 999999),
            'amount' => $total,
            'currency' => 'USD',
            'status' => $paymentStatus->value,
            'is_test' => $isTest,
        ]);

        return $order;
    }

    // Customer API Tests
    public function test_customer_can_list_own_orders(): void
    {
        $this->createSampleOrder($this->customer1);
        $this->createSampleOrder($this->customer1);
        $this->createSampleOrder($this->customer2);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $order2 = $this->createSampleOrder($this->customer2);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->getJson("/api/v1/orders/{$order2->order_number}");

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_order_tracking_endpoint_returns_status_and_timeline(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PROCESSING);

        $unauthorized = $this->getJson("/api/v1/orders/{$order->order_number}/track");
        $unauthorized->assertStatus(403);

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

    public function test_post_order_track_succeeds_with_customer_email_and_postal_code(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::DISPATCHED);

        // 1. Look up with customer email
        $resEmail = $this->postJson('/api/v1/orders/track', [
            'order_number' => $order->order_number,
            'email_or_zip' => $this->customer1->email,
        ]);
        $resEmail->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'orderNumber' => $order->order_number,
                ],
            ]);

        // 2. Look up with postal code / zip
        $resZip = $this->postJson('/api/v1/orders/track', [
            'order_number' => $order->order_number,
            'email_or_zip' => $order->shipping_address['zipCode'] ?? $order->shipping_address['postal_code'],
        ]);
        $resZip->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'orderNumber' => $order->order_number,
                ],
            ]);

        // 3. Look up with invalid email returns 404
        $resInvalid = $this->postJson('/api/v1/orders/track', [
            'order_number' => $order->order_number,
            'email_or_zip' => 'wrong.email@example.com',
        ]);
        $resInvalid->assertStatus(404);
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

    // Admin Order Management Tests
    public function test_admin_can_view_order_listing_with_pagination(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->createSampleOrder($this->customer1);
        }

        $res = $this->actingAs($this->adminUser)->get('/admin/orders');

        $res->assertStatus(200);
        $res->assertViewIs('admin.orders.index');
        $res->assertViewHas('orders');
        $orders = $res->viewData('orders');
        $this->assertEquals(15, $orders->count());
        $this->assertEquals(20, $orders->total());
    }

    public function test_admin_can_search_orders_by_number_tracking_and_customer(): void
    {
        $order = $this->createSampleOrder($this->customer1);

        // Search by order number
        $resNum = $this->actingAs($this->adminUser)->get('/admin/orders?search=' . $order->order_number);
        $resNum->assertStatus(200);
        $resNum->assertSee($order->order_number);

        // Search by customer name
        $resCust = $this->actingAs($this->adminUser)->get('/admin/orders?search=' . urlencode($this->customer1->name));
        $resCust->assertStatus(200);
        $resCust->assertSee($order->order_number);

        // Search by tracking number
        $resTrk = $this->actingAs($this->adminUser)->get('/admin/orders?search=' . $order->tracking_number);
        $resTrk->assertStatus(200);
        $resTrk->assertSee($order->order_number);
    }

    public function test_admin_can_filter_orders_by_status_payment_type_and_date_range(): void
    {
        $placedOrder = $this->createSampleOrder($this->customer1, OrderStatus::PLACED, PaymentStatus::PAID, 100.00, false);
        $deliveredOrder = $this->createSampleOrder($this->customer2, OrderStatus::DELIVERED, PaymentStatus::PAID, 200.00, false);
        $testOrder = $this->createSampleOrder($this->customer1, OrderStatus::CONFIRMED, PaymentStatus::PENDING, 50.00, true);

        // Filter by status
        $resStatus = $this->actingAs($this->adminUser)->get('/admin/orders?status=delivered');
        $resStatus->assertSee($deliveredOrder->order_number);
        $resStatus->assertDontSee($placedOrder->order_number);

        // Filter by payment status
        $resPay = $this->actingAs($this->adminUser)->get('/admin/orders?payment_status=pending');
        $resPay->assertSee($testOrder->order_number);
        $resPay->assertDontSee($placedOrder->order_number);

        // Filter by type: real vs test
        $resReal = $this->actingAs($this->adminUser)->get('/admin/orders?type=real');
        $resReal->assertSee($placedOrder->order_number);
        $resReal->assertDontSee($testOrder->order_number);

        $resTest = $this->actingAs($this->adminUser)->get('/admin/orders?type=test');
        $resTest->assertSee($testOrder->order_number);
        $resTest->assertDontSee($placedOrder->order_number);

        // Filter by date range
        $resDate = $this->actingAs($this->adminUser)->get('/admin/orders?date_from=' . now()->subDay()->toDateString() . '&date_to=' . now()->addDay()->toDateString());
        $resDate->assertSee($placedOrder->order_number);
    }

    public function test_admin_can_sort_orders_by_oldest_and_total(): void
    {
        $cheapOrder = $this->createSampleOrder($this->customer1, OrderStatus::PLACED, PaymentStatus::PAID, 20.00);
        $expensiveOrder = $this->createSampleOrder($this->customer2, OrderStatus::PLACED, PaymentStatus::PAID, 500.00);

        $resSortHigh = $this->actingAs($this->adminUser)->get('/admin/orders?sort=total_high');
        $orders = $resSortHigh->viewData('orders');
        $this->assertEquals($expensiveOrder->id, $orders->first()->id);

        $resSortLow = $this->actingAs($this->adminUser)->get('/admin/orders?sort=total_low');
        $ordersLow = $resSortLow->viewData('orders');
        $this->assertEquals($cheapOrder->id, $ordersLow->first()->id);
    }

    public function test_admin_can_view_order_details_page_with_snapshots_and_financials(): void
    {
        $order = $this->createSampleOrder($this->customer1);

        $res = $this->actingAs($this->adminUser)->get("/admin/orders/{$order->id}");

        $res->assertStatus(200);
        $res->assertSee($order->order_number);
        $res->assertSee($this->customer1->name);
        $res->assertSee($this->customer1->email);
        $res->assertSee('Shipping Address (Historical Snapshot)');
        $res->assertSee('Billing Address (Historical Snapshot)');
        $res->assertSee('Payment Records');
        $res->assertSee('Advance Order Status');
    }

    // State Machine & Transition Tests
    public function test_valid_status_transitions_succeed_and_record_history(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);

        // 1. Placed -> Confirmed
        $res1 = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CONFIRMED->value,
            'notes' => 'Order verified by staff',
        ]);
        $res1->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);

        // 2. Confirmed -> Processing
        $res2 = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::PROCESSING->value,
        ]);
        $res2->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);

        // 3. Processing -> Packed
        $res3 = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::PACKED->value,
        ]);
        $res3->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals(OrderStatus::PACKED, $order->status);
        $this->assertEquals(FulfillmentStatus::PARTIALLY_FULFILLED, $order->fulfillment_status);

        // 4. Packed -> Dispatched
        $res4 = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::DISPATCHED->value,
            'carrier' => 'FedEx Express',
            'tracking_number' => '123456789012',
        ]);
        $res4->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals(OrderStatus::DISPATCHED, $order->status);
        $this->assertEquals('FedEx Express', $order->carrier);
        $this->assertEquals('123456789012', $order->tracking_number);

        // History count must be at least 4
        $this->assertGreaterThanOrEqual(4, $order->statusHistories()->count());
    }

    public function test_invalid_status_transitions_are_strictly_rejected(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);

        // Cannot skip directly from Placed to Delivered
        $res = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::DELIVERED->value,
        ]);

        $res->assertSessionHas('error');
        $order->refresh();
        $this->assertEquals(OrderStatus::PLACED, $order->status);
    }

    public function test_terminal_statuses_cannot_transition(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::DELIVERED);

        // Delivered order cannot transition to Processing
        $res = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::PROCESSING->value,
        ]);
        $res->assertSessionHas('error');
        $order->refresh();
        $this->assertEquals(OrderStatus::DELIVERED, $order->status);

        // Cancelled order cannot transition to Dispatched
        $cancelledOrder = $this->createSampleOrder($this->customer1, OrderStatus::CANCELLED);
        $resCancel = $this->actingAs($this->adminUser)->post("/admin/orders/{$cancelledOrder->id}/status", [
            'status' => OrderStatus::DISPATCHED->value,
        ]);
        $resCancel->assertSessionHas('error');
        $cancelledOrder->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $cancelledOrder->status);
    }

    public function test_updating_status_does_not_modify_payment_status(): void
    {
        // Order with unpaid/pending payment status
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED, PaymentStatus::PENDING);
        $this->assertEquals(PaymentStatus::PENDING, $order->payment_status);

        // Advance fulfillment status to Confirmed
        $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CONFIRMED->value,
        ]);

        $order->refresh();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        // Payment status must strictly remain PENDING
        $this->assertEquals(PaymentStatus::PENDING, $order->payment_status);
    }

    public function test_transition_to_cancelled_releases_reservations_and_restores_stock(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED, PaymentStatus::PAID);
        $variant = $order->items->first()->variant;
        $initialStock = $variant->inventory->quantity;

        // Create an active reservation
        StockReservation::create([
            'order_id' => $order->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'status' => 'reserved',
            'session_or_user_id' => (string) $this->customer1->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Transition to CANCELLED via status update
        $res = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CANCELLED->value,
            'notes' => 'Customer requested cancellation via call',
        ]);
        $res->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // Active reservation released
        $this->assertEquals(0, StockReservation::where('order_id', $order->id)->where('status', 'reserved')->count());

        // Stock restored
        $variant->inventory->refresh();
        $this->assertEquals($initialStock + 1, $variant->inventory->quantity);
    }

    public function test_dispatching_order_creates_and_syncs_shipment_record(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PACKED);

        $res = $this->actingAs($this->adminUser)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::DISPATCHED->value,
            'carrier' => 'FedEx Ground',
            'tracking_number' => 'FX-99887766',
            'location' => 'Memphis SuperHub, TN',
        ]);
        $res->assertSessionHas('success');

        $order->refresh();
        $this->assertCount(1, $order->shipments);

        $shipment = $order->shipments->first();
        $this->assertEquals('FedEx Ground', $shipment->carrier);
        $this->assertEquals('FX-99887766', $shipment->tracking_number);
        $this->assertEquals('in_transit', $shipment->status);
        $this->assertNotNull($shipment->shipped_at);
        $this->assertGreaterThanOrEqual(1, $shipment->events()->count());
    }

    public function test_tracking_url_generated_for_standard_carriers(): void
    {
        $fedexOrder = $this->createSampleOrder($this->customer1);
        $fedexOrder->update(['carrier' => 'FedEx Express', 'tracking_number' => '123456']);
        $this->assertStringContainsString('fedex.com', $fedexOrder->tracking_url);
        $this->assertStringContainsString('123456', $fedexOrder->tracking_url);

        $upsOrder = $this->createSampleOrder($this->customer1);
        $upsOrder->update(['carrier' => 'UPS Ground', 'tracking_number' => '1Z999']);
        $this->assertStringContainsString('ups.com', $upsOrder->tracking_url);

        $uspsOrder = $this->createSampleOrder($this->customer1);
        $uspsOrder->update(['carrier' => 'USPS Priority', 'tracking_number' => '94001']);
        $this->assertStringContainsString('usps.com', $uspsOrder->tracking_url);

        $dhlOrder = $this->createSampleOrder($this->customer1);
        $dhlOrder->update(['carrier' => 'DHL Express', 'tracking_number' => '555666']);
        $this->assertStringContainsString('dhl.com', $dhlOrder->tracking_url);
    }

    public function test_role_permissions_enforced_for_order_management(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);

        // 1. Order Manager (has orders.view, orders.update_status, orders.cancel) -> Allowed
        $resManager = $this->actingAs($this->orderManager)->get('/admin/orders');
        $resManager->assertStatus(200);

        $resUpdate = $this->actingAs($this->orderManager)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CONFIRMED->value,
        ]);
        $resUpdate->assertSessionHas('success');

        // 2. Inventory Manager (does NOT have orders.view) -> Denied with 403
        $this->flushSession();
        $resInv = $this->actingAs($this->inventoryManager)->get('/admin/orders');
        $resInv->assertStatus(403);

        $resInvUpdate = $this->actingAs($this->inventoryManager)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::PROCESSING->value,
        ]);
        $resInvUpdate->assertStatus(403);
    }

    public function test_customer_profile_or_address_update_does_not_alter_historical_order_snapshot(): void
    {
        $order = $this->createSampleOrder($this->customer1);
        $originalShippingStreet = $order->shipping_address['street'];
        $originalCustomerName = $order->customer->name;

        // Customer modifies their live account profile and default address
        $this->customer1->update([
            'name' => 'Brand New Name',
            'phone' => '+19998887777',
        ]);

        $order->refresh();

        // Historical order addresses and customer snapshot remain untouched
        $this->assertEquals($originalShippingStreet, $order->shipping_address['street']);
        $this->assertEquals('John', $order->shipping_address['firstName']);
    }

    public function test_orders_view_only_user_can_view_but_cannot_update_or_cancel(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);

        // Assign only 'orders.view' permission to a staff role
        $viewPermission = Permission::where('slug', 'orders.view')->firstOrFail();
        $viewerRole = Role::where('slug', 'customer_support')->firstOrFail();
        $viewerRole->permissions()->sync([$viewPermission->id]);

        $viewerStaff = User::create([
            'name' => 'View Only Staff',
            'email' => 'view_only_staff@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $viewerStaff->roles()->sync([$viewerRole->id]);

        // 1. GET /admin/orders -> Allowed (200)
        $this->flushSession();
        $resIndex = $this->actingAs($viewerStaff)->get('/admin/orders');
        $resIndex->assertStatus(200);

        // 2. GET /admin/orders/{id} -> Allowed (200)
        $resDetail = $this->actingAs($viewerStaff)->get("/admin/orders/{$order->id}");
        $resDetail->assertStatus(200);

        // 3. POST /admin/orders/{id}/status -> Denied (403)
        $resStatus = $this->actingAs($viewerStaff)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CONFIRMED->value,
        ]);
        $resStatus->assertStatus(403);

        // 4. POST /admin/orders/{id}/cancel -> Denied (403)
        $resCancel = $this->actingAs($viewerStaff)->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Unauthorized cancellation attempt',
        ]);
        $resCancel->assertStatus(403);

        // Verify order state unchanged
        $order->refresh();
        $this->assertEquals(OrderStatus::PLACED, $order->status);
    }

    public function test_orders_update_status_user_without_cancel_permission_can_update_status_but_cannot_cancel(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);

        // Assign 'orders.view' and 'orders.update_status' without 'orders.cancel'
        $viewPermission = Permission::where('slug', 'orders.view')->firstOrFail();
        $updateStatusPermission = Permission::where('slug', 'orders.update_status')->firstOrFail();
        $updaterRole = Role::where('slug', 'order_manager')->firstOrFail();
        $updaterRole->permissions()->sync([$viewPermission->id, $updateStatusPermission->id]);

        $updaterStaff = User::create([
            'name' => 'Status Updater Staff',
            'email' => 'status_updater_staff@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $updaterStaff->roles()->sync([$updaterRole->id]);

        // 1. GET /admin/orders -> Allowed (200)
        $this->flushSession();
        $resIndex = $this->actingAs($updaterStaff)->get('/admin/orders');
        $resIndex->assertStatus(200);

        // 2. POST /admin/orders/{id}/status -> Allowed (redirect 302 with success)
        $resUpdate = $this->actingAs($updaterStaff)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CONFIRMED->value,
        ]);
        $resUpdate->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);

        // 3. POST /admin/orders/{id}/cancel -> Denied (403)
        $resCancel = $this->actingAs($updaterStaff)->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Unauthorized cancel attempt without cancel permission',
        ]);
        $resCancel->assertStatus(403);

        // Order remains CONFIRMED, not CANCELLED
        $order->refresh();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
    }

    public function test_administrative_staff_with_no_order_permissions_receives_403_for_all_order_endpoints(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::PLACED);

        // $this->inventoryManager has role 'inventory_manager', which is an administrative staff role
        // but has zero 'orders.*' permissions.
        $this->flushSession();

        // 1. GET /admin/orders -> Denied (403)
        $resList = $this->actingAs($this->inventoryManager)->get('/admin/orders');
        $resList->assertStatus(403);

        // 2. GET /admin/orders/{id} -> Denied (403)
        $resDetail = $this->actingAs($this->inventoryManager)->get("/admin/orders/{$order->id}");
        $resDetail->assertStatus(403);

        // 3. POST /admin/orders/{id}/status -> Denied (403)
        $resStatus = $this->actingAs($this->inventoryManager)->post("/admin/orders/{$order->id}/status", [
            'status' => OrderStatus::CONFIRMED->value,
        ]);
        $resStatus->assertStatus(403);

        // 4. POST /admin/orders/{id}/cancel -> Denied (403)
        $resCancel = $this->actingAs($this->inventoryManager)->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Unauthorized cancellation attempt by inventory staff',
        ]);
        $resCancel->assertStatus(403);

        // Verify order state untouched
        $order->refresh();
        $this->assertEquals(OrderStatus::PLACED, $order->status);
    }

    public function test_paid_order_cancellation_automatically_initiates_refund_and_tracks_records(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::CONFIRMED, PaymentStatus::PAID);
        $variant = $order->items->first()->variant;
        $inv = $variant->inventory;
        $initialStock = $inv->quantity;

        // Cancel the paid order via admin
        $this->flushSession();
        $response = $this->actingAs($this->orderManager)->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Customer requested full refund before shipping',
        ]);
        $response->assertSessionHas('success');

        $order->refresh();

        // 1. Verify Order status is CANCELLED and payment_status is REFUNDED
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertEquals(PaymentStatus::REFUNDED, $order->payment_status);

        // 2. Verify Refund record is created and processed
        $this->assertCount(1, $order->refunds);
        $refund = $order->refunds->first();
        $this->assertEquals('processed', $refund->status);
        $this->assertEquals((float) $order->total, (float) $refund->amount);
        $this->assertNotNull($refund->gateway_refund_id);
        $this->assertNotNull($refund->processed_at);

        // 3. Verify RefundTransaction record
        $this->assertCount(1, $refund->transactions);
        $refundTxn = $refund->transactions->first();
        $this->assertEquals('success', $refundTxn->status);
        $this->assertEquals((float) $order->total, (float) $refundTxn->amount);

        // 4. Verify Payment model is marked REFUNDED and has refund PaymentTransaction
        $payment = $order->latestPayment;
        $this->assertEquals(PaymentStatus::REFUNDED, $payment->status);
        $this->assertDatabaseHas('payment_transactions', [
            'payment_id' => $payment->id,
            'type' => 'refund',
            'status' => 'success',
        ]);

        // 5. Verify physical inventory was restored
        $inv->refresh();
        $this->assertEquals($initialStock + 1, $inv->quantity);
    }

    public function test_paid_order_cancellation_with_failed_gateway_refund_records_failed_refund_obligation(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::CONFIRMED, PaymentStatus::PAID);
        $variant = $order->items->first()->variant;
        $inv = $variant->inventory;
        $initialStock = $inv->quantity;

        // Mock PaymentService to simulate gateway refund failure
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('refundPayment')
                ->once()
                ->andThrow(new \Exception('Payment gateway connection timed out'));
        });

        // Cancel order via admin
        $this->flushSession();
        $response = $this->actingAs($this->orderManager)->post("/admin/orders/{$order->id}/cancel", [
            'reason' => 'Gateway failure test cancellation',
        ]);
        $response->assertSessionHas('success');

        $order->refresh();

        // 1. Order is still CANCELLED and physical inventory is restored
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $inv->refresh();
        $this->assertEquals($initialStock + 1, $inv->quantity);

        // 2. Refund record exists with status 'failed' (explicit tracked refund obligation)
        $this->assertCount(1, $order->refunds);
        $refund = $order->refunds->first();
        $this->assertEquals('failed', $refund->status);
        $this->assertStringContainsString('Payment gateway connection timed out', $refund->notes);

        // 3. RefundTransaction record logged as 'failed'
        $this->assertCount(1, $refund->transactions);
        $this->assertEquals('failed', $refund->transactions->first()->status);

        // 4. Order payment_status is NOT falsely marked REFUNDED (remains PAID to prevent silent false settlement)
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);

        // 5. Status history records the failed refund obligation
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'new_status' => OrderStatus::CANCELLED->value,
        ]);
        $history = $order->statusHistories()->where('new_status', OrderStatus::CANCELLED->value)->first();
        $this->assertStringContainsString('failed', $history->description);
    }

    public function test_duplicate_cancellation_on_paid_order_is_idempotent(): void
    {
        $order = $this->createSampleOrder($this->customer1, OrderStatus::CONFIRMED, PaymentStatus::PAID);
        $orderService = app(OrderService::class);

        // 1. First cancellation
        $firstCancel = $orderService->cancelOrder($order, 'First cancellation', $this->orderManager);
        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertCount(1, $order->refunds);

        $inv = $order->items->first()->variant->inventory;
        $stockAfterFirst = $inv->quantity;

        // 2. Second cancellation attempt on same order
        $secondCancel = $orderService->cancelOrder($order, 'Duplicate second cancellation', $this->orderManager);

        $order->refresh();
        $inv->refresh();

        // Must still have exactly 1 refund, exactly 1 cancellation history, and unchanged stock
        $this->assertCount(1, $order->refunds);
        $this->assertEquals($stockAfterFirst, $inv->quantity);
        $this->assertEquals(1, $order->statusHistories()->where('new_status', OrderStatus::CANCELLED->value)->count());
    }
}
