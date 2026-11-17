<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function initiatePayment(Order $order, array $paymentData): array;

    /**
     * Authoritatively verify gateway webhook payload and cryptographic signature.
     *
     * @return array{verified: bool, transaction_id?: ?string, amount?: ?float, currency?: ?string, status?: ?string, message: string}
     */
    public function verifyWebhook(Payment $payment, array $payload, ?string $signature, string $rawBody): array;

    public function processRefund(Payment $payment, float $amount, ?string $reason = null): array;
}

