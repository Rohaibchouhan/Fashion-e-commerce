<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function get(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->query('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1',
            'size' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        try {
            $this->cartService->addItem(
                $cart,
                $validated['product_id'],
                $validated['variant_id'] ?? null,
                $validated['quantity'] ?? 1,
                $validated['size'] ?? null,
                $validated['color'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Item added to your shopping cart.',
                'data' => new CartResource($cart->fresh(['items.product.images', 'items.variant'])),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        try {
            $updatedCart = $this->cartService->updateItemQuantity($cart, $itemId, $validated['quantity']);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated.',
                'data' => new CartResource($updatedCart),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        $updatedCart = $this->cartService->removeItem($cart, $itemId);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'data' => new CartResource($updatedCart),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        $this->cartService->clearCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Shopping cart cleared.',
            'data' => new CartResource($cart->fresh(['items'])),
        ]);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        try {
            $result = $this->cartService->applyCoupon($cart, $request->code);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new CartResource($result['cart']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $cart = $this->cartService->getOrCreateCart($request->user('sanctum'), $sessionId);

        $updatedCart = $this->cartService->removeCoupon($cart);

        return response()->json([
            'success' => true,
            'message' => 'Coupon removed.',
            'data' => new CartResource($updatedCart),
        ]);
    }
}

