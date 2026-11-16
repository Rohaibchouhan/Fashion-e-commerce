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

    public function verifyPayment(Payment $payment, array $payload): bool
    {
        return !empty($payload['bank_reference']);
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

