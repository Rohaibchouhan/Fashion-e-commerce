<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Payments\BankTransferGateway;
use App\Services\Payments\CardGateway;
use App\Services\Payments\CodGateway;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\TestGateway;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected ?InventoryService $inventoryService = null
    ) {
        $this->inventoryService = $inventoryService ?? app(InventoryService::class);
    }

    public function getGateway(string $method): PaymentGatewayInterface
    {
        return match (strtolower($method)) {
            'card', 'credit_card', 'debit_card', 'stripe' => new CardGateway(),
            'bank_transfer', 'wire' => new BankTransferGateway(),
            'cod', 'cash_on_delivery' => new CodGateway(),
            'test', 'test_payment', 'sandbox' => new TestGateway(),
            default => new CardGateway(),
        };
    }

    public function processOrderPayment(Order $order, array $paymentData): Payment
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $method = $paymentData['type'] ?? $paymentData['method'] ?? $order->payment_method ?? 'card';
            $gateway = $this->getGateway($method);

            $result = $gateway->initiatePayment($order, $paymentData);

            $isTest = in_array(strtolower($method), ['test', 'test_payment', 'sandbox'], true)
                || !empty($result['is_test']);

            $statusEnum = match ($result['status'] ?? 'pending') {
                'paid' => PaymentStatus::PAID,
                'failed' => PaymentStatus::FAILED,
                default => PaymentStatus::PENDING,
            };

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $method,
                'transaction_id' => $result['transaction_id'] ?? null,
                'amount' => $order->total,
                'currency' => 'USD',
                'status' => $statusEnum,
                'is_test' => $isTest,
                'gateway_response' => $result,
                'failure_reason' => ($statusEnum === PaymentStatus::FAILED) ? ($result['message'] ?? 'Payment failed') : null,
                'paid_at' => ($statusEnum === PaymentStatus::PAID) ? now() : null,
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'type' => 'charge',
                'amount' => $order->total,
                'reference_id' => $result['transaction_id'] ?? null,
                'status' => ($statusEnum === PaymentStatus::PAID) ? 'success' : (($statusEnum === PaymentStatus::FAILED) ? 'failed' : 'pending'),
                'is_test' => $isTest,
                'raw_payload' => $result,
            ]);

            // Update order status if payment was captured immediately
            if ($result['status'] === 'paid') {
                $order->update([
                    'payment_status' => PaymentStatus::PAID,
                    'status' => OrderStatus::CONFIRMED,
                    'payment_last4' => $result['last4'] ?? $order->payment_last4,
                ]);

                // Commit reserved inventory deduction atomically
                $this->inventoryService->commitReservationsForOrder($order->id);

                $prefix = $isTest ? '[TEST SANDBOX] ' : '';
                $order->statusHistories()->create([
                    'previous_status' => OrderStatus::PLACED->value,
                    'new_status' => OrderStatus::CONFIRMED->value,
                    'label' => 'Payment Verified & Confirmed',
                    'description' => "{$prefix}Payment captured via " . strtoupper($method) . ' (Txn: ' . ($result['transaction_id'] ?? 'N/A') . ').',
                ]);
            }

            return $payment;
        }, 3);
    }

    /**
     * Handle incoming gateway webhook securely:
     * - Enforce cryptographic signature verification
     * - Reject unsigned, invalid, expired, and replayed events
     * - Validate order, payment, transaction reference uniqueness, amount, and currency
     * - Commit inventory atomically inside locked transaction with bounded retry
     */
    public function handleWebhook(
        string $gatewayName,
        array $payload,
        ?string $signature = null,
        string $rawBody = ''
    ): array {
        if (empty($signature)) {
            return [
                'success' => false,
                'message' => 'Missing webhook signature header. Webhooks without cryptographic signatures are rejected.',
            ];
        }

        // 1. Resolve Order
        $orderId = $payload['order_id']
            ?? $payload['data']['object']['metadata']['order_id']
            ?? $payload['data']['object']['client_reference_id']
            ?? null;

        $orderNumber = $payload['order_number']
            ?? $payload['data']['object']['metadata']['order_number']
            ?? null;

        $paymentId = $payload['payment_id']
            ?? $payload['data']['object']['metadata']['payment_id']
            ?? null;

        $order = null;
        if ($orderId) {
            $order = Order::find($orderId);
        } elseif ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        } elseif ($paymentId) {
            $order = Order::whereHas('payments', fn ($q) => $q->where('id', $paymentId))->first();
        }

        if (!$order) {
            throw new Exception("Order not found for webhook transaction.");
        }

        // 2. Resolve Payment
        $payment = null;
        if ($paymentId) {
            $payment = Payment::where('id', $paymentId)->where('order_id', $order->id)->first();
        }
        if (!$payment) {
            $payment = $order->latestPayment;
        }
        if (!$payment) {
            throw new Exception("Payment record not found for order.");
        }

        // 3. Delegate cryptographic signature and event status verification to Gateway
        $gateway = $this->getGateway($gatewayName);
        $verification = $gateway->verifyWebhook($payment, $payload, $signature, $rawBody);

        if (empty($verification['verified'])) {
            return [
                'success' => false,
                'message' => $verification['message'] ?? 'Payment verification failed or invalid signature.',
            ];
        }

        // 4. Validate Gateway Transaction Reference
        $transactionId = $verification['transaction_id'] ?? $payload['transaction_id'] ?? $payload['id'] ?? null;
        if (empty($transactionId)) {
            return [
                'success' => false,
                'message' => 'Missing gateway transaction reference in webhook payload.',
            ];
        }

        // 5. Transaction Reference Uniqueness Check across different orders
        $reusedPayment = Payment::where('transaction_id', $transactionId)
            ->where('order_id', '!=', $order->id)
            ->exists();
        $reusedTxn = PaymentTransaction::where('reference_id', $transactionId)
            ->whereHas('payment', fn ($q) => $q->where('order_id', '!=', $order->id))
            ->exists();

        if ($reusedPayment || $reusedTxn) {
            return [
                'success' => false,
                'message' => "Transaction reference '{$transactionId}' has already been used on another order.",
            ];
        }

        // 6. Validate Payment Amount
        if (isset($verification['amount']) && $verification['amount'] !== null) {
            if (abs((float) $verification['amount'] - (float) $payment->amount) > 0.01) {
                return [
                    'success' => false,
                    'message' => "Payment amount mismatch. Expected: \${$payment->amount}, Received: \${$verification['amount']}.",
                ];
            }
        }

        // 7. Validate Currency
        if (!empty($verification['currency'])) {
            if (strtoupper($verification['currency']) !== strtoupper($payment->currency)) {
                return [
                    'success' => false,
                    'message' => "Payment currency mismatch. Expected: {$payment->currency}, Received: {$verification['currency']}.",
                ];
            }
        }

        // 8. Prevent duplicate webhook replays for the SAME payment
        $existingTxn = PaymentTransaction::where('reference_id', $transactionId)
            ->where('payment_id', $payment->id)
            ->where('status', 'success')
            ->first();

        if ($existingTxn) {
            return [
                'success' => true,
                'message' => 'Duplicate webhook event ignored (already processed).',
            ];
        }

        $isTest = in_array(strtolower($gatewayName), ['test', 'test_payment', 'sandbox'], true)
            || !empty($verification['is_test'])
            || str_starts_with((string) $transactionId, 'TEST-');

        // 9. Atomic Payment Confirmation & Inventory Commitment
        // Strict Lock Order: Order -> Payment -> StockReservation -> Inventory
        return DB::transaction(function () use ($order, $payment, $payload, $transactionId, $isTest) {
            $orderLocked = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $paymentLocked = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($orderLocked->payment_status === PaymentStatus::PAID && $paymentLocked->status === PaymentStatus::PAID) {
                return ['success' => true, 'message' => 'Payment already confirmed (duplicate webhook ignored).'];
            }

            if ($orderLocked->status === OrderStatus::CANCELLED) {
                throw new Exception("Cannot confirm payment: Order #{$orderLocked->order_number} has already been cancelled or expired.");
            }

            // Atomically commit inventory reservation exactly once.
            // If reservations are expired, released, or missing, this throws and rolls back.
            $this->inventoryService->commitReservationsForOrder($orderLocked->id);

            $paymentLocked->update([
                'status' => PaymentStatus::PAID,
                'transaction_id' => $transactionId,
                'is_test' => $isTest,
                'paid_at' => now(),
                'gateway_response' => array_merge((array) $paymentLocked->gateway_response, $payload),
            ]);

            $orderLocked->update([
                'payment_status' => PaymentStatus::PAID,
                'status' => OrderStatus::CONFIRMED,
            ]);

            PaymentTransaction::create([
                'payment_id' => $paymentLocked->id,
                'type' => 'capture',
                'amount' => $paymentLocked->amount,
                'reference_id' => $transactionId,
                'status' => 'success',
                'is_test' => $isTest,
                'raw_payload' => $payload,
            ]);

            $prefix = $isTest ? '[TEST SANDBOX] ' : '';
            $orderLocked->statusHistories()->create([
                'previous_status' => $orderLocked->status->value,
                'new_status' => OrderStatus::CONFIRMED->value,
                'label' => 'Payment Confirmed via Webhook',
                'description' => "{$prefix}Webhook validated payment transaction ID: " . $transactionId,
            ]);

            return ['success' => true, 'message' => 'Payment verified successfully.'];
        }, 3);
    }

    /**
     * Authoritative payment confirmation method for verified gateway webhooks or direct capture callbacks.
     */
    public function confirmPayment(
        Order $order,
        string $transactionId,
        ?string $method = null,
        ?array $gatewayResponse = null
    ): Payment {
        $isTest = in_array(strtolower((string) $method), ['test', 'test_payment', 'sandbox'], true)
            || str_starts_with((string) $transactionId, 'TEST-');

        return DB::transaction(function () use ($order, $transactionId, $method, $gatewayResponse, $isTest) {
            $orderLocked = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($orderLocked->status === OrderStatus::CANCELLED) {
                throw new Exception("Cannot confirm payment: Order #{$orderLocked->order_number} has already been cancelled.");
            }

            $payment = Payment::where('order_id', $orderLocked->id)->lockForUpdate()->latest()->first();
            if (!$payment) {
                $payment = Payment::create([
                    'order_id' => $orderLocked->id,
                    'payment_method' => $method ?? $orderLocked->payment_method ?? 'card',
                    'transaction_id' => $transactionId,
                    'amount' => $orderLocked->total,
                    'currency' => 'USD',
                    'status' => PaymentStatus::PENDING,
                    'is_test' => $isTest,
                ]);
            }

            // If already confirmed paid, return idempotently
            if ($orderLocked->payment_status === PaymentStatus::PAID && $payment->status === PaymentStatus::PAID) {
                return $payment;
            }

            // Atomically commit reserved inventory deduction BEFORE marking order/payment as paid
            $this->inventoryService->commitReservationsForOrder($orderLocked->id);

            $payment->update([
                'status' => PaymentStatus::PAID,
                'transaction_id' => $transactionId,
                'is_test' => $isTest,
                'paid_at' => now(),
                'gateway_response' => $gatewayResponse ?? ['transaction_id' => $transactionId, 'status' => 'paid'],
            ]);

            $orderLocked->update([
                'payment_status' => PaymentStatus::PAID,
                'status' => OrderStatus::CONFIRMED,
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'type' => 'capture',
                'amount' => $payment->amount,
                'reference_id' => $transactionId,
                'status' => 'success',
                'is_test' => $isTest,
                'raw_payload' => $gatewayResponse ?? ['transaction_id' => $transactionId],
            ]);

            $prefix = $isTest ? '[TEST SANDBOX] ' : '';
            $orderLocked->statusHistories()->create([
                'previous_status' => OrderStatus::PLACED->value,
                'new_status' => OrderStatus::CONFIRMED->value,
                'label' => 'Payment Verified & Confirmed',
                'description' => "{$prefix}Payment verified for transaction ID: " . $transactionId,
            ]);

            return $payment;
        }, 3);
    }

    public function refundPayment(Payment $payment, float $amount, ?string $reason = null): array
    {
        if ($amount > (float) $payment->amount) {
            throw new Exception("Refund amount (\${$amount}) cannot exceed original payment amount (\${$payment->amount}).");
        }

        $gateway = $this->getGateway($payment->payment_method);
        $result = $gateway->processRefund($payment, $amount, $reason);

        if (!empty($result['success'])) {
            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'type' => 'refund',
                'amount' => $amount,
                'reference_id' => $result['refund_id'] ?? null,
                'status' => 'success',
                'raw_payload' => $result,
            ]);

            $newStatus = ($amount >= (float) $payment->amount)
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED;

            $payment->update(['status' => $newStatus]);
            $payment->order->update(['payment_status' => $newStatus]);

            return $result;
        }

        throw new Exception($result['message'] ?? 'Refund failed at payment gateway.');
    }
}
