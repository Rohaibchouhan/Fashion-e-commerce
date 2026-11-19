<?php

namespace App\Http\Controllers\Api\V1\Reviews;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ReviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    public function index(Request $request, string $productIdOrSlug): JsonResponse
    {
        $product = Product::where('id', $productIdOrSlug)
            ->orWhere('slug', $productIdOrSlug)
            ->firstOrFail();

        $reviews = ProductReview::with('user')
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->latest()
            ->paginate(15);

        $stats = $product->calculateReviewStats();

        $userReview = null;
        $hasPurchased = false;

        if ($user = $request->user('sanctum')) {
            $existing = ProductReview::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $userReview = [
                    'id' => (string) $existing->id,
                    'rating' => (int) $existing->rating,
                    'title' => $existing->title,
                    'comment' => $existing->comment,
                    'isVerifiedPurchase' => (bool) $existing->is_verified_purchase,
                    'isApproved' => (bool) $existing->is_approved,
                    'status' => $existing->is_approved ? 'approved' : 'pending',
                    'createdAt' => $existing->created_at->toISOString(),
                ];
            }

            $hasPurchased = Order::where('customer_id', $user->id)
                ->whereIn('status', [
                    OrderStatus::DELIVERED,
                    OrderStatus::CONFIRMED,
                    OrderStatus::PROCESSING,
                    OrderStatus::DISPATCHED,
                    OrderStatus::IN_TRANSIT,
                ])
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data' => $reviews->map(fn ($r) => [
                'id' => (string) $r->id,
                'author' => $r->user?->name ?? 'Verified Buyer',
                'rating' => (int) $r->rating,
                'date' => $r->created_at->toISOString(),
                'title' => $r->title,
                'comment' => $r->comment,
                'verified' => (bool) $r->is_verified_purchase,
                'adminReply' => $r->admin_reply,
                'images' => $r->images ?? [],
            ]),
            'stats' => $stats,
            'userReview' => $userReview,
            'userEligibility' => [
                'isAuthenticated' => !is_null($request->user('sanctum')),
                'hasPurchased' => $hasPurchased,
                'canReview' => !is_null($request->user('sanctum')),
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request, string $productIdOrSlug): JsonResponse
    {
        $product = Product::where('id', $productIdOrSlug)
            ->orWhere('slug', $productIdOrSlug)
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:200',
            'comment' => 'required|string|max:2000',
            'images' => 'nullable|array',
        ]);

        try {
            $review = $this->reviewService->submitReview($request->user(), $product->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully. It will appear once approved by our moderation team.',
                'data' => [
                    'id' => (string) $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'is_verified_purchase' => $review->is_verified_purchase,
                    'is_approved' => $review->is_approved,
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

