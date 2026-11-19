<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerReviewSubmissionTest extends TestCase
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
            'email' => 'customer.review.test@example.com',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Signature Cotton Overshirt',
            'slug' => 'signature-cotton-overshirt',
            'sku' => 'SCO-101',
            'category_id' => $category->id,
            'regular_price' => 75.00,
            'is_active' => true,
            'rating' => 0.00,
            'review_count' => 0,
        ]);
    }

    public function test_guest_cannot_submit_review(): void
    {
        $response = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Guest review attempt',
            'comment' => 'Should be unauthorized',
        ]);

        $response->assertStatus(401);
    }

    public function test_form_validation_enforces_required_fields_and_sensible_ranges(): void
    {
        Sanctum::actingAs($this->customer);

        // Missing all required fields
        $response = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating', 'title', 'comment']);

        // Invalid rating (out of 1-5 range)
        $invalidRating = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 6,
            'title' => 'Valid Title',
            'comment' => 'Valid description here',
        ]);
        $invalidRating->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        // Exceeding max length on title
        $longTitle = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 5,
            'title' => str_repeat('A', 201),
            'comment' => 'Valid description here',
        ]);
        $longTitle->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_customer_without_eligible_purchase_submits_unverified_review(): void
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 4,
            'title' => 'Great fabric feel',
            'comment' => 'Tried this in store, excellent heavy cotton texture.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.title', 'Great fabric feel')
            ->assertJsonPath('data.is_verified_purchase', false)
            ->assertJsonPath('data.is_approved', false);

        // Ensure pending review does NOT increment public product stats
        $this->product->refresh();
        $this->assertEquals(0, $this->product->review_count);
        $this->assertEquals(0.00, (float) $this->product->rating);
    }

    public function test_customer_with_delivered_order_submits_verified_review(): void
    {
        // Create delivered order for this customer and product
        $order = Order::create([
            'order_number' => 'ORD-REV-VERIFIED-01',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'subtotal' => 75.00,
            'shipping' => 0.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 75.00,
            'currency' => 'USD',
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'street' => '123 Test Ave',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            'billing_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'street' => '123 Test Ave',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'product_slug' => $this->product->slug,
            'unit_price' => 75.00,
            'quantity' => 1,
            'subtotal' => 75.00,
            'total' => 75.00,
        ]);

        Sanctum::actingAs($this->customer);

        $response = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Incredible tailoring and fabric weight',
            'comment' => 'Arrived quickly. Perfect drape and fits true to size.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_verified_purchase', true)
            ->assertJsonPath('data.is_approved', false);

        $review = ProductReview::where('product_id', $this->product->id)
            ->where('user_id', $this->customer->id)
            ->first();

        $this->assertNotNull($review);
        $this->assertTrue((bool) $review->is_verified_purchase);
        $this->assertEquals($order->id, $review->order_id);
    }

    public function test_submitting_twice_updates_existing_review_preventing_duplicate(): void
    {
        Sanctum::actingAs($this->customer);

        // Initial submission
        $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 3,
            'title' => 'Initial Impression',
            'comment' => 'Initial comment before wash',
        ])->assertStatus(201);

        // Subsequent update
        $update = $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 5,
            'title' => 'Updated Impression',
            'comment' => 'Updated comment after wash - holds up wonderfully!',
        ]);

        $update->assertStatus(201);

        $count = ProductReview::where('product_id', $this->product->id)
            ->where('user_id', $this->customer->id)
            ->count();

        $this->assertEquals(1, $count);

        $review = ProductReview::where('product_id', $this->product->id)
            ->where('user_id', $this->customer->id)
            ->first();

        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Updated Impression', $review->title);
    }

    public function test_reviews_index_endpoint_returns_user_review_and_eligibility(): void
    {
        Sanctum::actingAs($this->customer);

        // Submit review
        $this->postJson("/api/v1/products/{$this->product->slug}/reviews", [
            'rating' => 5,
            'title' => 'My Review',
            'comment' => 'Detailed comment for testing index',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/products/{$this->product->slug}/reviews");

        $response->assertStatus(200)
            ->assertJsonPath('userReview.title', 'My Review')
            ->assertJsonPath('userReview.rating', 5)
            ->assertJsonPath('userReview.status', 'pending')
            ->assertJsonPath('userEligibility.isAuthenticated', true);
    }
}
