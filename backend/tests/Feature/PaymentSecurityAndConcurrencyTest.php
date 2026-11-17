<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\IdempotencyConflictException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\ShippingMethodSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentSecurityAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected string $token;
    protected string $stripeSecret = 'whsec_test_secret_key_1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(CouponSeeder::class);
        $this->seed(ShippingMethodSeeder::class);

        $this->customer = User::factory()->create([
            'name' => 'Jane Security Tester',
            'email' => 'jane.tester@example.com',
            'status' => 'active',
        ]);
        $this->token = $this->customer->createToken('test-token')->plainTextToken;

        Config::set('services.stripe.webhook_secret', $this->stripeSecret);
    }

    protected function createOrder(int $quantity = 2): array
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/checkout/process', [
                'shippingAddress' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Tester',
                    'street' => '100 Security Blvd',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [
                    ['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => $quantity],
                ],
            ]);

        $response->assertStatus(201);
        $order = Order::findOrFail($response->json('data.order.id'));
        $payment = $order->latestPayment;

        return [$order, $payment, $variant];
    }

    protected function buildStripeSignature(string $rawBody, string $secret, ?int $timestamp = null): string
    {
        $t = $timestamp ?? time();
        $signedPayload = "{$t}.{$rawBody}";
        $v1 = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$t},v1={$v1}";
    }

    // ========================================================
    // 1. Payment Webhook Security & Signature Verification
    // ========================================================

    public function test_webhook_without_signature_is_rejected(): void
    {
        [$order, $payment] = $this->createOrder();

        $payload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'status' => 'succeeded',
                    'amount_received' => (int) ($order->total * 100),
                    'currency' => 'usd',
                    'metadata' => [
                        'order_id' => $order->id,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/payments/webhook/stripe', $payload);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('signature', strtolower($response->json('message')));
    }

    public function test_webhook_with_unconfigured_secret_is_disabled(): void
    {
        Config::set('services.stripe.webhook_secret', null);
        [$order, $payment] = $this->createOrder();

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'status' => 'succeeded',
                    'amount_received' => (int) ($order->total * 100),
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        $signature = 't=' . time() . ',v1=fake_signature';

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('not configured', strtolower($response->json('message')));
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        [$order, $payment] = $this->createOrder();

        $rawBody = json_encode([
            'id' => 'evt_test_invalid_sig',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_invalid_sig',
                    'status' => 'succeeded',
                    'amount_received' => (int) ($order->total * 100),
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        $signature = 't=' . time() . ',v1=invalid_hmac_hash_999999999999';

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('verification failed', strtolower($response->json('message')));
    }

    public function test_webhook_with_expired_timestamp_is_rejected(): void
    {
        [$order, $payment] = $this->createOrder();

        $rawBody = json_encode([
            'id' => 'evt_test_expired',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_expired',
                    'status' => 'succeeded',
                    'amount_received' => (int) ($order->total * 100),
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        // 10 minutes ago exceeds the 300-second tolerance
        $expiredTime = time() - 600;
        $signature = $this->buildStripeSignature($rawBody, $this->stripeSecret, $expiredTime);

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('tolerance exceeded', strtolower($response->json('message')));
    }

    public function test_webhook_with_valid_stripe_signature_confirms_payment_and_commits_stock(): void
    {
        [$order, $payment, $variant] = $this->createOrder(2);
        $initialPhysical = $variant->inventory->quantity;

        $txnId = 'pi_valid_' . Str::random(16);
        $rawBody = json_encode([
            'id' => 'evt_' . Str::random(16),
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $txnId,
                    'status' => 'succeeded',
                    'amount_received' => (int) round($order->total * 100),
                    'currency' => 'usd',
                    'metadata' => [
                        'order_id' => $order->id,
                    ],
                ],
            ],
        ]);

        $signature = $this->buildStripeSignature($rawBody, $this->stripeSecret);

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $order->refresh();
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);

        // Physical inventory committed
        $this->assertEquals($initialPhysical - 2, $variant->inventory->fresh()->quantity);
        $this->assertEquals(0, $variant->inventory->fresh()->reserved_quantity);

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'committed',
        ]);
    }

    public function test_webhook_replay_is_idempotent_and_ignored(): void
    {
        [$order, $payment, $variant] = $this->createOrder(2);
        $initialPhysical = $variant->inventory->quantity;

        $txnId = 'pi_replay_' . Str::random(16);
        $rawBody = json_encode([
            'id' => 'evt_replay_' . Str::random(12),
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $txnId,
                    'status' => 'succeeded',
                    'amount_received' => (int) round($order->total * 100),
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        $signature = $this->buildStripeSignature($rawBody, $this->stripeSecret);

        // 1st delivery
        $res1 = $this->call('POST', '/api/v1/payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $rawBody);
        $res1->assertStatus(200);

        // 2nd delivery (replay)
        $res2 = $this->call('POST', '/api/v1/payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $rawBody);
        $res2->assertStatus(200);
        $this->assertStringContainsString('already processed', strtolower($res2->json('message')));

        // Physical stock only deducted once
        $this->assertEquals($initialPhysical - 2, $variant->inventory->fresh()->quantity);
    }

    public function test_webhook_rejects_cod_payments(): void
    {
        [$order, $payment] = $this->createOrder();

        $rawBody = json_encode([
            'order_id' => $order->id,
            'status' => 'paid',
        ]);

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/cod',
            [],
            [],
            [],
            ['HTTP_X_SIGNATURE' => 'any_signature', 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('cannot be confirmed via public webhooks', $response->json('message'));
    }

    public function test_webhook_rejects_amount_mismatch(): void
    {
        [$order, $payment] = $this->createOrder();

        // Send half of order total
        $tamperedAmountCents = (int) round(($order->total / 2) * 100);
        $rawBody = json_encode([
            'id' => 'evt_tamper_amt',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_tamper_amt_' . Str::random(10),
                    'status' => 'succeeded',
                    'amount_received' => $tamperedAmountCents,
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        $signature = $this->buildStripeSignature($rawBody, $this->stripeSecret);

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('amount mismatch', strtolower($response->json('message')));
        $this->assertEquals(PaymentStatus::PENDING, $order->fresh()->payment_status);
    }

    public function test_webhook_rejects_currency_mismatch(): void
    {
        [$order, $payment] = $this->createOrder();

        $rawBody = json_encode([
            'id' => 'evt_tamper_curr',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_tamper_curr_' . Str::random(10),
                    'status' => 'succeeded',
                    'amount_received' => (int) round($order->total * 100),
                    'currency' => 'eur',
                    'metadata' => ['order_id' => $order->id],
                ],
            ],
        ]);

        $signature = $this->buildStripeSignature($rawBody, $this->stripeSecret);

        $response = $this->call(
            'POST',
            '/api/v1/payments/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(400);
        $this->assertStringContainsString('currency mismatch', strtolower($response->json('message')));
        $this->assertEquals(PaymentStatus::PENDING, $order->fresh()->payment_status);
    }

    public function test_webhook_rejects_transaction_reference_reuse_across_orders(): void
    {
        [$orderA, $paymentA] = $this->createOrder(1);
        [$orderB, $paymentB] = $this->createOrder(1);

        $sharedTxnId = 'pi_shared_token_' . Str::random(12);

        // Confirm Order A
        $rawBodyA = json_encode([
            'id' => 'evt_order_a',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $sharedTxnId,
                    'status' => 'succeeded',
                    'amount_received' => (int) round($orderA->total * 100),
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $orderA->id],
                ],
            ],
        ]);
        $sigA = $this->buildStripeSignature($rawBodyA, $this->stripeSecret);

        $resA = $this->call('POST', '/api/v1/payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sigA, 'CONTENT_TYPE' => 'application/json'], $rawBodyA);
        $resA->assertStatus(200);

        // Attempt to confirm Order B with the same transaction reference
        $rawBodyB = json_encode([
            'id' => 'evt_order_b',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $sharedTxnId,
                    'status' => 'succeeded',
                    'amount_received' => (int) round($orderB->total * 100),
                    'currency' => 'usd',
                    'metadata' => ['order_id' => $orderB->id],
                ],
            ],
        ]);
        $sigB = $this->buildStripeSignature($rawBodyB, $this->stripeSecret);

        $resB = $this->call('POST', '/api/v1/payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sigB, 'CONTENT_TYPE' => 'application/json'], $rawBodyB);
        $resB->assertStatus(400);
        $this->assertStringContainsString('already been used', strtolower($resB->json('message')));
        $this->assertEquals(PaymentStatus::PENDING, $orderB->fresh()->payment_status);
    }

    // ========================================================
    // 2. Inventory Reservation Lifecycle & Isolation
    // ========================================================

    public function test_payment_confirmation_rejected_if_reservation_already_released(): void
    {
        [$order, $payment, $variant] = $this->createOrder();

        // Release reservations explicitly
        $inventoryService = app(InventoryService::class);
        $inventoryService->releaseReservationsForOrder($order->id, 'Testing released reservation guard');

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);

        $paymentService = app(PaymentService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('released');

        $paymentService->confirmPayment($order, 'TXN_TEST_RELEASED');
    }

    public function test_payment_confirmation_rejected_if_reservation_expired(): void
    {
        [$order, $payment, $variant] = $this->createOrder();

        // Mark reservation expired 1 hour ago
        StockReservation::where('order_id', $order->id)->update([
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $paymentService = app(PaymentService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('expired');

        $paymentService->confirmPayment($order, 'TXN_TEST_EXPIRED');
    }

    public function test_release_expired_reservations_does_not_release_active_order_reservations(): void
    {
        [$order, $payment, $variant] = $this->createOrder(2);

        // Make reservation timestamp in the past to test isolation
        StockReservation::where('order_id', $order->id)->update([
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $inventoryService = app(InventoryService::class);
        // loose cleanup must only touch reservations without an order!
        $released = $inventoryService->releaseExpiredReservations();

        $this->assertEquals(0, $released);
        // Order reservation is still untouched and reserved!
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'reserved',
        ]);
    }

    public function test_order_cancellation_after_payment_restores_physical_stock_idempotently(): void
    {
        [$order, $payment, $variant] = $this->createOrder(3);
        $initialPhysical = $variant->inventory->quantity;

        $paymentService = app(PaymentService::class);
        $orderService = app(\App\Services\OrderService::class);

        // Pay and deduct physical inventory
        $paymentService->confirmPayment($order, 'TXN_PAY_FOR_CANCEL');
        $this->assertEquals($initialPhysical - 3, $variant->inventory->fresh()->quantity);

        // Cancel order -> physical stock restored
        $orderService->cancelOrder($order, 'Customer requested return before shipment');
        $this->assertEquals($initialPhysical, $variant->inventory->fresh()->quantity);

        // Cancel again (duplicate trigger) -> stock must NOT be restored twice!
        $orderService->cancelOrder($order, 'Duplicate cancellation call');
        $this->assertEquals($initialPhysical, $variant->inventory->fresh()->quantity);
    }

    // ========================================================
    // 3. Idempotency Key & Fingerprinting Hardening
    // ========================================================

    public function test_idempotency_fingerprint_changes_when_payment_method_differs(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $checkoutService = app(CheckoutService::class);

        $payloadCard = [
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'card'],
            'shippingAddress' => ['street' => '1 Main St', 'city' => 'Anytown', 'state' => 'CA', 'zipCode' => '90001'],
        ];

        $payloadCod = [
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'cod'],
            'shippingAddress' => ['street' => '1 Main St', 'city' => 'Anytown', 'state' => 'CA', 'zipCode' => '90001'],
        ];

        $hashCard = $checkoutService->generateIdempotencyFingerprint($payloadCard, $this->customer);
        $hashCod = $checkoutService->generateIdempotencyFingerprint($payloadCod, $this->customer);

        $this->assertNotEquals($hashCard, $hashCod);
    }

    public function test_idempotency_fingerprint_is_stable_when_address_keys_are_reordered(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $checkoutService = app(CheckoutService::class);

        $payload1 = [
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'card'],
            'shippingAddress' => [
                'city' => 'Anytown',
                'state' => 'CA',
                'street' => '1 Main St',
                'zipCode' => '90001',
            ],
        ];

        $payload2 = [
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'card'],
            'shippingAddress' => [
                'street' => '1 Main St',
                'zipCode' => '90001',
                'city' => 'Anytown',
                'state' => 'CA',
            ],
        ];

        $hash1 = $checkoutService->generateIdempotencyFingerprint($payload1, $this->customer);
        $hash2 = $checkoutService->generateIdempotencyFingerprint($payload2, $this->customer);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_idempotency_conflict_thrown_when_same_key_used_with_different_payment_method(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'IDEMPOTENT_KEY_' . Str::random(16);

        // 1st request with card
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/checkout/process', [
                'idempotency_key' => $idempotencyKey,
                'shippingAddress' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Tester',
                    'street' => '100 Security Blvd',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ]);

        $res1->assertStatus(201);

        // 2nd request with same key but different payment method
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/checkout/process', [
                'idempotency_key' => $idempotencyKey,
                'shippingAddress' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Tester',
                    'street' => '100 Security Blvd',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'cod'],
            ]);

        $res2->assertStatus(409);
        $this->assertStringContainsString('different order details', $res2->json('message'));
    }

    public function test_multi_item_reservation_locks_variants_in_ascending_order(): void
    {
        $product = Product::first();
        $variants = $product->variants->take(3);
        $this->assertGreaterThanOrEqual(2, $variants->count());

        $v1 = $variants[0];
        $v2 = $variants[1];

        // Pass items in descending order of variant_id
        $itemsInReverse = [
            ['variant_id' => max($v1->id, $v2->id), 'quantity' => 1],
            ['variant_id' => min($v1->id, $v2->id), 'quantity' => 1],
        ];

        $inventoryService = app(InventoryService::class);
        $reservations = $inventoryService->reserveStock((string) $this->customer->id, $itemsInReverse, 30);

        $this->assertCount(2, $reservations);
        // Both reservations created
        $this->assertDatabaseHas('stock_reservations', [
            'variant_id' => $v1->id,
            'status' => 'reserved',
        ]);
        $this->assertDatabaseHas('stock_reservations', [
            'variant_id' => $v2->id,
            'status' => 'reserved',
        ]);
    }

    public function test_concurrent_duplicate_idempotency_key_handles_unique_violation_gracefully(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $idempotencyKey = 'IDEMPOTENT_RACE_' . Str::random(16);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'shippingAddress' => [
                'firstName' => 'Jane',
                'lastName' => 'Tester',
                'street' => '100 Security Blvd',
                'city' => 'San Jose',
                'state' => 'CA',
                'zipCode' => '95113',
            ],
            'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1]],
            'payment' => ['method' => 'card'],
        ];

        $checkoutService = app(CheckoutService::class);

        // First process creates the order
        $result1 = $checkoutService->processCheckout($payload, $this->customer);
        $this->assertNotNull($result1['order']);
        $this->assertEquals($idempotencyKey, $result1['order']->idempotency_key);

        // Second process with identical key returns idempotent replay
        $result2 = $checkoutService->processCheckout($payload, $this->customer);
        $this->assertTrue($result2['idempotent_replay']);
        $this->assertEquals($result1['order']->id, $result2['order']->id);
    }
}
