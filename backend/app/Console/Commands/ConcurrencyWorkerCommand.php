<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Console\Command;

class ConcurrencyWorkerCommand extends Command
{
    protected $signature = 'ecommerce:concurrency-worker
                            {action : checkout or confirm}
                            {--key= : Idempotency key for checkout}
                            {--variant= : Variant ID for checkout}
                            {--order= : Order ID for confirmation}
                            {--txn= : Transaction reference for confirmation}
                            {--wait-until= : Unix timestamp with microsecond to synchronize execution}';

    protected $description = 'Worker process for genuine simultaneous concurrency testing on real MySQL';

    public function handle(CheckoutService $checkoutService, PaymentService $paymentService): int
    {
        $action = $this->argument('action');
        $waitUntil = (float) $this->option('wait-until');

        // Precision spin-wait to synchronize two independent processes to the exact millisecond
        if ($waitUntil > 0) {
            while (microtime(true) < $waitUntil) {
                usleep(500); // 0.5ms precision
            }
        }

        try {
            if ($action === 'checkout') {
                $key = $this->option('key');
                $variantId = (int) $this->option('variant');
                $variant = ProductVariant::findOrFail($variantId);
                $customer = User::where('status', 'active')->firstOrFail();

                $payload = [
                    'idempotency_key' => $key,
                    'shippingAddress' => [
                        'firstName' => 'Simul',
                        'lastName' => 'Tester',
                        'street' => '100 Race Rd',
                        'city' => 'Anytown',
                        'state' => 'CA',
                        'zipCode' => '90210',
                    ],
                    'items' => [['productId' => $variant->product_id, 'variantId' => $variant->id, 'quantity' => 1]],
                    'payment' => ['method' => 'card'],
                ];

                $result = $checkoutService->processCheckout($payload, $customer);
                $this->line(json_encode([
                    'success' => true,
                    'order_id' => $result['order']->id,
                    'idempotent_replay' => $result['idempotent_replay'] ?? false,
                ]));
                return 0;
            }

            if ($action === 'confirm') {
                $orderId = (int) $this->option('order');
                $txnId = $this->option('txn');
                $order = Order::findOrFail($orderId);

                $payment = $paymentService->confirmPayment($order, $txnId);
                $this->line(json_encode([
                    'success' => true,
                    'order_id' => $order->id,
                    'payment_status' => $payment->status->value,
                ]));
                return 0;
            }

            $this->error("Unknown action: {$action}");
            return 1;
        } catch (Exception $e) {
            $this->line(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
            return 1;
        }
    }
}
