<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Str;

class TestGateway implements PaymentGatewayInterface
{
    /**
     * Initiate a sandbox test payment with controllable scenarios.
     * Strictly forbidden in production environments.
     */
    public function initiatePayment(Order $order, array $paymentData): array
    {
        // 1. Strict environment restriction: Never run in production
        if (app()->environment('production')) {
            throw new Exception("Test payment gateway is strictly forbidden in production environments.");
        }

        // 2. Setting check: must be enabled by an administrator (defaults to disabled)
        if (!Setting::get('test_payment_mode_enabled', config('services.test_payment.enabled', false))) {
            throw new Exception("Test payment gateway is currently disabled by store settings.");
        }

        $scenario = strtolower((string) ($paymentData['scenario'] ?? $paymentData['test_scenario'] ?? 'success'));

        return match ($scenario) {
            'failure', 'fail', 'decline' => [
                'status' => 'failed',
                'transaction_id' => 'TEST-FAIL-' . Str::upper(Str::random(10)),
                'message' => 'Sandbox test payment declined: Simulated insufficient funds or bank decline.',
                'is_test' => true,
                'scenario' => 'failure',
            ],
            'pending' => [
                'status' => 'pending',
                'transaction_id' => 'TEST-PENDING-' . Str::upper(Str::random(10)),
                'message' => 'Sandbox test payment initiated. Awaiting asynchronous capture or verification callback.',
                'is_test' => true,
                'scenario' => 'pending',
            ],
            default => [
                'status' => 'paid',
                'transaction_id' => 'TEST-SUCCESS-' . Str::upper(Str::random(10)),
                'message' => 'Sandbox test payment authorized and captured successfully.',
                'last4' => '9999',
                'card_brand' => 'TestSandbox',
                'is_test' => true,
                'scenario' => 'success',
            ],
        };
    }

    /**
     * Verify test webhook payload.
     * Must still enforce cryptographic signature verification using the test webhook secret.
     */
    public function verifyWebhook(Payment $payment, array $payload, ?string $signature, string $rawBody): array
    {
        if (app()->environment('production')) {
            return [
                'verified' => false,
                'message' => 'Test payment gateway webhooks are strictly forbidden in production.',
            ];
        }

        if (!Setting::get('test_payment_mode_enabled', config('services.test_payment.enabled', false))) {
            return [
                'verified' => false,
                'message' => 'Test payment gateway is currently disabled by store settings.',
            ];
        }

        $secret = config('services.test_payment.webhook_secret') ?? 'whsec_test_payment_sandbox_key_998877';

        if (empty($signature)) {
            return [
                'verified' => false,
                'message' => 'Missing test webhook signature header.',
            ];
        }

        $expectedSig = hash_hmac('sha256', $rawBody, $secret);
        if (!hash_equals($expectedSig, $signature)) {
            return [
                'verified' => false,
                'message' => 'Invalid test webhook signature. Test webhooks cannot bypass cryptographic verification.',
            ];
        }

        $txnId = $payload['transaction_id'] ?? $payload['id'] ?? null;
        if (empty($txnId) || !str_starts_with($txnId, 'TEST-')) {
            return [
                'verified' => false,
                'message' => 'Invalid or missing test transaction reference (must begin with TEST-).',
            ];
        }

        $status = strtolower((string) ($payload['status'] ?? ''));
        if ($status !== 'succeeded' && $status !== 'paid') {
            return [
                'verified' => false,
                'message' => "Test payment is not in captured status (current: {$status}).",
            ];
        }

        return [
            'verified' => true,
            'transaction_id' => $txnId,
            'amount' => isset($payload['amount']) ? (float) $payload['amount'] : (float) $payment->amount,
            'currency' => strtoupper((string) ($payload['currency'] ?? 'USD')),
            'status' => 'succeeded',
            'is_test' => true,
            'message' => 'Test sandbox payment cryptographically verified.',
        ];
    }

    public function processRefund(Payment $payment, float $amount, ?string $reason = null, ?string $idempotencyKey = null): array
    {
        if (app()->environment('production')) {
            throw new Exception("Test payment gateway is strictly forbidden in production.");
        }

        $refundId = $idempotencyKey
            ? 'REF-TEST-' . strtoupper(substr(md5($idempotencyKey), 0, 10))
            : 'REF-TEST-' . Str::upper(Str::random(8));

        return [
            'success' => true,
            'refund_id' => $refundId,
            'idempotency_key' => $idempotencyKey,
            'message' => "Sandbox test refund of \${$amount} recorded successfully.",
            'is_test' => true,
        ];
    }
}
