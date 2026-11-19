<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Exceptions\IdempotencyConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    /**
     * Preview / Calculate authoritative order totals, shipping, coupon discount, and tax.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if ($user && $user->status !== 'active') {
            $user->currentAccessToken()?->delete();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact customer support.',
            ], 403);
        }

        try {
            $quote = $this->checkoutService->calculateQuote($request->all(), $user);

            return response()->json([
                'success' => true,
                'data' => $quote,
            ]);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout exception: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process checkout and create an authoritative order with reserved inventory.
     */
    public function process(CheckoutRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // Check for idempotency key in header or payload
        $idempotencyKey = $request->header('X-Idempotency-Key')
            ?? $request->header('Idempotency-Key')
            ?? $request->input('idempotency_key')
            ?? null;

        if ($idempotencyKey) {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        // Check for guest session identifier
        $guestSessionId = $request->header('X-Session-Id')
            ?? $request->header('X-Guest-Session-Id')
            ?? $request->input('session_id')
            ?? $request->input('guest_session_id')
            ?? null;

        $user = $request->user('sanctum');
        if ($user && $user->status !== 'active') {
            $user->currentAccessToken()?->delete();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact customer support.',
            ], 403);
        }

        try {
            $result = $this->checkoutService->processCheckout($payload, $user, $guestSessionId);

            $isReplay = $result['idempotent_replay'] ?? false;

            return response()->json([
                'success' => true,
                'message' => $isReplay ? 'Order previously placed (idempotent replay).' : 'Order placed successfully!',
                'data' => [
                    'order' => new OrderResource($result['order']),
                    'user' => $result['user'] ? new UserResource($result['user']) : null,
                    'token' => $result['token'],
                    'is_new_customer' => $result['is_new_customer'] ?? false,
                    'idempotent_replay' => $isReplay,
                ],
            ], 201);
        } catch (IdempotencyConflictException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'idempotency_conflict',
            ], 409);
        } catch (ValidationException $e) {
            $isAccountExists = isset($e->errors()['email']) && str_contains(implode(' ', $e->errors()['email']), 'sign in');
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'requires_auth' => $isAccountExists,
            ], 422);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout exception: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
