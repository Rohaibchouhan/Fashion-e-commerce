<?php

namespace App\Http\Controllers\Api\V1\Wishlist;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlistService) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->wishlistService->getWishlist($request->user());

        return response()->json([
            'success' => true,
            'data' => $items->map(fn ($item) => [
                'id' => (string) $item->id,
                'productId' => (string) $item->product_id,
                'product' => new ProductResource($item->product),
                'addedAt' => $item->created_at->toISOString(),
            ]),
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|integer']);

        $item = $this->wishlistService->addToWishlist($request->user(), $request->product_id);

        return response()->json([
            'success' => true,
            'message' => 'Product saved to your wishlist.',
            'data' => [
                'id' => (string) $item->id,
                'productId' => (string) $item->product_id,
            ],
        ], 201);
    }

    public function remove(Request $request, int $productId): JsonResponse
    {
        $this->wishlistService->removeFromWishlist($request->user(), $productId);

        return response()->json([
            'success' => true,
            'message' => 'Product removed from your wishlist.',
        ]);
    }

    public function check(Request $request, int $productId): JsonResponse
    {
        $inWishlist = $this->wishlistService->isInWishlist($request->user(), $productId);

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
        ]);
    }
}

