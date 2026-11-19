<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);
    }

    public function test_catalog_endpoint_returns_active_products(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'compareAtPrice',
                        'inStock',
                    ],
                ],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_can_filter_products_by_gender(): void
    {
        $response = $this->getJson('/api/v1/products?gender=men');

        $response->assertStatus(200);
        $products = $response->json('data');
        $this->assertNotEmpty($products);

        foreach ($products as $p) {
            $this->assertContains($p['gender'] ?? 'men', ['men', 'unisex']);
        }
    }

    public function test_can_search_products_by_keyword(): void
    {
        $response = $this->getJson('/api/v1/products?search=Tee');

        $response->assertStatus(200);
        $products = $response->json('data');
        $this->assertNotEmpty($products);

        foreach ($products as $p) {
            $this->assertTrue(
                stripos($p['name'], 'Tee') !== false || stripos($p['sku'], 'Tee') !== false
            );
        }
    }

    public function test_can_retrieve_single_product_details_by_slug(): void
    {
        $response = $this->getJson('/api/v1/products/relaxed-fit-tee');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'variants' => [
                        '*' => ['id', 'sku', 'size', 'color', 'stock'],
                    ],
                    'sizes',
                    'colors',
                    'images',
                ],
            ]);

        $this->assertEquals('relaxed-fit-tee', $response->json('data.slug'));
    }

    public function test_inactive_products_are_hidden_from_public_catalog(): void
    {
        $product = Product::where('slug', 'relaxed-fit-tee')->first();
        $product->update(['is_active' => false]);

        $detailRes = $this->getJson('/api/v1/products/relaxed-fit-tee');
        $detailRes->assertStatus(404);

        $listRes = $this->getJson('/api/v1/products?search=Relaxed+Fit');
        $slugs = collect($listRes->json('data'))->pluck('slug');
        $this->assertNotContains('relaxed-fit-tee', $slugs);
    }

    public function test_search_is_strictly_case_insensitive(): void
    {
        $resLower = $this->getJson('/api/v1/products?search=linen');
        $resUpper = $this->getJson('/api/v1/products?search=LINEN');
        $resTitle = $this->getJson('/api/v1/products?search=Linen');

        $resLower->assertStatus(200);
        $resUpper->assertStatus(200);
        $resTitle->assertStatus(200);

        $countLower = $resLower->json('meta.total');
        $countUpper = $resUpper->json('meta.total');
        $countTitle = $resTitle->json('meta.total');

        $this->assertGreaterThan(0, $countLower);
        $this->assertEquals($countLower, $countUpper, 'Uppercase search must return identical total count as lowercase.');
        $this->assertEquals($countLower, $countTitle, 'Titlecase search must return identical total count as lowercase.');
    }

    public function test_multi_word_search_matches_products_containing_terms(): void
    {
        // "Linen Shirt" should match "Linen Blend Shirt"
        $response = $this->getJson('/api/v1/products?search=Linen+Shirt');
        $response->assertStatus(200);
        $this->assertGreaterThan(0, $response->json('meta.total'));

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue(
            $names->contains(fn ($name) => stripos($name, 'Linen') !== false && stripos($name, 'Shirt') !== false)
        );
    }

    public function test_invalid_search_returns_zero_items_cleanly(): void
    {
        $response = $this->getJson('/api/v1/products?search=xyznonexistentquery999');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('meta.total'));
        $this->assertEmpty($response->json('data'));
    }

    public function test_search_matches_chino_pants_to_easy_chino_pants(): void
    {
        $response = $this->getJson('/api/v1/products?search=Chino+Pants');
        $response->assertStatus(200);
        $this->assertGreaterThan(0, $response->json('meta.total'));

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue(
            $names->contains('Easy Chino Pants'),
            'Searching for "Chino Pants" must find "Easy Chino Pants"'
        );
    }

    public function test_product_detail_returns_colors_with_images_and_reviews_list(): void
    {
        $product = Product::where('slug', 'relaxed-fit-tee')->first();
        $this->assertNotNull($product);

        $response = $this->getJson('/api/v1/products/' . $product->slug);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'colors',
                    'variants',
                    'reviews',
                ],
            ]);

        $colors = $response->json('data.colors');
        $this->assertNotEmpty($colors);

        $reviews = $response->json('data.reviews');
        $this->assertIsArray($reviews);
    }
}
