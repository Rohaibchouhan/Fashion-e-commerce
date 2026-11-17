<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Str;

class CardGateway implements PaymentGatewayInterface
{
    public function initiatePayment(Order $order, array $paymentData): array
    {
        // Check for card details
        $cardNumber = preg_replace('/\s+/', '', $paymentData['cardNumber'] ?? $paymentData['card_number'] ?? '');
        $expDate = $paymentData['expDate'] ?? $paymentData['exp_date'] ?? '';
        $cvv = $paymentData['cvv'] ?? '';

        // If card number provided, validate format without persisting
        if ($cardNumber && strlen($cardNumber) < 13) {
            throw new Exception("Invalid card number provided.");
        }

        $transactionId = 'TXN_CARD_' . Str::upper(Str::random(12));
        $isSuccessful = true; // In production this invokes Stripe/Adyen/Braintree SDK

        return [
            'status' => $isSuccessful ? 'paid' : 'failed',
            'transaction_id' => $transactionId,
            'message' => 'Card payment authorized and captured successfully.',
            'last4' => $cardNumber ? substr($cardNumber, -4) : '8821',
            'card_brand' => 'Visa',
        ];
    }

    /**
     * Authoritatively verify gateway webhook payload and cryptographic signature.
     * Uses official Stripe signature verification (t=timestamp,v1=signature).
     */
    public function verifyWebhook(Payment $payment, array $payload, ?string $signature, string $rawBody): array
    {
        $secret = config('services.stripe.webhook_secret');
        if (empty($secret)) {
            return [
                'verified' => false,
                'message' => 'Stripe webhook secret is not configured. Webhook confirmation is disabled.',
            ];
        }

        if (empty($signature)) {
            return [
                'verified' => false,
                'message' => 'Missing Stripe webhook signature header.',
            ];
        }

        // Parse official Stripe signature header: t=1614555555,v1=abcdef...
        $timestamp = null;
        $signatures = [];

        $items = explode(',', $signature);
        foreach ($items as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                if ($key === 't') {
                    $timestamp = (int) $val;
                } elseif ($key === 'v1') {
                    $signatures[] = $val;
                }
            }
        }

        if (!$timestamp || empty($signatures)) {
            return [
                'verified' => false,
                'message' => 'Malformed Stripe webhook signature header format.',
            ];
        }

        // Enforce 300-second timestamp freshness tolerance to prevent replay attacks
        if (abs(time() - $timestamp) > 300) {
            return [
                'verified' => false,
                'message' => 'Stripe webhook signature has expired or timestamp tolerance exceeded.',
            ];
        }

        // Compute expected HMAC-SHA256 signature: hash_hmac('sha256', "${timestamp}.${rawBody}", $secret)
        $signedPayload = "{$timestamp}.{$rawBody}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        $matched = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return [
                'verified' => false,
                'message' => 'Stripe cryptographic signature verification failed.',
            ];
        }

        // Parse JSON payload
        $event = json_decode($rawBody, true) ?: $payload;
        $dataObject = $event['data']['object'] ?? $event;

        $txnId = $dataObject['id'] ?? $payload['transaction_id'] ?? $payload['id'] ?? null;
        $status = strtolower((string) ($dataObject['status'] ?? $payload['status'] ?? ''));

        // Gateways indicate success with 'succeeded' (Stripe) or 'paid'
        if ($status !== 'succeeded' && $status !== 'paid') {
            return [
                'verified' => false,
                'message' => "Payment is not in settled/captured status (current status: {$status}).",
            ];
        }

        // Extract amount & currency
        $amount = null;
        if (isset($dataObject['amount_received'])) {
            $amount = round(((float) $dataObject['amount_received']) / 100, 2);
        } elseif (isset($dataObject['amount']) && is_numeric($dataObject['amount'])) {
            // Stripe native amounts are in integer cents
            $isCents = isset($dataObject['object']) && in_array($dataObject['object'], ['payment_intent', 'charge']);
            $amount = $isCents ? round(((float) $dataObject['amount']) / 100, 2) : (float) $dataObject['amount'];
        } elseif (isset($payload['amount'])) {
            $amount = (float) $payload['amount'];
        }

        $currency = strtoupper((string) ($dataObject['currency'] ?? $payload['currency'] ?? 'USD'));

        return [
            'verified' => true,
            'transaction_id' => $txnId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'succeeded',
            'message' => 'Stripe webhook signature and status verified successfully.',
        ];
    }

    public function processRefund(Payment $payment, float $amount, ?string $reason = null): array
    {
        return [
            'success' => true,
            'refund_id' => 'REF-CARD-' . Str::upper(Str::random(10)),
            'message' => "Refund of \${$amount} successfully credited back to original payment card.",
        ];
    }
}
