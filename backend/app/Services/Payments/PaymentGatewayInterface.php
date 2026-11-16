<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function initiatePayment(Order $order, array $paymentData): array;

    public function verifyPayment(Payment $payment, array $payload): bool;

    public function processRefund(Payment $payment, float $amount, ?string $reason = null): array;
}

