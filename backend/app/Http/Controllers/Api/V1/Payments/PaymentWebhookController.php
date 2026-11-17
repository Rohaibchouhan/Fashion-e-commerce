<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $signature = $request->header('Stripe-Signature')
            ?? $request->header('X-Webhook-Signature')
            ?? $request->header('X-Signature');

        $rawBody = $request->getContent();

        try {
            $result = $this->paymentService->handleWebhook(
                $gateway,
                $request->all(),
                $signature,
                $rawBody
            );

            $statusCode = ($result['success'] ?? false) ? 200 : 400;

            return response()->json($result, $statusCode);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
