<?php

namespace App\Services\Payments;

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

    /**
     * COD payments cannot be verified through public webhook endpoints.
     * Cash collection must be verified manually by authorized delivery staff or parcel dispatch callbacks.
     */
    public function verifyWebhook(Payment $payment, array $payload, ?string $signature, string $rawBody): array
    {
        return [
            'verified' => false,
            'message' => 'Cash on Delivery (COD) payments cannot be confirmed via public webhooks. Payment collection must be recorded through authorized staff or delivery confirmation.',
        ];
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
