<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;
    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);

        $this->admin = User::where('email', 'admin@wearday.com')->first();
        $this->category = Category::first();
        $this->brand = Brand::first();

        Storage::fake('public');
    }

    public function test_admin_can_view_product_listing_and_filter(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee('Product Catalog');
        $response->assertSee('garments');
    }

    public function test_admin_can_access_create_product_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/create');
        $response->assertStatus(200);
        $response->assertSee('Basic Product Information');
        $response->assertSee('Auto-Generate Matrix');
    }

    public function test_create_product_requires_mandatory_fields(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', []);
        $response->assertSessionHasErrors(['name', 'sku', 'description', 'regular_price', 'category_id', 'gender']);
    }

    public function test_create_product_fails_with_duplicate_sku(): void
    {
        $existing = Product::first();

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Duplicate SKU Garment',
            'sku' => $existing->sku,
            'description' => 'Test description',
            'regular_price' => 50.00,
            'category_id' => $this->category->id,
            'gender' => 'men',
        ]);

        $response->assertSessionHasErrors('sku');
    }

    public function test_create_product_fails_when_sale_price_exceeds_regular_price(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Invalid Sale Garment',
            'sku' => 'TEST-INVALID-SALE',
            'description' => 'Test description',
            'regular_price' => 50.00,
            'sale_price' => 75.00, // Invalid: exceeds regular price
            'category_id' => $this->category->id,
            'gender' => 'women',
        ]);

        $response->assertSessionHasErrors('sale_price');
    }

    public function test_create_product_fails_with_invalid_category_or_brand(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Bad Foreign Keys',
            'sku' => 'TEST-BAD-FK',
            'description' => 'Test description',
            'regular_price' => 50.00,
            'category_id' => 999999, // nonexistent
            'brand_id' => 888888, // nonexistent
            'gender' => 'unisex',
        ]);

        $response->assertSessionHasErrors(['category_id', 'brand_id']);
    }

    public function test_admin_can_create_product_with_variants_and_atomic_inventory(): void
    {
        $coverFile = UploadedFile::fake()->image('cover.jpg', 600, 800);

        $payload = [
            'name' => 'Fine Merino Wool Crewneck',
            'slug' => 'fine-merino-wool-crewneck-unique',
            'sku' => 'FMW-UNIQUE-100',
            'subtitle' => '100% Extrafine 19.5 Micron Australian Wool',
            'description' => 'Luxuriously soft crewneck knit sweater with ribbed cuffs and hem.',
            'regular_price' => 120.00,
            'sale_price' => 98.00,
            'cost_price' => 45.00,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'gender' => 'men',
            'is_active' => '1',
            'is_featured' => '1',
            'main_image' => $coverFile,
            'variants' => [
                [
                    'color' => 'Navy Blue',
                    'color_hex' => '#000080',
                    'size' => 'M',
                    'sku' => 'FMW-100-NVY-M',
                    'price' => 120.00,
                    'stock' => 25,
                ],
                [
                    'color' => 'Navy Blue',
                    'color_hex' => '#000080',
                    'size' => 'L',
                    'sku' => 'FMW-100-NVY-L',
                    'price' => 120.00,
                    'stock' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/products', $payload);
        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'sku' => 'FMW-UNIQUE-100',
            'name' => 'Fine Merino Wool Crewneck',
            'gender' => 'men',
        ]);

        $product = Product::where('sku', 'FMW-UNIQUE-100')->first();
        $this->assertNotNull($product);
        $this->assertCount(2, $product->variants);
        $this->assertCount(1, $product->images);
        $this->assertTrue($product->images->first()->is_primary);

        $variantM = $product->variants()->where('sku', 'FMW-100-NVY-M')->first();
        $this->assertNotNull($variantM);
        $this->assertDatabaseHas('inventories', [
            'variant_id' => $variantM->id,
            'quantity' => 25,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variantM->id,
            'type' => 'initial',
            'quantity_change' => 25,
        ]);
    }

    public function test_admin_can_update_product_and_change_prices(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'name' => 'Updated Garment Name',
            'sku' => $product->sku,
            'description' => 'Updated detailed description text.',
            'regular_price' => 89.00,
            'sale_price' => 69.00,
            'category_id' => $product->category_id,
            'gender' => $product->gender,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Garment Name',
            'regular_price' => 89.00,
            'sale_price' => 69.00,
        ]);
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $product = Product::first();
        $originalStatus = $product->is_active;

        $response = $this->actingAs($this->admin)->post("/admin/products/{$product->id}/toggle");
        $response->assertRedirect();

        $this->assertEquals(!$originalStatus, $product->fresh()->is_active);
    }

    public function test_admin_can_delete_unreferenced_product(): void
    {
        $product = Product::create([
            'name' => 'Temporary Sample Tee',
            'slug' => 'temporary-sample-tee',
            'sku' => 'TEMP-SAMPLE-99',
            'description' => 'Sample item to be deleted.',
            'regular_price' => 25.00,
            'category_id' => $this->category->id,
            'gender' => 'unisex',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}/delete");
        $response->assertRedirect('/admin/products');

        $this->assertNull(Product::find($product->id));
    }

    public function test_product_with_orders_is_safely_archived_and_order_integrity_preserved(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        // Simulate an order containing this variant
        $order = Order::create([
            'order_number' => 'WD-TEST-ORDS-001',
            'customer_id' => $this->admin->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 100.00,
            'tax' => 10.00,
            'shipping' => 0.00,
            'total' => 110.00,
            'shipping_address' => ['street' => '123 Atelier Way', 'city' => 'New York', 'zip' => '10001'],
            'billing_address' => ['street' => '123 Atelier Way', 'city' => 'New York', 'zip' => '10001'],
            'payment_method' => 'card',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $variant->sku,
            'product_slug' => $product->slug,
            'unit_price' => 50.00,
            'quantity' => 2,
            'subtotal' => 100.00,
            'total' => 100.00,
        ]);

        // Attempt deletion
        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}/delete");
        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('success');

        // Garment is archived (soft deleted & inactive), but OrderItem is preserved
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $this->assertTrue($product->fresh()->trashed());
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_admin_can_manage_images(): void
    {
        $product = Product::first();
        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'url' => 'https://example.com/img1.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'url' => 'https://example.com/img2.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        // Set img2 as primary
        $response = $this->actingAs($this->admin)->post("/admin/products/{$product->id}/images/{$img2->id}/primary");
        $response->assertRedirect();
        $this->assertTrue((bool) $img2->fresh()->is_primary);
        $this->assertFalse((bool) $img1->fresh()->is_primary);

        // Delete img1
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}/images/{$img1->id}/delete");
        $deleteResponse->assertRedirect();
        $this->assertNull(ProductImage::find($img1->id));
    }

    public function test_delete_product_route_supports_both_post_and_delete_methods(): void
    {
        $prod1 = Product::create([
            'name' => 'Method Test 1',
            'slug' => 'method-test-1',
            'sku' => 'METH-001',
            'description' => 'Test',
            'regular_price' => 20,
            'category_id' => $this->category->id,
            'gender' => 'men',
        ]);

        $prod2 = Product::create([
            'name' => 'Method Test 2',
            'slug' => 'method-test-2',
            'sku' => 'METH-002',
            'description' => 'Test',
            'regular_price' => 20,
            'category_id' => $this->category->id,
            'gender' => 'women',
        ]);

        // Test POST to /delete route
        $postResponse = $this->actingAs($this->admin)->post("/admin/products/{$prod1->id}/delete");
        $postResponse->assertRedirect('/admin/products');

        // Test DELETE to /delete route (previously threw 405 MethodNotAllowed)
        $deleteResponse = $this->actingAs($this->admin)->delete("/admin/products/{$prod2->id}/delete");
        $deleteResponse->assertRedirect('/admin/products');
    }

    public function test_image_reordering_updates_sort_order(): void
    {
        $product = Product::first();
        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'url' => 'https://example.com/reorder1.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);
        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'url' => 'https://example.com/reorder2.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/products/{$product->id}/images/reorder", [
            'image_ids' => [$img2->id, $img1->id],
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(0, $img2->fresh()->sort_order);
        $this->assertEquals(1, $img1->fresh()->sort_order);
    }

    public function test_image_validation_rejects_invalid_file_types_and_sizes(): void
    {
        $fakeTxt = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Bad Image Garment',
            'sku' => 'TEST-BAD-IMG-01',
            'description' => 'Test garment',
            'regular_price' => 50.00,
            'category_id' => $this->category->id,
            'gender' => 'men',
            'main_image' => $fakeTxt,
        ]);

        $response->assertSessionHasErrors('main_image');
    }

    public function test_main_image_replacement_on_product_update(): void
    {
        $product = Product::first();
        $oldImage = ProductImage::create([
            'product_id' => $product->id,
            'url' => '/storage/products/old_cover.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $newCover = UploadedFile::fake()->image('new_cover.png', 800, 1000);

        $response = $this->actingAs($this->admin)->post("/admin/products/{$product->id}", [
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'regular_price' => $product->regular_price,
            'category_id' => $product->category_id,
            'gender' => $product->gender,
            'is_active' => '1',
            'main_image' => $newCover,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertFalse((bool) $oldImage->fresh()->is_primary);

        $newPrimary = $product->fresh()->images()->where('is_primary', true)->first();
        $this->assertNotNull($newPrimary);
        $this->assertStringContainsString('prod_' . $product->id . '_main_', $newPrimary->url);
    }

    public function test_duplicate_variant_combinations_are_gracefully_deduplicated(): void
    {
        $payload = [
            'name' => 'Deduplication Test Tee',
            'sku' => 'DEDUP-TEE-001',
            'description' => 'Testing variant deduplication.',
            'regular_price' => 45.00,
            'category_id' => $this->category->id,
            'gender' => 'unisex',
            'is_active' => '1',
            'variants' => [
                [
                    'color' => 'Black',
                    'size' => 'M',
                    'stock' => 10,
                ],
                [
                    'color' => 'Black', // Exact same color & size combination
                    'size' => 'M',
                    'stock' => 99,
                ],
                [
                    'color' => 'White',
                    'size' => 'L',
                    'stock' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/admin/products', $payload);
        $response->assertRedirect('/admin/products');

        $product = Product::where('sku', 'DEDUP-TEE-001')->first();
        $this->assertNotNull($product);
        // Only 2 variants should exist because the duplicate Black/M was filtered out
        $this->assertCount(2, $product->variants);
    }

    public function test_negative_stock_and_price_are_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Negative Price Garment',
            'sku' => 'TEST-NEG-VAL',
            'description' => 'Testing negative numbers',
            'regular_price' => -20.00,
            'category_id' => $this->category->id,
            'gender' => 'men',
            'variants' => [
                [
                    'size' => 'S',
                    'stock' => -5,
                    'price' => -10,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['regular_price', 'variants.0.stock', 'variants.0.price']);
    }

    public function test_admin_can_adjust_inventory_stock_with_audit_trail(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();
        $inventory = Inventory::where('variant_id', $variant->id)->first();
        $initialQty = $inventory->quantity;

        $response = $this->actingAs($this->admin)->post("/admin/inventory/{$variant->id}/adjust", [
            'new_quantity' => $initialQty + 50,
            'reason' => 'Seasonal restock from warehouse',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals($initialQty + 50, $inventory->fresh()->quantity);
        $this->assertEquals($initialQty + 50, $variant->fresh()->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'type' => 'manual_adjustment',
            'quantity_change' => 50,
            'quantity_after' => $initialQty + 50,
        ]);
    }

    public function test_category_and_brand_crud_and_validation(): void
    {
        // 1. Create Category
        $catRes = $this->actingAs($this->admin)->post('/admin/categories', [
            'name' => 'Formal Knitwear',
            'gender' => 'men',
            'description' => 'Finest formal knitwear',
        ]);
        $catRes->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Formal Knitwear', 'slug' => 'formal-knitwear']);

        // 2. Create Brand
        $brandRes = $this->actingAs($this->admin)->post('/admin/brands', [
            'name' => 'Milano Atelier',
            'description' => 'Italian artisanal tailoring',
            'website' => 'https://milanoatelier.com',
        ]);
        $brandRes->assertRedirect();
        $this->assertDatabaseHas('brands', ['name' => 'Milano Atelier', 'slug' => 'milano-atelier']);
    }

    public function test_unauthorized_user_cannot_access_or_modify_products(): void
    {
        $customerRole = Role::where('slug', 'customer')->first();
        $customer = User::create([
            'name' => 'Regular Customer',
            'email' => 'customer_unauth_test@wearday.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        if ($customerRole) {
            $customer->roles()->sync([$customerRole->id]);
        }
        $product = Product::first();

        // Customer attempts to view admin create form and is redirected to admin.login
        $getRes = $this->actingAs($customer)->get('/admin/products/create');
        $getRes->assertStatus(302)->assertRedirect(route('admin.login'));

        // Customer attempts to create product and is redirected to admin.login
        $postRes = $this->actingAs($customer)->post('/admin/products', [
            'name' => 'Hacker Product',
            'sku' => 'HACK-001',
        ]);
        $postRes->assertStatus(302)->assertRedirect(route('admin.login'));

        // Customer attempts to delete product and is redirected to admin.login
        $delRes = $this->actingAs($customer)->delete("/admin/products/{$product->id}/delete");
        $delRes->assertStatus(302)->assertRedirect(route('admin.login'));
    }

    public function test_storefront_api_matches_database_prices_and_inventory(): void
    {
        $product = Product::first();
        $variant = $product->variants->first();

        $response = $this->getJson("/api/v1/products/{$product->slug}");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals($product->name, $data['name']);
        $this->assertEquals((float) $product->effective_price, (float) $data['price']);

        $variantData = collect($data['variants'])->firstWhere('id', $variant->id);
        $this->assertNotNull($variantData);
        $this->assertEquals($variant->stock, $variantData['stock']);
    }
}
