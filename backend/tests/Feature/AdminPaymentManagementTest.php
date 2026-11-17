<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customer;
    protected string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(CouponSeeder::class);
        $this->seed(ShippingMethodSeeder::class);

        $this->adminUser = User::where('email', 'admin@wearday.com')->first();

        $customerRole = Role::where('slug', 'customer')->first();
        $this->customer = User::create([
            'name' => 'John Customer',
            'email' => 'john.customer@example.com',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);
        $this->customerToken = $this->customer->createToken('test-token')->plainTextToken;

        Setting::set('test_payment_mode_enabled', '1', 'financial', 'boolean');
    }

    protected function tearDown(): void
    {
        App::detectEnvironment(fn () => 'testing');
        parent::tearDown();
    }

    protected function placePendingOrder(int $qty = 1): Order
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $checkoutService = app(CheckoutService::class);
        $result = $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => $qty]],
            'payment' => ['method' => 'card'],
        ], $this->customer);

        return $result['order'];
    }

    // ========================================================
    // 1. Authorization & Dashboard Access Tests
    // ========================================================

    public function test_authorized_administrator_can_access_payments_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/payments');

        $response->assertStatus(200);
        $response->assertSee('Payment Management');
        $response->assertSee('Customer Payments');
        $response->assertSee('Test &amp; Sandbox Payments', false);
    }

    public function test_unauthorized_users_cannot_access_payment_management(): void
    {
        // Unauthenticated request redirects to admin login
        $unauthRes = $this->get('/admin/payments');
        $unauthRes->assertStatus(302)->assertRedirect(route('admin.login'));

        // Customer account denied
        $custRes = $this->actingAs($this->customer)->get('/admin/payments');
        $custRes->assertStatus(302)->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_single_payment_details(): void
    {
        $order = $this->placePendingOrder();
        $payment = $order->latestPayment;

        $response = $this->actingAs($this->adminUser)->get("/admin/payments/{$payment->id}");

        $response->assertStatus(200);
        $response->assertSee($order->order_number);
        $response->assertSee('Transactions Audit Trail');
    }

    // ========================================================
    // 2. Test Payment Sandbox Mode Toggle Tests
    // ========================================================

    public function test_admin_can_toggle_test_payment_mode(): void
    {
        $this->assertTrue((bool) Setting::get('test_payment_mode_enabled'));

        // Toggle OFF
        $res1 = $this->actingAs($this->adminUser)->post('/admin/payments/test-mode/toggle');
        $res1->assertStatus(302);
        $this->assertFalse((bool) Setting::get('test_payment_mode_enabled'));

        // Toggle ON
        $res2 = $this->actingAs($this->adminUser)->post('/admin/payments/test-mode/toggle');
        $res2->assertStatus(302);
        $this->assertTrue((bool) Setting::get('test_payment_mode_enabled'));
    }

    public function test_test_payment_mode_cannot_be_toggled_in_production(): void
    {
        try {
            App::detectEnvironment(fn () => 'production');

            $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
                ->actingAs($this->adminUser)
                ->from('/admin/payments')
                ->post('/admin/payments/test-mode/toggle');

            $response->assertStatus(302);
            $response->assertSessionHas('error');
        } finally {
            App::detectEnvironment(fn () => 'testing');
        }
    }

    // ========================================================
    // 3. Test Payment Execution, Scenarios & Lifecycle Tests
    // ========================================================

    public function test_test_payment_succeeds_in_allowed_environment(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;

        $checkoutService = app(CheckoutService::class);
        $result = $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2]],
            'payment' => [
                'type' => 'test',
                'scenario' => 'success',
            ],
        ], $this->customer);

        $order = $result['order']->fresh();
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);

        $payment = $order->latestPayment;
        $this->assertNotNull($payment);
        $this->assertTrue($payment->is_test);
        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertStringStartsWith('TEST-SUCCESS-', $payment->transaction_id);

        // Exercises real physical inventory commitment
        $this->assertEquals($initialPhysical - 2, $variant->inventory->fresh()->quantity);
        $this->assertEquals(0, $variant->inventory->fresh()->reserved_quantity);

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'committed',
        ]);
    }

    public function test_test_payment_failure_does_not_mark_order_paid(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $initialPhysical = $variant->inventory->quantity;

        $checkoutService = app(CheckoutService::class);
        $result = $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => [
                'type' => 'test',
                'scenario' => 'failure',
            ],
        ], $this->customer);

        $order = $result['order']->fresh();
        // Order remains in pending placed status
        $this->assertEquals(PaymentStatus::PENDING, $order->payment_status);
        $this->assertEquals(OrderStatus::PLACED, $order->status);

        $payment = $order->latestPayment;
        $this->assertNotNull($payment);
        $this->assertTrue($payment->is_test);
        $this->assertEquals(PaymentStatus::FAILED, $payment->status);

        // Physical inventory must NOT be deducted
        $this->assertEquals($initialPhysical, $variant->inventory->fresh()->quantity);
        // Reservation remains held
        $this->assertEquals(1, $variant->inventory->fresh()->reserved_quantity);
    }

    public function test_test_payment_cannot_execute_in_production(): void
    {
        try {
            App::detectEnvironment(fn () => 'production');

            $order = $this->placePendingOrder();
            $paymentService = app(PaymentService::class);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('forbidden in production');

            $paymentService->processOrderPayment($order, [
                'type' => 'test',
                'scenario' => 'success',
            ]);
        } finally {
            App::detectEnvironment(fn () => 'testing');
        }
    }

    public function test_test_transactions_are_distinguishable_from_real_transactions(): void
    {
        // 1. Real Order & Payment
        $realOrder = $this->placePendingOrder();
        $paymentService = app(PaymentService::class);
        $paymentService->confirmPayment($realOrder, 'TXN_REAL_VERIFIED_123');

        // 2. Test Order & Payment
        $product = Product::first();
        $variant = $product->variants->first();
        $checkoutService = app(CheckoutService::class);
        $testResult = $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['type' => 'test', 'scenario' => 'success'],
        ], $this->customer);

        $testOrder = $testResult['order']->fresh();

        $realPayment = $realOrder->latestPayment;
        $testPayment = $testOrder->latestPayment;

        $this->assertFalse($realPayment->is_test);
        $this->assertTrue($testPayment->is_test);

        // Filtered views separate them cleanly
        $realList = Payment::where('is_test', false)->pluck('id')->toArray();
        $testList = Payment::where('is_test', true)->pluck('id')->toArray();

        $this->assertContains($realPayment->id, $realList);
        $this->assertNotContains($testPayment->id, $realList);

        $this->assertContains($testPayment->id, $testList);
        $this->assertNotContains($realPayment->id, $testList);
    }

    public function test_test_payment_cannot_bypass_inventory_reservation_rules(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Reduce available stock to 0
        $variant->inventory->update(['quantity' => 0, 'reserved_quantity' => 0]);

        $checkoutService = app(CheckoutService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['type' => 'test', 'scenario' => 'success'],
        ], $this->customer);
    }

    public function test_admin_simulator_endpoint_executes_test_payment(): void
    {
        $order = $this->placePendingOrder();

        $response = $this->actingAs($this->adminUser)->post('/admin/payments/test-simulate', [
            'order_id' => $order->id,
            'scenario' => 'success',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.payments', ['tab' => 'test']));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        $this->assertTrue($order->latestPayment->is_test);
    }

    public function test_existing_checkout_and_real_payment_functionality_remains_operational(): void
    {
        $order = $this->placePendingOrder(1);

        $payment = $order->latestPayment;
        $this->assertNotNull($payment);
        $this->assertFalse($payment->is_test);
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);

        $paymentService = app(PaymentService::class);
        $paymentService->confirmPayment($order, 'TXN_REAL_LIVE_999');

        $order->refresh();
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertFalse($order->latestPayment->is_test);
    }

    public function test_test_payment_is_disabled_by_default_and_rejected(): void
    {
        Setting::set('test_payment_mode_enabled', '0', 'financial', 'boolean');

        $product = Product::first();
        $variant = $product->variants->first();
        $checkoutService = app(CheckoutService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('disabled by store administrator');

        $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['type' => 'test', 'scenario' => 'success'],
        ], $this->customer);
    }

    public function test_unauthorized_staff_member_cannot_access_or_manage_payments(): void
    {
        // Create an admin staff member lacking payments.view and settings.manage permissions
        $inventoryRole = Role::where('slug', 'inventory_manager')->first();
        $staffUser = User::create([
            'name' => 'Stock Clerk',
            'email' => 'stock.clerk@wearday.com',
            'password' => bcrypt('StaffPass123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $staffUser->roles()->sync([$inventoryRole->id]);

        // 1. Cannot view payments index (403 Forbidden)
        $resView = $this->actingAs($staffUser)->get('/admin/payments');
        $resView->assertStatus(403);

        // 2. Cannot toggle test payment mode (403 Forbidden)
        $resToggle = $this->actingAs($staffUser)->post('/admin/payments/test-mode/toggle');
        $resToggle->assertStatus(403);

        // 3. Cannot simulate test payment (403 Forbidden)
        $order = $this->placePendingOrder();
        $resSim = $this->actingAs($staffUser)->post('/admin/payments/test-simulate', [
            'order_id' => $order->id,
            'scenario' => 'success',
        ]);
        $resSim->assertStatus(403);
    }

    public function test_test_payments_are_strictly_excluded_from_real_revenue_analytics(): void
    {
        // 1. Process a real order
        $realOrder = $this->placePendingOrder(1);
        $realAmount = (float) $realOrder->total;
        $paymentService = app(PaymentService::class);
        $paymentService->confirmPayment($realOrder, 'TXN_REAL_SETTLED_001');

        // 2. Process a test sandbox order
        $product = Product::first();
        $variant = $product->variants->first();
        $checkoutService = app(CheckoutService::class);
        $testResult = $checkoutService->processCheckout([
            'shippingAddress' => [
                'firstName' => 'John',
                'lastName' => 'Customer',
                'street' => '100 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zipCode' => '90210',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2]],
            'payment' => ['type' => 'test', 'scenario' => 'success'],
        ], $this->customer);
        $testOrder = $testResult['order']->fresh();
        $testAmount = (float) $testOrder->total;

        // Verify both orders are marked as PAID
        $this->assertEquals(PaymentStatus::PAID, $realOrder->fresh()->payment_status);
        $this->assertEquals(PaymentStatus::PAID, $testOrder->fresh()->payment_status);

        // 3. Real revenue strictly sums only real orders
        $realRevenue = (float) Order::real()->where('payment_status', PaymentStatus::PAID)->sum('total');
        $this->assertEquals($realAmount, $realRevenue);

        // Verify the test amount is NOT included in real revenue
        $allOrdersSum = (float) Order::where('payment_status', PaymentStatus::PAID)->sum('total');
        $this->assertEquals($realAmount + $testAmount, $allOrdersSum);
        $this->assertNotEquals($allOrdersSum, $realRevenue);

        // Dashboard request receives the real revenue
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
        $response->assertViewHas('totalRevenue', $realAmount);
    }

    public function test_sensitive_credentials_are_redacted_in_payment_details(): void
    {
        $order = $this->placePendingOrder();
        $payment = $order->latestPayment;

        $payment->update([
            'gateway_response' => [
                'api_key' => 'secret_live_key_99999',
                'webhook_secret' => 'whsec_private_abc',
                'cvv' => '123',
                'status' => 'paid',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->get("/admin/payments/{$payment->id}");

        $response->assertStatus(200);
        $response->assertDontSee('secret_live_key_99999');
        $response->assertDontSee('whsec_private_abc');
        $response->assertDontSee('123');
        $response->assertSee('***REDACTED***');
    }
}
