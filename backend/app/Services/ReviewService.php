<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Exception;

class ReviewService
{
    public function submitReview(User $user, int $productId, array $data): ProductReview
    {
        Product::findOrFail($productId);

        $rating = max(1, min(5, (int) $data['rating']));

        // Check if verified purchase: customer has an order containing this product that is completed or delivered
        $purchasedOrder = Order::where('customer_id', $user->id)
            ->whereIn('status', [OrderStatus::DELIVERED, OrderStatus::CONFIRMED, OrderStatus::PROCESSING, OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT])
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->first();

        $isVerified = !is_null($purchasedOrder);

        $review = ProductReview::updateOrCreate(
            [
                'product_id' => $productId,
                'user_id' => $user->id,
            ],
            [
                'order_id' => $purchasedOrder?->id,
                'rating' => $rating,
                'title' => $data['title'],
                'comment' => $data['comment'] ?? $data['description'] ?? '',
                'is_verified_purchase' => $isVerified,
                'is_approved' => false, // Requires admin moderation by default
                'images' => $data['images'] ?? null,
            ]
        );

        return $review;
    }

    public function moderateReview(ProductReview $review, bool $approve, ?string $adminReply = null): ProductReview
    {
        $review->update([
            'is_approved' => $approve,
            'admin_reply' => $adminReply ?? $review->admin_reply,
        ]);

        $review->updateProductStats();

        return $review;
    }
}

