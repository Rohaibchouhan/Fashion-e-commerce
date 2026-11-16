<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class CodGateway implements PaymentGatewayInterface
{
    public function initiatePayment(Order $order, array $paymentData): array
    {
        return [
            'status' => 'pending',
            'transaction_id' => 'COD-' . Str::upper(Str::random(10)),
            'message' => 'Cash on delivery accepted. Payment will be collected at courier drop-off.',
            'redirect_url' => null,
        ];
    }

    public function verifyPayment(Payment $payment, array $payload): bool
    {
        // COD is verified manually by admin or courier webhook upon parcel delivery
        return true;
    }

    public function processRefund(Payment $payment, float $amount, ?string $reason = null): array
    {
        return [
            'success' => true,
            'refund_id' => 'REF-COD-' . Str::upper(Str::random(8)),
            'message' => 'COD cash/bank refund logged for manual payout.',
        ];
    }
}

