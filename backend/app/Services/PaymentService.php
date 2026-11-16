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
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function getGateway(string $method): PaymentGatewayInterface
    {
        return match (strtolower($method)) {
            'card', 'credit_card', 'debit_card', 'stripe' => new CardGateway(),
            'bank_transfer', 'wire' => new BankTransferGateway(),
            'cod', 'cash_on_delivery' => new CodGateway(),
            default => new CardGateway(),
        };
    }

    public function processOrderPayment(Order $order, array $paymentData): Payment
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $method = $paymentData['type'] ?? $paymentData['method'] ?? $order->payment_method;
            $gateway = $this->getGateway($method);

            $result = $gateway->initiatePayment($order, $paymentData);

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $method,
                'transaction_id' => $result['transaction_id'] ?? null,
                'amount' => $order->total,
                'currency' => 'USD',
                'status' => $result['status'] === 'paid' ? PaymentStatus::PAID : PaymentStatus::PENDING,
                'gateway_response' => $result,
                'paid_at' => $result['status'] === 'paid' ? now() : null,
            ]);

            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'type' => 'charge',
                'amount' => $order->total,
                'reference_id' => $result['transaction_id'] ?? null,
                'status' => $result['status'] === 'paid' ? 'success' : 'pending',
                'raw_payload' => $result,
            ]);

            // Update order status if payment was captured immediately
            if ($result['status'] === 'paid') {
                $order->update([
                    'payment_status' => PaymentStatus::PAID,
                    'status' => OrderStatus::CONFIRMED,
                    'payment_last4' => $result['last4'] ?? $order->payment_last4,
                ]);

                $order->statusHistories()->create([
                    'previous_status' => OrderStatus::PLACED->value,
                    'new_status' => OrderStatus::CONFIRMED->value,
                    'label' => 'Payment Verified & Confirmed',
                    'description' => 'Payment captured via ' . strtoupper($method) . ' (Txn: ' . ($result['transaction_id'] ?? 'N/A') . ').',
                ]);
            }

            return $payment;
        });
    }

    public function handleWebhook(string $gatewayName, array $payload, ?string $signature = null): array
    {
        // Prevent duplicate webhook replays
        $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;
        if ($transactionId) {
            $existingTxn = PaymentTransaction::where('reference_id', $transactionId)
                ->where('status', 'success')
                ->first();

            if ($existingTxn) {
                return [
                    'success' => true,
                    'message' => 'Duplicate webhook event ignored (already processed).',
                ];
            }
        }

        $orderId = $payload['order_id'] ?? null;
        $order = Order::find($orderId);
        if (!$order) {
            throw new Exception("Order not found for webhook transaction.");
        }

        $payment = $order->latestPayment;
        if (!$payment) {
            throw new Exception("Payment record not found for order.");
        }

        $gateway = $this->getGateway($gatewayName);
        $isValid = $gateway->verifyPayment($payment, $payload);

        if ($isValid) {
            DB::transaction(function () use ($order, $payment, $payload, $transactionId) {
                $payment->update([
                    'status' => PaymentStatus::PAID,
                    'paid_at' => now(),
                    'gateway_response' => array_merge((array)$payment->gateway_response, $payload),
                ]);

                $order->update([
                    'payment_status' => PaymentStatus::PAID,
                    'status' => OrderStatus::CONFIRMED,
                ]);

                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'type' => 'capture',
                    'amount' => $payment->amount,
                    'reference_id' => $transactionId,
                    'status' => 'success',
                    'raw_payload' => $payload,
                ]);

                $order->statusHistories()->create([
                    'previous_status' => $order->status->value,
                    'new_status' => OrderStatus::CONFIRMED->value,
                    'label' => 'Payment Confirmed via Webhook',
                    'description' => 'Webhook validated payment transaction ID: ' . ($transactionId ?? 'N/A'),
                ]);
            });

            return ['success' => true, 'message' => 'Payment verified successfully.'];
        }

        return ['success' => false, 'message' => 'Payment verification failed or invalid signature.'];
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

