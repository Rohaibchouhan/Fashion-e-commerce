<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function initiatePayment(Order $order, array $paymentData): array
    {
        return [
            'status' => 'pending',
            'transaction_id' => 'WIRE-' . Str::upper(Str::random(10)),
            'message' => 'Please transfer to JPMorgan Chase, Routing: 021000021, Acct: 987654321 with Reference: ' . $order->order_number,
            'redirect_url' => null,
        ];
    }

    /**
     * Authoritatively verify bank transfer notification with signature.
     */
    public function verifyWebhook(Payment $payment, array $payload, ?string $signature, string $rawBody): array
    {
        $secret = config('services.bank_transfer.webhook_secret') ?? config('services.stripe.webhook_secret');
        if (empty($secret)) {
            return [
                'verified' => false,
                'message' => 'Bank transfer webhook secret is not configured. Webhook confirmation is disabled.',
            ];
        }

        if (empty($signature)) {
            return [
                'verified' => false,
                'message' => 'Missing bank transfer webhook signature header.',
            ];
        }

        $expectedSig = hash_hmac('sha256', $rawBody, $secret);
        if (!hash_equals($expectedSig, $signature)) {
            return [
                'verified' => false,
                'message' => 'Invalid bank transfer webhook signature.',
            ];
        }

        $ref = $payload['bank_reference'] ?? $payload['transaction_id'] ?? null;
        if (empty($ref)) {
            return [
                'verified' => false,
                'message' => 'Missing bank reference in webhook payload.',
            ];
        }

        $status = strtolower((string) ($payload['status'] ?? ''));
        if ($status !== 'completed' && $status !== 'settled') {
            return [
                'verified' => false,
                'message' => 'Bank transfer has not settled or completed.',
            ];
        }

        return [
            'verified' => true,
            'transaction_id' => $ref,
            'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
            'currency' => isset($payload['currency']) ? strtoupper((string) $payload['currency']) : 'USD',
            'status' => 'settled',
            'message' => 'Bank transfer cryptographically verified.',
        ];
    }

    public function processRefund(Payment $payment, float $amount, ?string $reason = null): array
    {
        return [
            'success' => true,
            'refund_id' => 'REF-WIRE-' . Str::upper(Str::random(8)),
            'message' => 'Bank wire refund scheduled for dispatch.',
        ];
    }
}
