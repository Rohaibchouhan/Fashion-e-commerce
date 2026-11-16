<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    public function process(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Check for idempotency key in header or payload
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->header('Idempotency-Key') ?? $payload['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        try {
            $result = $this->checkoutService->processCheckout($payload, $request->user('sanctum'));

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'data' => [
                    'order' => new OrderResource($result['order']),
                    'user' => $result['user'] ? new UserResource($result['user']) : null,
                    'token' => $result['token'],
                    'is_new_customer' => $result['is_new_customer'] ?? false,
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

