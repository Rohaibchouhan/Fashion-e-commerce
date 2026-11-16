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
}
