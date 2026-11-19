<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VerifyOrderConcurrencyCommand extends Command
{
    protected $signature = 'ecommerce:verify-order-concurrency';
    protected $description = 'Empirically verify payment confirmation vs cancellation and duplicate cancellation concurrency on dedicated MySQL test database';

    public function handle(
        CheckoutService $checkoutService,
        PaymentService $paymentService,
        OrderService $orderService,
        InventoryService $inventoryService
    ): int {
        $this->info("=======================================================================");
        $this->info("=== Real MySQL Genuine Multi-Process Order Concurrency Verification ===");
        $this->info("=======================================================================");

        $driver = DB::connection()->getDriverName();
        $dbName = DB::connection()->getDatabaseName();
        $this->line("Database Driver: <fg=cyan>{$driver}</>");
        $this->line("Database Name:   <fg=cyan>{$dbName}</>");

        // Strict Safeguard: Must be running on MySQL and against dedicated test database
        if ($driver !== 'mysql') {
            $this->error("CRITICAL ERROR: This test must run on real MySQL (InnoDB), not {$driver}.");
            return 1;
        }

        if (!str_ends_with($dbName, '_test')) {
            $this->error("CRITICAL SAFETY CHECK FAILED: Refusing to run concurrency tests against '{$dbName}'. Dedicated test database ending in '_test' is required.");
            return 1;
        }

        $phpBin = PHP_BINARY;
        $artisan = base_path('artisan');
        $allPassed = true;

        // =========================================================================
        // Test A: Simultaneous Payment Confirmation vs Order Cancellation
        // =========================================================================
        $this->line("\n<fg=yellow>[Test A] Simultaneous Payment Confirmation vs Order Cancellation</>");
        $this->line("  Scenario: Two independent PHP processes racing on the exact same placed order.");
        $this->line("  Process 1 attempts Payment Confirmation; Process 2 attempts Order Cancellation.");

        try {
            $customer = User::where('status', 'active')->firstOrFail();
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();

            $initPhysical = (int) $inv->quantity;
            $initReserved = (int) $inv->reserved_quantity;
            $initMovementsCount = InventoryMovement::where('inventory_id', $inv->id)->count();

            // Place order with 1 item (reserves 1 stock unit)
            $orderRes = $checkoutService->processCheckout([
                'idempotency_key' => 'ORDER_CONCURRENCY_' . Str::random(10),
                'shippingAddress' => [
                    'firstName' => 'Concurrency',
                    'lastName' => 'Tester',
                    'street' => '100 Race Condition Blvd',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ], $customer);

            $order = $orderRes['order'];
            $inv->refresh();

            $this->line("  -> Order #{$order->order_number} (ID: {$order->id}) placed with 1 item.");
            $this->assert($inv->reserved_quantity === $initReserved + 1, "Reserved quantity incremented by 1 on checkout.");
            $this->assert($inv->quantity === $initPhysical, "Physical inventory untouched on checkout.");

            $txnId = 'TXN_RACE_' . Str::random(8);
            $syncTime = microtime(true) + 1.2; // 1200ms in the future to allow both processes to bootstrap

            $cmd1 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker confirm --order={$order->id} --txn={$txnId} --wait-until={$syncTime} --env=mysql_test";
            $cmd2 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker cancel --order={$order->id} --reason=\"Simultaneous cancel race\" --wait-until={$syncTime} --env=mysql_test";

            // Spawn two independent OS processes simultaneously
            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            $p2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);

            $out1 = stream_get_contents($pipes1[1]);
            $err1 = stream_get_contents($pipes1[2]);
            fclose($pipes1[1]); fclose($pipes1[2]);
            $code1 = proc_close($p1);

            $out2 = stream_get_contents($pipes2[1]);
            $err2 = stream_get_contents($pipes2[2]);
            fclose($pipes2[1]); fclose($pipes2[2]);
            $code2 = proc_close($p2);

            $res1 = json_decode(trim($out1), true);
            $res2 = json_decode(trim($out2), true);

            $this->line("  Process 1 (Confirm): " . trim($out1));
            $this->line("  Process 2 (Cancel):  " . trim($out2));

            if (!$res1 || !$res2) {
                throw new Exception("One or both worker processes crashed.\nOut1: {$out1} {$err1}\nOut2: {$out2} {$err2}");
            }

            $order->refresh();
            $inv->refresh();

            // Determine which branch won the race for the orders row lock
            if ($res1['success'] === true && $res2['success'] === true) {
                // Payment confirmation acquired row lock first, committed reservation, then cancellation ran and restored stock
                $this->line("  -> Outcome: Payment confirmation acquired lock first; Cancellation acquired lock second.");
                $this->assert($order->status === OrderStatus::CANCELLED, "Order final status is CANCELLED");
                $this->assert($inv->quantity === $initPhysical, "Physical inventory perfectly restored to initial ({$initPhysical}) after payment commit + cancellation restore");
                $this->assert($inv->reserved_quantity === $initReserved, "Reserved quantity returned to initial ({$initReserved})");
            } elseif ($res1['success'] === false && $res2['success'] === true) {
                // Cancellation acquired row lock first, cancelled order and released reservation; confirmation rejected because order already cancelled
                $this->line("  -> Outcome: Cancellation acquired lock first; Payment confirmation was safely rejected.");
                $this->assert(str_contains($res1['error'], 'already been cancelled'), "Payment confirmation was safely rejected with 'already been cancelled' message");
                $this->assert($order->status === OrderStatus::CANCELLED, "Order final status is CANCELLED");
                $this->assert($inv->quantity === $initPhysical, "Physical inventory unchanged at initial ({$initPhysical})");
                $this->assert($inv->reserved_quantity === $initReserved, "Reserved quantity returned to initial ({$initReserved})");
            } else {
                throw new Exception("Unexpected race result combination: Res1=" . json_encode($res1) . ", Res2=" . json_encode($res2));
            }

            // Verify Stock Reservations are not in orphaned RESERVED state
            $activeReservations = StockReservation::where('order_id', $order->id)
                ->where('status', 'reserved')
                ->count();
            $this->assert($activeReservations === 0, "No active/orphaned reservations remain for this order.");

            // Verify Status History records
            $cancelledHistories = OrderStatusHistory::where('order_id', $order->id)
                ->where('new_status', OrderStatus::CANCELLED->value)
                ->count();
            $this->assert($cancelledHistories === 1, "Exactly 1 cancellation status history record exists.");

            $this->info("✔ PASS: Simultaneous payment confirmation and cancellation executed with zero stock corruption or lock collision.");
        } catch (Exception $e) {
            $this->error("✖ FAIL [Test A]: " . $e->getMessage());
            $allPassed = false;
        }

        // =========================================================================
        // Test B: Simultaneous Duplicate Cancellations for the Same Order
        // =========================================================================
        $this->line("\n<fg=yellow>[Test B] Simultaneous Duplicate Cancellations (Same Order)</>");
        $this->line("  Scenario: Two independent PHP processes simultaneously attempt to cancel the exact same order.");
        $this->line("  Verifying idempotency, single stock restoration, and no duplicate status histories.");

        try {
            $customer = User::where('status', 'active')->firstOrFail();
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();

            $initPhysical = (int) $inv->quantity;
            $initReserved = (int) $inv->reserved_quantity;

            // Place order with 1 item
            $orderRes = $checkoutService->processCheckout([
                'idempotency_key' => 'DUP_CANCEL_' . Str::random(10),
                'shippingAddress' => [
                    'firstName' => 'DupCancel',
                    'lastName' => 'Tester',
                    'street' => '200 Parallel Way',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ], $customer);

            $order = $orderRes['order'];
            $inv->refresh();

            $this->assert($inv->reserved_quantity === $initReserved + 1, "Reserved quantity incremented by 1 on checkout.");

            $syncTime = microtime(true) + 1.2;

            $cmd1 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker cancel --order={$order->id} --reason=\"Simultaneous cancel A\" --wait-until={$syncTime} --env=mysql_test";
            $cmd2 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker cancel --order={$order->id} --reason=\"Simultaneous cancel B\" --wait-until={$syncTime} --env=mysql_test";

            // Spawn two independent OS processes simultaneously
            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            $p2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);

            $out1 = stream_get_contents($pipes1[1]);
            $err1 = stream_get_contents($pipes1[2]);
            fclose($pipes1[1]); fclose($pipes1[2]);
            $code1 = proc_close($p1);

            $out2 = stream_get_contents($pipes2[1]);
            $err2 = stream_get_contents($pipes2[2]);
            fclose($pipes2[1]); fclose($pipes2[2]);
            $code2 = proc_close($p2);

            $res1 = json_decode(trim($out1), true);
            $res2 = json_decode(trim($out2), true);

            $this->line("  Process 1: " . trim($out1));
            $this->line("  Process 2: " . trim($out2));

            if (!$res1 || !$res2) {
                throw new Exception("One or both worker processes crashed.\nOut1: {$out1} {$err1}\nOut2: {$out2} {$err2}");
            }

            // Both processes must report success (first executes cancellation; second returns idempotent replay)
            $this->assert($res1['success'] === true, "Process 1 completed successfully.");
            $this->assert($res2['success'] === true, "Process 2 completed successfully.");

            $order->refresh();
            $inv->refresh();

            // Verify final order state
            $this->assert($order->status === OrderStatus::CANCELLED, "Order status is CANCELLED.");

            // Verify stock reservation was released exactly once (not decremented below initial)
            $this->assert($inv->reserved_quantity === $initReserved, "Reserved quantity decremented exactly once back to initial ({$initReserved}). No double release.");
            $this->assert($inv->quantity === $initPhysical, "Physical inventory unchanged at initial ({$initPhysical}).");

            // Verify status history has exactly ONE cancellation record
            $cancelledHistories = OrderStatusHistory::where('order_id', $order->id)
                ->where('new_status', OrderStatus::CANCELLED->value)
                ->count();
            $this->assert($cancelledHistories === 1, "Exactly 1 cancellation record in order_status_histories. Idempotent cancel prevented duplicate history.");

            $this->info("✔ PASS: Simultaneous duplicate cancellations handled idempotently with zero duplicate stock releases or histories.");
        } catch (Exception $e) {
            $this->error("✖ FAIL [Test B]: " . $e->getMessage());
            $allPassed = false;
        }

        // =========================================================================
        // Test C: Simultaneous Concurrent Refund Requests (Race on Remaining Balance)
        // =========================================================================
        $this->line("\n<fg=yellow>[Test C] Simultaneous Concurrent Refund Requests (Race on Remaining Balance)</>");
        $this->line("  Scenario: Two independent PHP processes simultaneously request $60.00 refunds on a $100.00 payment.");
        $this->line("  Pessimistic locking must serialize requests so total refunds NEVER exceed captured balance.");

        try {
            $customer = User::where('status', 'active')->firstOrFail();
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();

            // Create a test order and captured payment for $100.00
            $orderRes = $checkoutService->processCheckout([
                'idempotency_key' => 'ORDER_REFUND_RACE_' . Str::random(10),
                'shippingAddress' => [
                    'firstName' => 'RefundRace',
                    'lastName' => 'Tester',
                    'street' => '300 Financial Row',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ], $customer);

            $order = $orderRes['order'];
            $payment = $paymentService->confirmPayment($order, 'TXN_REFUND_RACE_' . Str::random(8));
            $payment->update(['amount' => 100.00]);
            $order->update(['total' => 100.00]);

            $syncTime = microtime(true) + 1.2;

            $cmd1 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker refund --payment={$payment->id} --amount=60.00 --key=RACE_REF_1 --wait-until={$syncTime} --env=mysql_test";
            $cmd2 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker refund --payment={$payment->id} --amount=60.00 --key=RACE_REF_2 --wait-until={$syncTime} --env=mysql_test";

            // Spawn two independent OS processes simultaneously
            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            $p2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);

            $out1 = stream_get_contents($pipes1[1]);
            $err1 = stream_get_contents($pipes1[2]);
            fclose($pipes1[1]); fclose($pipes1[2]);
            $code1 = proc_close($p1);

            $out2 = stream_get_contents($pipes2[1]);
            $err2 = stream_get_contents($pipes2[2]);
            fclose($pipes2[1]); fclose($pipes2[2]);
            $code2 = proc_close($p2);

            $res1 = json_decode(trim($out1), true);
            $res2 = json_decode(trim($out2), true);

            $this->line("  Process 1: " . trim($out1));
            $this->line("  Process 2: " . trim($out2));

            if (!$res1 || !$res2) {
                throw new Exception("One or both worker processes crashed.\nOut1: {$out1} {$err1}\nOut2: {$out2} {$err2}");
            }

            // Exactly one process must succeed, and exactly one must fail due to insufficient balance
            $successCount = 0;
            $failCount = 0;
            if ($res1['success'] === true) $successCount++; else $failCount++;
            if ($res2['success'] === true) $successCount++; else $failCount++;

            $this->assert($successCount === 1, "Exactly one refund process succeeded ($60.00).");
            $this->assert($failCount === 1, "Exactly one refund process was safely rejected because of balance limit.");

            $failedError = ($res1['success'] === false) ? ($res1['error'] ?? '') : ($res2['error'] ?? '');
            $this->assert(str_contains($failedError, 'exceeds remaining refundable balance'), "Rejected process failed with 'exceeds remaining refundable balance' message.");

            $payment->refresh();
            $this->assert((float) $payment->refunded_amount === 60.00, "MySQL database records exactly $60.00 refunded (NOT $120.00).");
            $this->assert((float) $payment->remaining_refundable_amount === 40.00, "MySQL remaining refundable balance is exactly $40.00.");
            $this->assert($payment->status === PaymentStatus::PARTIALLY_REFUNDED, "Payment status in MySQL is partially_refunded.");

            $this->info("✔ PASS: Concurrent refund requests safely serialized under MySQL row lock; over-refunding was 100% prevented.");
        } catch (Exception $e) {
            $this->error("✖ FAIL [Test C]: " . $e->getMessage());
            $allPassed = false;
        }

        // =========================================================================
        // Test D: Simultaneous Duplicate Refund Retries (Idempotency Key Collision)
        // =========================================================================
        $this->line("\n<fg=yellow>[Test D] Simultaneous Duplicate Refund Retries (Same Idempotency Key)</>");
        $this->line("  Scenario: Two independent PHP processes simultaneously execute a refund with the EXACT same idempotency key.");

        try {
            $customer = User::where('status', 'active')->firstOrFail();
            $variant = ProductVariant::where('stock', '>', 5)->firstOrFail();

            $orderRes = $checkoutService->processCheckout([
                'idempotency_key' => 'ORDER_IDEMP_RACE_' . Str::random(10),
                'shippingAddress' => [
                    'firstName' => 'IdempRace',
                    'lastName' => 'Tester',
                    'street' => '400 Idempotent Way',
                    'city' => 'San Jose',
                    'state' => 'CA',
                    'zipCode' => '95113',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ], $customer);

            $order = $orderRes['order'];
            $payment = $paymentService->confirmPayment($order, 'TXN_IDEMP_RACE_' . Str::random(8));
            $payment->update(['amount' => 50.00]);

            $sharedKey = 'SIMUL_IDEMP_REF_' . Str::random(10);
            $syncTime = microtime(true) + 1.2;

            $cmd1 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker refund --payment={$payment->id} --amount=50.00 --key={$sharedKey} --wait-until={$syncTime} --env=mysql_test";
            $cmd2 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker refund --payment={$payment->id} --amount=50.00 --key={$sharedKey} --wait-until={$syncTime} --env=mysql_test";

            // Spawn two independent OS processes simultaneously
            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            $p2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);

            $out1 = stream_get_contents($pipes1[1]);
            $err1 = stream_get_contents($pipes1[2]);
            fclose($pipes1[1]); fclose($pipes1[2]);
            $code1 = proc_close($p1);

            $out2 = stream_get_contents($pipes2[1]);
            $err2 = stream_get_contents($pipes2[2]);
            fclose($pipes2[1]); fclose($pipes2[2]);
            $code2 = proc_close($p2);

            $res1 = json_decode(trim($out1), true);
            $res2 = json_decode(trim($out2), true);

            $this->line("  Process 1: " . trim($out1));
            $this->line("  Process 2: " . trim($out2));

            if (!$res1 || !$res2) {
                throw new Exception("One or both worker processes crashed.\nOut1: {$out1} {$err1}\nOut2: {$out2} {$err2}");
            }

            // Both processes must report success
            $this->assert($res1['success'] === true, "Process 1 completed successfully.");
            $this->assert($res2['success'] === true, "Process 2 completed successfully.");

            // Exactly 1 must have performed the operation, and 1 must have received idempotent replay
            $replayCount = 0;
            if (!empty($res1['idempotent_replay'])) $replayCount++;
            if (!empty($res2['idempotent_replay'])) $replayCount++;
            $this->assert($replayCount === 1, "Exactly one process performed the refund and the racing process safely received idempotent replay.");

            // Check database transactions count
            $txnCount = PaymentTransaction::where('payment_id', $payment->id)->where('type', 'refund')->count();
            $this->assert($txnCount === 1, "Exactly 1 refund transaction exists in MySQL (no double-refund).");

            $payment->refresh();
            $this->assert((float) $payment->remaining_refundable_amount === 0.00, "Payment is fully settled in MySQL.");
            $this->assert($payment->status === PaymentStatus::REFUNDED, "Payment status is refunded.");

            $this->info("✔ PASS: Simultaneous duplicate refund calls with identical idempotency key handled with zero double-refunding.");
        } catch (Exception $e) {
            $this->error("✖ FAIL [Test D]: " . $e->getMessage());
            $allPassed = false;
        }

        $this->line("\n=======================================================================");
        if ($allPassed) {
            $this->info("ALL Genuine MySQL Order Concurrency Verification Tests PASSED!");
            $this->info("Dedicated Test Database: {$dbName} (Clean & Verified)");
            return 0;
        }

        $this->error("Order Concurrency Verification Tests FAILED!");
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
