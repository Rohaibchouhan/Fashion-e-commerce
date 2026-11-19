<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $adminUser;
    protected User $restrictedStaff;
    protected PaymentService $paymentService;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);

        $customerRole = Role::where('slug', 'customer')->first();
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $inventoryRole = Role::where('slug', 'inventory_manager')->first();

        $this->customer = User::create([
            'name' => 'Refund Tester',
            'email' => 'refund.tester@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer->roles()->sync([$customerRole->id]);

        $this->adminUser = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin.manager@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->adminUser->roles()->sync([$superAdminRole->id]);

        $this->restrictedStaff = User::create([
            'name' => 'Restricted Staff',
            'email' => 'restricted.staff@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->restrictedStaff->roles()->sync([$inventoryRole->id]);

        $this->paymentService = app(PaymentService::class);
        $this->orderService = app(OrderService::class);
    }

    protected function createPaidOrder(float $amount = 100.00): array
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $order = Order::create([
            'order_number' => 'WD-REF-' . strtoupper(bin2hex(random_bytes(4))),
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => 'card',
            'subtotal' => $amount,
            'shipping_cost' => 0.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => $amount,
            'shipping_address' => [
                'firstName' => 'Refund',
                'lastName' => 'Tester',
                'street' => '100 Safe St',
                'city' => 'San Jose',
                'state' => 'CA',
                'zipCode' => '95113',
            ],
            'billing_address' => [
                'firstName' => 'Refund',
                'lastName' => 'Tester',
                'street' => '100 Safe St',
                'city' => 'San Jose',
                'state' => 'CA',
                'zipCode' => '95113',
            ],
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
            'unit_price' => $amount,
            'quantity' => 1,
            'subtotal' => $amount,
            'total' => $amount,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'card',
            'transaction_id' => 'TXN-CARD-' . strtoupper(bin2hex(random_bytes(4))),
            'amount' => $amount,
            'currency' => 'USD',
            'status' => PaymentStatus::PAID,
            'is_test' => false,
            'paid_at' => now(),
        ]);

        PaymentTransaction::create([
            'payment_id' => $payment->id,
            'type' => 'capture',
            'amount' => $amount,
            'reference_id' => $payment->transaction_id,
            'status' => 'success',
            'raw_payload' => ['captured' => true],
        ]);

        return [$order, $payment];
    }

    public function test_total_refunds_cannot_exceed_captured_payment_amount(): void
    {
        [$order, $payment] = $this->createPaidOrder(100.00);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exceeds remaining refundable balance');

        $this->paymentService->refundPayment($payment, 150.00, 'Over-refund attempt');
    }

    public function test_multiple_partial_refunds_calculate_remaining_balance_correctly(): void
    {
        [$order, $payment] = $this->createPaidOrder(100.00);

        // 1. Partial refund 1: $40.00 -> remaining $60.00
        $res1 = $this->paymentService->refundPayment($payment, 40.00, 'Partial refund 1');
        $this->assertTrue($res1['success']);
        $payment->refresh();
        $this->assertEquals(PaymentStatus::PARTIALLY_REFUNDED, $payment->status);
        $this->assertEquals(60.00, $payment->remaining_refundable_amount);

        // 2. Partial refund 2: $35.00 -> remaining $25.00
        $res2 = $this->paymentService->refundPayment($payment, 35.00, 'Partial refund 2');
        $this->assertTrue($res2['success']);
        $payment->refresh();
        $this->assertEquals(PaymentStatus::PARTIALLY_REFUNDED, $payment->status);
        $this->assertEquals(25.00, $payment->remaining_refundable_amount);

        // 3. Attempting to refund $30.00 must fail because only $25.00 remains
        $threw = false;
        try {
            $this->paymentService->refundPayment($payment, 30.00, 'Excessive partial refund');
        } catch (Exception $e) {
            $threw = true;
            $this->assertStringContainsString('exceeds remaining refundable balance', $e->getMessage());
        }
        $this->assertTrue($threw, "Excessive partial refund did not throw exception");

        // 4. Final partial refund 3: exactly $25.00 -> remaining $0.00, status becomes REFUNDED
        $res3 = $this->paymentService->refundPayment($payment, 25.00, 'Final partial refund');
        $this->assertTrue($res3['success']);
        $payment->refresh();
        $this->assertEquals(PaymentStatus::REFUNDED, $payment->status);
        $this->assertEquals(0.00, $payment->remaining_refundable_amount);

        // 5. Any further refund attempt must be rejected
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already been fully refunded');
        $this->paymentService->refundPayment($payment, 5.00, 'Post-settled refund attempt');
    }

    public function test_duplicate_refund_with_same_idempotency_key_returns_idempotent_replay(): void
    {
        [$order, $payment] = $this->createPaidOrder(100.00);
        $idempotencyKey = 'IDEMP_TEST_UNIQUE_12345';

        // 1. Initial refund with idempotency key
        $res1 = $this->paymentService->refundPayment($payment, 50.00, 'Initial refund', $idempotencyKey);
        $this->assertTrue($res1['success']);
        $this->assertEquals($idempotencyKey, $res1['idempotency_key']);

        $payment->refresh();
        $this->assertEquals(50.00, $payment->remaining_refundable_amount);
        $this->assertEquals(1, PaymentTransaction::where('payment_id', $payment->id)->where('type', 'refund')->count());

        // 2. Duplicate refund request with identical idempotency key
        $res2 = $this->paymentService->refundPayment($payment, 50.00, 'Duplicate request', $idempotencyKey);
        $this->assertTrue($res2['success']);

        // Assert idempotent replay: transaction count NOT duplicated, balance NOT deducted again
        $payment->refresh();
        $this->assertEquals(50.00, $payment->remaining_refundable_amount);
        $this->assertEquals(1, PaymentTransaction::where('payment_id', $payment->id)->where('type', 'refund')->count());
    }

    public function test_view_refunds_requires_permission(): void
    {
        // 1. Unauthenticated guest -> Redirected to admin login (302)
        $this->flushSession();
        $resGuest = $this->get('/admin/refunds');
        $resGuest->assertStatus(302)->assertRedirect(route('admin.login'));

        // 2. Restricted staff lacking 'payments.view' -> 403 Forbidden
        $this->flushSession();
        $resDenied = $this->actingAs($this->restrictedStaff)->get('/admin/refunds');
        $resDenied->assertStatus(403);

        // 3. Super Admin (has all permissions) -> 200 OK
        $this->flushSession();
        $resAllowed = $this->actingAs($this->adminUser)->get('/admin/refunds');
        $resAllowed->assertStatus(200);
        $resAllowed->assertSee('Customer Refunds &amp; Payouts', false);

        // 4. Staff member assigned an admin staff role with 'payments.view' -> 200 OK
        $supportRole = Role::where('slug', 'customer_support')->first();
        $viewPerm = Permission::where('slug', 'payments.view')->first();
        if ($viewPerm && $supportRole) {
            $supportRole->permissions()->syncWithoutDetaching([$viewPerm->id]);
        }
        $supportStaff = User::create([
            'name' => 'Support Staff with Payment View',
            'email' => 'support.' . uniqid() . '@example.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        if ($supportRole) {
            $supportStaff->roles()->sync([$supportRole->id]);
        }

        $this->flushSession();
        $resSupport = $this->actingAs($supportStaff)->get('/admin/refunds');
        $resSupport->assertStatus(200);
        $resSupport->assertSee('Customer Refunds &amp; Payouts', false);
    }

    public function test_retry_refund_requires_permission(): void
    {
        [$order, $payment] = $this->createPaidOrder(80.00);

        $refund = Refund::create([
            'refund_number' => 'REF-FAIL-' . strtoupper(bin2hex(random_bytes(4))),
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 80.00,
            'reason' => 'Failed refund retry test',
            'status' => 'failed',
            'notes' => 'Simulated gateway timeout',
        ]);

        // 1. Restricted staff lacking 'refunds.retry' -> Denied 403
        $this->flushSession();
        $resDenied = $this->actingAs($this->restrictedStaff)->post("/admin/refunds/{$refund->id}/retry");
        $resDenied->assertStatus(403);

        // 2. Super Admin (has all permissions) -> Allowed (redirect with success flash)
        $this->flushSession();
        $resAllowed = $this->actingAs($this->adminUser)->post("/admin/refunds/{$refund->id}/retry");
        $resAllowed->assertSessionHas('success');

        $refund->refresh();
        $this->assertEquals('processed', $refund->status);
    }

    public function test_retry_refund_workflow_preserves_every_attempt(): void
    {
        [$order, $payment] = $this->createPaidOrder(50.00);

        // Create an initial failed refund with 1 failed transaction attempt
        $refund = Refund::create([
            'refund_number' => 'REF-ATTEMPT-' . strtoupper(bin2hex(random_bytes(4))),
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50.00,
            'reason' => 'Initial failure',
            'status' => 'failed',
            'notes' => 'Attempt 1 failed: Gateway unreachable',
        ]);

        RefundTransaction::create([
            'refund_id' => $refund->id,
            'amount' => 50.00,
            'gateway' => 'card',
            'status' => 'failed',
            'response_payload' => ['error' => 'Gateway unreachable'],
        ]);

        $this->assertCount(1, $refund->transactions);

        // Retry the refund
        $retriedRefund = $this->paymentService->retryRefund($refund, $this->adminUser);

        // Assert updated refund status and audit history
        $this->assertEquals('processed', $retriedRefund->status);
        $this->assertNotNull($retriedRefund->gateway_refund_id);

        // Assert every attempt is preserved: now exactly 2 transactions (1 failed, 1 success)
        $refund->refresh();
        $this->assertCount(2, $refund->transactions);
        $this->assertEquals('failed', $refund->transactions->first()->status);
        $this->assertEquals('success', $refund->transactions->last()->status);

        // Assert payment and order statuses are updated
        $payment->refresh();
        $order->refresh();
        $this->assertEquals(PaymentStatus::REFUNDED, $payment->status);
        $this->assertEquals(PaymentStatus::REFUNDED, $order->payment_status);
    }

    public function test_cannot_retry_already_processed_refund(): void
    {
        [$order, $payment] = $this->createPaidOrder(60.00);

        $refund = Refund::create([
            'refund_number' => 'REF-SUCCESS-' . strtoupper(bin2hex(random_bytes(4))),
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 60.00,
            'reason' => 'Completed refund',
            'status' => 'processed',
            'gateway_refund_id' => 'REF-CARD-12345',
            'processed_at' => now(),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already been processed successfully and cannot be retried');

        $this->paymentService->retryRefund($refund, $this->adminUser);
    }
}
