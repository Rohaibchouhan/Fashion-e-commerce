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

        // If card number provided, validate format
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

    public function verifyPayment(Payment $payment, array $payload): bool
    {
        return isset($payload['status']) && $payload['status'] === 'success';
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

