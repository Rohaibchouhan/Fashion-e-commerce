<?php

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(protected ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getFilteredProducts($request->all());

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(string $slugOrId): JsonResponse
    {
        $product = $this->productService->getProductBySlugOrId($slugOrId);

        if (!$product || !$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or unavailable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::with(['category', 'variants', 'images'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function trending(): JsonResponse
    {
        $products = Product::with(['category', 'variants', 'images'])
            ->where('is_active', true)
            ->where('is_trending', true)
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function bestsellers(): JsonResponse
    {
        $products = Product::with(['category', 'variants', 'images'])
            ->where('is_active', true)
            ->where('is_bestseller', true)
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function newArrivals(): JsonResponse
    {
        $products = Product::with(['category', 'variants', 'images'])
            ->where('is_active', true)
            ->where('is_new', true)
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }
}

