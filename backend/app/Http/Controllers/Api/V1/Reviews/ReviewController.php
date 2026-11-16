<?php

namespace App\Http\Controllers\Api\V1\Reviews;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\ReviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    public function index(int $productId): JsonResponse
    {
        $reviews = ProductReview::with('user')
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reviews->map(fn ($r) => [
                'id' => (string) $r->id,
                'author' => $r->user->name,
                'rating' => $r->rating,
                'date' => $r->created_at->toISOString(),
                'title' => $r->title,
                'comment' => $r->comment,
                'verified' => (bool) $r->is_verified_purchase,
                'adminReply' => $r->admin_reply,
                'images' => $r->images ?? [],
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:200',
            'comment' => 'required|string|max:2000',
            'images' => 'nullable|array',
        ]);

        try {
            $review = $this->reviewService->submitReview($request->user(), $productId, $validated);

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

