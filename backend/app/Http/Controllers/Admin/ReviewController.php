<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    public function index()
    {
        $reviews = ProductReview::with(['product.images', 'user'])
            ->latest()
            ->paginate(15);

        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(Request $request, ProductReview $review)
    {
        $this->reviewService->moderateReview($review, true, $request->input('admin_reply'));
        return back()->with('success', 'Review approved and published.');
    }

    public function reject(ProductReview $review)
    {
        $this->reviewService->moderateReview($review, false);
        return back()->with('success', 'Review rejected and hidden from store.');
    }
}

