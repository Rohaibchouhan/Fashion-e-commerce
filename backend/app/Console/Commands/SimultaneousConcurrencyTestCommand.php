<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimultaneousConcurrencyTestCommand extends Command
{
    protected $signature = 'ecommerce:verify-true-concurrency';
    protected $description = 'Perform genuine multi-process simultaneous concurrency testing on live MySQL';

    public function handle(CheckoutService $checkoutService): int
    {
        $this->info("=== Real MySQL Genuine Multi-Process Simultaneous Concurrency ===");

        $driver = DB::connection()->getDriverName();
        $dbName = DB::connection()->getDatabaseName();
        $this->line("Database Engine: <fg=cyan>{$driver}</> on <fg=cyan>{$dbName}</>");

        if ($driver !== 'mysql') {
            $this->error("Must be executed on MySQL.");
            return 1;
        }

        $allPassed = true;

        // =========================================================================
        // Test 1: Two Simultaneous Independent Processes with Same Idempotency Key
        // =========================================================================
        $this->line("\n<fg=yellow>[Concurrent Test 1] Two Simultaneous Checkout Processes (Same Idempotency Key)</>");

        try {
            $variant = ProductVariant::where('stock', '>', 10)->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();
            $initReserved = $inv->reserved_quantity;

            $key = 'SIMUL_IDEMP_' . Str::random(10);
            $syncTime = microtime(true) + 1.2; // Synchronize start ~1.2s in future

            $phpBin = PHP_BINARY;
            $artisan = base_path('artisan');

            $cmd1 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker checkout --key={$key} --variant={$variant->id} --wait-until={$syncTime}";
            $cmd2 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker checkout --key={$key} --variant={$variant->id} --wait-until={$syncTime}";

            // Launch process 1
            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            // Launch process 2 simultaneously
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
                throw new Exception("One or both worker processes crashed. Out1: {$out1} {$err1}, Out2: {$out2} {$err2}");
            }

            // Exactly one process created the order, the other received idempotent replay
            $createdCount = 0;
            $replayedCount = 0;
            if (empty($res1['idempotent_replay'])) $createdCount++; else $replayedCount++;
            if (empty($res2['idempotent_replay'])) $createdCount++; else $replayedCount++;

            $this->assert($createdCount === 1, "Exactly one process inserted the order in MySQL");
            $this->assert($replayedCount === 1, "The racing process caught unique constraint and safely returned idempotent replay");
            $this->assert($res1['order_id'] === $res2['order_id'], "Both processes resolved the identical order ID ({$res1['order_id']})");

            $orderCount = Order::where('idempotency_key', $key)->count();
            $this->assert($orderCount === 1, "MySQL database contains exactly 1 order row for idempotency key");

            $inv->refresh();
            $this->assert($inv->reserved_quantity === $initReserved + 1, "Reserved quantity incremented by exactly 1 item (no double-reservation)");

            // Cleanup
            $orderId = $res1['order_id'];
            StockReservation::where('order_id', $orderId)->delete();
            \App\Models\OrderItem::where('order_id', $orderId)->delete();
            Payment::where('order_id', $orderId)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $orderId)->delete();
            Order::where('id', $orderId)->forceDelete();
            $inv->decrement('reserved_quantity', 1);

            $this->info("✔ PASS: Two simultaneous checkout processes raced safely on MySQL without duplicate orders or stock corruption.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        // =========================================================================
        // Test 2: Two Simultaneous Payment Confirmations for the Same Order
        // =========================================================================
        $this->line("\n<fg=yellow>[Concurrent Test 2] Two Simultaneous Payment Confirmations (Same Order)</>");

        try {
            $variant = ProductVariant::where('stock', '>', 10)->firstOrFail();
            $customer = User::where('status', 'active')->firstOrFail();
            $inv = Inventory::where('variant_id', $variant->id)->firstOrFail();
            $initPhysical = $inv->quantity;

            // Place pending order
            $orderRes = $checkoutService->processCheckout([
                'shippingAddress' => [
                    'firstName' => 'SimulPay',
                    'lastName' => 'Race',
                    'street' => '200 Parallel Way',
                    'city' => 'Anytown',
                    'state' => 'CA',
                    'zipCode' => '90210',
                ],
                'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                'payment' => ['method' => 'card'],
            ], $customer);

            $order = $orderRes['order'];
            $txnId = 'SIMUL_PAY_TXN_' . Str::random(8);
            $syncTime = microtime(true) + 1.2;

            $cmd1 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker confirm --order={$order->id} --txn={$txnId} --wait-until={$syncTime}";
            $cmd2 = "\"{$phpBin}\" \"{$artisan}\" ecommerce:concurrency-worker confirm --order={$order->id} --txn={$txnId} --wait-until={$syncTime}";

            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            $p2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);

            $out1 = stream_get_contents($pipes1[1]);
            $err1 = stream_get_contents($pipes1[2]);
            fclose($pipes1[1]); fclose($pipes1[2]);
            proc_close($p1);

            $out2 = stream_get_contents($pipes2[1]);
            $err2 = stream_get_contents($pipes2[2]);
            fclose($pipes2[1]); fclose($pipes2[2]);
            proc_close($p2);

            $res1 = json_decode(trim($out1), true);
            $res2 = json_decode(trim($out2), true);

            $this->line("  Process 1: " . trim($out1));
            $this->line("  Process 2: " . trim($out2));

            $this->assert(!empty($res1['success']), "Process 1 completed successfully");
            $this->assert(!empty($res2['success']), "Process 2 completed successfully");

            $order->refresh();
            $inv->refresh();

            $this->assert($order->payment_status === PaymentStatus::PAID, "Order status is PAID");
            $this->assert($inv->quantity === $initPhysical - 1, "Physical inventory deducted exactly once (initial - 1)");
            $this->assert($inv->reserved_quantity === 0, "Reserved inventory cleared to 0");

            $captureCount = PaymentTransaction::where('payment_id', $order->latestPayment->id)->where('type', 'capture')->count();
            $this->assert($captureCount === 1, "Exactly 1 capture transaction exists in MySQL");

            // Cleanup
            StockReservation::where('order_id', $order->id)->delete();
            \App\Models\OrderItem::where('order_id', $order->id)->delete();
            PaymentTransaction::where('payment_id', $order->latestPayment->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            \App\Models\OrderStatusHistory::where('order_id', $order->id)->delete();
            $order->forceDelete();
            $inv->update(['quantity' => $initPhysical]);

            $this->info("✔ PASS: Two simultaneous payment confirmation processes executed atomically on MySQL without duplicate deductions.");
        } catch (Exception $e) {
            $this->error("✖ FAIL: " . $e->getMessage());
            $allPassed = false;
        }

        $this->line("\n============================================================");
        if ($allPassed) {
            $this->info("All Genuine Multi-Process Simultaneous Concurrency Tests PASSED!");
            return 0;
        }

        $this->error("Genuine Concurrency Tests FAILED!");
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
