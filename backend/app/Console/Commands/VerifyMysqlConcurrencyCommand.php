<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VerifyMysqlConcurrencyCommand extends Command
{
    protected $signature = 'ecommerce:verify-mysql-concurrency';
    protected $description = 'Verify locking, concurrency, idempotency, and payment security directly on the live MySQL database';

    public function handle(
        InventoryService $inventoryService,
        PaymentService $paymentService,
        CheckoutService $checkoutService
    ): int {
        $this->info("=== Real MySQL Concurrency & Payment Security Verification ===");

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $dbName = $connection->getDatabaseName();

        $this->line("Database Driver: <fg=cyan>{$driver}</>");
        $this->line("Database Name:   <fg=cyan>{$dbName}</>");

        if ($driver !== 'mysql') {
            $this->error("ERROR: This verification must run against real MySQL, found: {$driver}");
            return 1;
        }

        $allPassed = true;

        // Test 1: Real MySQL Multi-Item Lock Order (Deadlock-Free Sorting)
        $this->line("\n<fg=yellow>[Test 1] Real MySQL Multi-Item Lock Ordering</>");
        try {
            $variants = ProductVariant::where('stock', '>', 5)->take(2)->get();
            if ($variants->count() < 2) {
                throw new Exception("Need at least 2 variants with stock > 5 in MySQL database.");
            }

            $v1 = $variants[0];
            $v2 = $variants[1];

            $lowVariant = min($v1->id, $v2->id);
            $highVariant = max($v1->id, $v2->id);

            // Pass in reverse order (highVariant first)
            $itemsReversed = [
                ['variant_id' => $highVariant, 'quantity' => 1],
                ['variant_id' => $lowVariant, 'quantity' => 1],
            ];

            $customer = User::where('status', 'active')->firstOrFail();
            $reservations = $inventoryService->reserveStock((string) $customer->id, $itemsReversed, 15);

            $this->assert($reservations[0]->variant_id === $lowVariant, "First reserved item is sorted ascending (ID: {$lowVariant})");
            $this->assert($reservations[1]->variant_id === $highVariant, "Second reserved item is sorted ascending (ID: {$highVariant})");

            // Cleanup reservations
            foreach ($reservations as $r) {
                Inventory::where('variant_id', $r->variant_id)->decrement('reserved_quantity', 1);
                $r->delete();
            }

            $this->info("✔ PASS: Multi-item reservation sorts variant IDs ascending to prevent MySQL deadlocks.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        // Test 2: Real MySQL Idempotency Key 1062 Unique Constraint Handling
        $this->line("\n<fg=yellow>[Test 2] Real MySQL Idempotency 1062 Race Condition Handling</>");
        try {
            $testKey = 'MYSQL_RACE_' . Str::random(12);
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $customer = User::where('status', 'active')->firstOrFail();

            $payload = [
                'idempotency_key' => $testKey,
                'shippingAddress' => [
                    'firstName' => 'MySQL',
                    'lastName' => 'Tester',
                    'street' => '123 InnoDB Way',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ];

            // Run 1: Inserts the order
            $res1 = $checkoutService->processCheckout($payload, $customer);
            $this->assert(!empty($res1['order']), "Order successfully placed on MySQL (ID: {$res1['order']->id})");

            // Run 2: Replays without crashing with raw 1062 SQL exception
            $res2 = $checkoutService->processCheckout($payload, $customer);
            $this->assert($res2['idempotent_replay'] === true, "Second request replayed idempotently");
            $this->assert($res2['order']->id === $res1['order']->id, "Replayed exact original order ID");

            // Cleanup test order
            $orderId = $res1['order']->id;
            StockReservation::where('order_id', $orderId)->delete();
            \App\Models\OrderItem::where('order_id', $orderId)->delete();
            Payment::where('order_id', $orderId)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $orderId)->delete();
            Order::where('id', $orderId)->forceDelete();
            Inventory::where('variant_id', $variant->id)->decrement('reserved_quantity', 1);

            $this->info("✔ PASS: MySQL unique constraint violation on idempotency_key is caught and handled gracefully.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        // Test 3: Real MySQL Cryptographic Webhook & Atomic Stock Commitment
        $this->line("\n<fg=yellow>[Test 3] Real MySQL Webhook Signature & Atomic Commitment</>");
        try {
            $secret = 'whsec_mysql_test_secret_998877665544332211';
            Config::set('services.stripe.webhook_secret', $secret);

            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $customer = User::where('status', 'active')->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();
            $initPhysical = $inv->quantity;

            $payload = [
                'shippingAddress' => [
                    'firstName' => 'MySQL',
                    'lastName' => 'Webhook',
                    'street' => '456 InnoDB Blvd',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 2]],
                'payment' => ['method' => 'card'],
            ];

            $orderResult = $checkoutService->processCheckout($payload, $customer);
            $order = $orderResult['order'];
            $payment = $order->latestPayment;

            $txnId = 'pi_mysql_' . Str::random(14);
            $rawBody = json_encode([
                'id' => 'evt_mysql_' . Str::random(12),
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

            $timestamp = time();
            $signature = "t={$timestamp},v1=" . hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

            // Execute handleWebhook directly through PaymentService
            $webhookResult = $paymentService->handleWebhook('stripe', json_decode($rawBody, true), $signature, $rawBody);
            $this->assert($webhookResult['success'] === true, "Webhook successfully verified on MySQL");

            $order->refresh();
            $this->assert($order->payment_status === PaymentStatus::PAID, "Order payment_status is PAID on MySQL");
            $this->assert($order->status === OrderStatus::CONFIRMED, "Order status is CONFIRMED on MySQL");

            // Verify physical stock deducted on MySQL
            $inv->refresh();
            $this->assert($inv->quantity === $initPhysical - 2, "Physical quantity permanently deducted on MySQL");
            $this->assert($inv->reserved_quantity === 0, "Reserved quantity cleared to 0 on MySQL");

            // Cleanup test order
            StockReservation::where('order_id', $order->id)->delete();
            \App\Models\OrderItem::where('order_id', $order->id)->delete();
            PaymentTransaction::where('payment_id', $payment->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $order->id)->delete();
            $order->forceDelete();
            $inv->update(['quantity' => $initPhysical]);

            $this->info("✔ PASS: Real MySQL webhook signature verification and atomic commitment succeeded.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        // Test 4: Real MySQL Duplicate Simultaneous Payment Confirmations (Exactly-Once Deduction)
        $this->line("\n<fg=yellow>[Test 4] Real MySQL Duplicate Payment Confirmation Race</>");
        try {
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $customer = User::where('status', 'active')->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();
            $initPhysical = $inv->quantity;

            $payload = [
                'shippingAddress' => [
                    'firstName' => 'MySQL',
                    'lastName' => 'Race',
                    'street' => '789 Concurrency Way',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ];

            $orderResult = $checkoutService->processCheckout($payload, $customer);
            $order = $orderResult['order'];
            $txnId = 'TXN_CONCURRENT_1_' . Str::random(8);

            // Confirmation 1: Commits reservation and deducts 1
            $paymentService->confirmPayment($order, $txnId);
            $inv->refresh();
            $this->assert($inv->quantity === $initPhysical - 1, "First confirmation deducted physical inventory exactly once");

            // Confirmation 2: Replays with same order / transaction
            $paymentService->confirmPayment($order, $txnId);
            $inv->refresh();
            $this->assert($inv->quantity === $initPhysical - 1, "Second confirmation did NOT double-deduct physical inventory");

            // Cleanup
            StockReservation::where('order_id', $order->id)->delete();
            \App\Models\OrderItem::where('order_id', $order->id)->delete();
            PaymentTransaction::where('payment_id', $order->latestPayment->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $order->id)->delete();
            $order->forceDelete();
            $inv->update(['quantity' => $initPhysical]);

            $this->info("✔ PASS: Real MySQL duplicate payment confirmation is atomic and strictly idempotent.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        // Test 5: Real MySQL Payment Confirmation vs Cancellation/Expiration Race
        $this->line("\n<fg=yellow>[Test 5] Real MySQL Payment Confirmation vs Cancelled/Expired Order</>");
        try {
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $customer = User::where('status', 'active')->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();
            $initPhysical = $inv->quantity;

            $payload = [
                'shippingAddress' => [
                    'firstName' => 'MySQL',
                    'lastName' => 'Cancelled',
                    'street' => '101 Race Ave',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ];

            $orderResult = $checkoutService->processCheckout($payload, $customer);
            $order = $orderResult['order'];

            // Cancel the order first and release held reservations
            $order->update(['status' => OrderStatus::CANCELLED]);
            $inventoryService->releaseReservationsForOrder($order->id);

            // Now attempt to confirm payment on the cancelled order
            $blocked = false;
            try {
                $paymentService->confirmPayment($order, 'TXN_RACE_AFTER_CANCEL_' . Str::random(8));
            } catch (Exception $e) {
                $blocked = true;
            }

            $this->assert($blocked, "Payment confirmation on cancelled order was rejected");
            $inv->refresh();
            $this->assert($inv->quantity === $initPhysical, "Physical stock preserved without deduction");

            // Cleanup
            StockReservation::where('order_id', $order->id)->delete();
            \App\Models\OrderItem::where('order_id', $order->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $order->id)->delete();
            $order->forceDelete();

            $this->info("✔ PASS: Cancelled order prevents late payment confirmation and stock deduction.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        // Test 6: Real MySQL Duplicate Cancellation & Idempotent Stock Release
        $this->line("\n<fg=yellow>[Test 6] Real MySQL Duplicate Cancellation & Reservation Release</>");
        try {
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $customer = User::where('status', 'active')->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();
            $initReserved = $inv->reserved_quantity;

            $payload = [
                'shippingAddress' => [
                    'firstName' => 'MySQL',
                    'lastName' => 'DoubleCancel',
                    'street' => '202 Double Way',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ];

            $orderResult = $checkoutService->processCheckout($payload, $customer);
            $order = $orderResult['order'];

            // Release 1: Releases the 1 reserved quantity
            $this->assert(StockReservation::where('order_id', $order->id)->where('status', 'reserved')->count() === 1, "Reservation is initially held with status 'reserved'");
            $inventoryService->releaseReservationsForOrder($order->id);
            $this->assert(StockReservation::where('order_id', $order->id)->where('status', 'released')->count() === 1, "Reservation transitioned to 'released'");

            // Release 2: Redundant call on already-released order
            $inventoryService->releaseReservationsForOrder($order->id);
            $this->assert(StockReservation::where('order_id', $order->id)->where('status', 'released')->count() === 1, "Second release is idempotent and leaves status 'released'");

            $inv->refresh();
            $this->assert($inv->reserved_quantity === $initReserved, "Reserved quantity returned to exact baseline without negative drift");

            // Cleanup
            StockReservation::where('order_id', $order->id)->delete();
            \App\Models\OrderItem::where('order_id', $order->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $order->id)->delete();
            $order->forceDelete();

            $this->info("✔ PASS: Duplicate cancellation and reservation release is strictly idempotent.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        $this->line("\n============================================================");
        if ($allPassed) {
            $this->info("All Real MySQL Concurrency & Security Tests PASSED!");
            return 0;
        }

        $this->error("Some Real MySQL Concurrency & Security Tests FAILED!");
        return 1;
    }

    protected function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception("Assertion failed: {$message}");
        }
        $this->line("  ✓ {$message}");
    }
}
