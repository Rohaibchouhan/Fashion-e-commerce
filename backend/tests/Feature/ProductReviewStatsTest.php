<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductReviewStatsTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);

        $category = Category::first();

        $this->customer = User::factory()->create([
            'email' => 'test.reviewer@example.com',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Test Chino Pants',
            'slug' => 'test-chino-pants',
            'sku' => 'TCP-001',
            'category_id' => $category->id,
            'regular_price' => 50.00,
            'is_active' => true,
            'rating' => 0.00,
            'review_count' => 0,
        ]);
    }

    public function test_zero_review_product_has_zero_rating_and_empty_distribution(): void
    {
        $stats = $this->product->calculateReviewStats();

        $this->assertEquals(0, $stats['totalCount']);
        $this->assertEquals(0.00, $stats['averageRating']);
        $this->assertEquals(0, $stats['counts']['5']);
        $this->assertEquals(0, $stats['percentages']['5']);

        // Check API response for zero-review product
        $response = $this->getJson("/api/v1/products/{$this->product->slug}");
        $response->assertStatus(200)
            ->assertJsonPath('data.rating', 0)
            ->assertJsonPath('data.reviewCount', 0)
            ->assertJsonPath('data.ratingStats.totalCount', 0)
            ->assertJsonPath('data.ratingStats.averageRating', 0)
            ->assertJsonPath('data.ratingStats.breakdown.0.count', 0)
            ->assertJsonPath('data.ratingStats.breakdown.0.percentage', 0);
    }

    public function test_single_review_calculates_exact_rating_and_hundred_percent_distribution(): void
    {
        ProductReview::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'rating' => 5,
            'title' => 'Excellent pants',
            'comment' => 'Great stretch and fit',
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        $this->product->refresh();

        $this->assertEquals(1, $this->product->review_count);
        $this->assertEquals(5.00, (float) $this->product->rating);

        $stats = $this->product->calculateReviewStats();
        $this->assertEquals(1, $stats['totalCount']);
        $this->assertEquals(5.00, $stats['averageRating']);
        $this->assertEquals(1, $stats['counts']['5']);
        $this->assertEquals(100.0, $stats['percentages']['5']);
        $this->assertEquals(0, $stats['counts']['4']);
        $this->assertEquals(0.0, $stats['percentages']['4']);
    }

    public function test_multiple_reviews_with_different_star_ratings_calculate_exact_distribution(): void
    {
        $users = User::factory()->count(4)->create();

        // 1x 5-star, 2x 4-star, 1x 2-star (Total = 4, Sum = 15, Average = 3.75)
        $ratings = [5, 4, 4, 2];

        foreach ($ratings as $idx => $star) {
            ProductReview::create([
                'product_id' => $this->product->id,
                'user_id' => $users[$idx]->id,
                'rating' => $star,
                'title' => "Review {$idx}",
                'comment' => "Comment {$idx}",
                'is_verified_purchase' => true,
                'is_approved' => true,
            ]);
        }

        $this->product->refresh();

        // Stored columns
        $this->assertEquals(4, $this->product->review_count);
        $this->assertEquals(3.75, (float) $this->product->rating);

        // Dynamic stats
        $stats = $this->product->calculateReviewStats();
        $this->assertEquals(4, $stats['totalCount']);
        $this->assertEquals(3.75, $stats['averageRating']);

        // 5-star: 1/4 = 25%
        $this->assertEquals(1, $stats['counts']['5']);
        $this->assertEquals(25.0, $stats['percentages']['5']);

        // 4-star: 2/4 = 50%
        $this->assertEquals(2, $stats['counts']['4']);
        $this->assertEquals(50.0, $stats['percentages']['4']);

        // 3-star: 0/4 = 0%
        $this->assertEquals(0, $stats['counts']['3']);
        $this->assertEquals(0.0, $stats['percentages']['3']);

        // 2-star: 1/4 = 25%
        $this->assertEquals(1, $stats['counts']['2']);
        $this->assertEquals(25.0, $stats['percentages']['2']);

        // 1-star: 0/4 = 0%
        $this->assertEquals(0, $stats['counts']['1']);
        $this->assertEquals(0.0, $stats['percentages']['1']);

        // Check reviews endpoint
        $res = $this->getJson("/api/v1/products/{$this->product->id}/reviews");
        $res->assertStatus(200)
            ->assertJsonPath('stats.totalCount', 4)
            ->assertJsonPath('stats.averageRating', 3.75)
            ->assertJsonPath('meta.total', 4);
    }

    public function test_pending_reviews_are_excluded_from_stats(): void
    {
        ProductReview::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'rating' => 5,
            'title' => 'Pending review',
            'comment' => 'Awaiting moderation',
            'is_verified_purchase' => true,
            'is_approved' => false,
        ]);

        $this->product->refresh();

        $this->assertEquals(0, $this->product->review_count);
        $this->assertEquals(0.00, (float) $this->product->rating);

        $stats = $this->product->calculateReviewStats();
        $this->assertEquals(0, $stats['totalCount']);
        $this->assertEquals(0.00, $stats['averageRating']);

        $res = $this->getJson("/api/v1/products/{$this->product->id}/reviews");
        $res->assertStatus(200)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_approving_review_dynamically_updates_stats_and_invalidates_cache(): void
    {
        $review = ProductReview::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'rating' => 4,
            'title' => 'Good product',
            'comment' => 'Very satisfied',
            'is_verified_purchase' => true,
            'is_approved' => false,
        ]);

        // Pre-approval
        $this->product->refresh();
        $this->assertEquals(0, $this->product->review_count);

        // Approve review
        $review->update(['is_approved' => true]);

        // Post-approval
        $this->product->refresh();
        $this->assertEquals(1, $this->product->review_count);
        $this->assertEquals(4.00, (float) $this->product->rating);

        $stats = $this->product->calculateReviewStats();
        $this->assertEquals(1, $stats['totalCount']);
        $this->assertEquals(4.00, $stats['averageRating']);
        $this->assertEquals(100.0, $stats['percentages']['4']);
    }

    public function test_hiding_or_rejecting_review_recalculates_stats_and_excludes_it(): void
    {
        $review = ProductReview::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'rating' => 3,
            'title' => 'Average pants',
            'comment' => 'Nothing special',
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        $this->product->refresh();
        $this->assertEquals(1, $this->product->review_count);
        $this->assertEquals(3.00, (float) $this->product->rating);

        // Reject / Hide
        $review->update(['is_approved' => false]);

        $this->product->refresh();
        $this->assertEquals(0, $this->product->review_count);
        $this->assertEquals(0.00, (float) $this->product->rating);
    }

    public function test_deleting_review_recalculates_stats(): void
    {
        $review = ProductReview::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'rating' => 5,
            'title' => 'To be deleted',
            'comment' => 'Will be removed',
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        $this->product->refresh();
        $this->assertEquals(1, $this->product->review_count);

        $review->delete();

        $this->product->refresh();
        $this->assertEquals(0, $this->product->review_count);
        $this->assertEquals(0.00, (float) $this->product->rating);
    }
}
