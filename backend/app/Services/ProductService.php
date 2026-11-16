<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function getFilteredProducts(array $params): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'brand', 'variants', 'images'])
            ->where('is_active', true);

        // Filter by gender
        if (!empty($params['gender']) && $params['gender'] !== 'all') {
            $gender = strtolower(trim($params['gender']));
            $query->whereIn('gender', [$gender, 'unisex']);
        }

        // Filter by category or category slug with strict hierarchy resolution
        if (!empty($params['category']) && $params['category'] !== 'all') {
            $catSlug = trim($params['category']);
            $genderFilter = !empty($params['gender']) && $params['gender'] !== 'all' ? strtolower(trim($params['gender'])) : null;

            // 1. Direct match by slug or id
            $matchedCategories = Category::where('slug', $catSlug)->orWhere('id', $catSlug)->get();

            // 2. If no direct match and gender provided, check gender-prefixed slug (e.g. "men-shirts", "men-t-shirts")
            if ($matchedCategories->isEmpty() && $genderFilter) {
                $matchedCategories = Category::where('slug', "{$genderFilter}-{$catSlug}")
                    ->orWhere('slug', "{$genderFilter}_{$catSlug}")
                    ->get();
            }

            // 3. Hierarchical search scoped to category gender or parent category gender
            if ($matchedCategories->isEmpty()) {
                $matchedCategories = Category::where(function (Builder $q) use ($catSlug) {
                    $q->where('slug', 'like', "%{$catSlug}%")
                      ->orWhere('name', 'like', "%{$catSlug}%");
                })->when($genderFilter, function (Builder $q) use ($genderFilter) {
                    $q->where(function (Builder $sq) use ($genderFilter) {
                        $sq->where('gender', $genderFilter)
                           ->orWhereHas('parent', fn (Builder $p) => $p->where('gender', $genderFilter));
                    });
                })->get();
            }

            if ($matchedCategories->isNotEmpty()) {
                // Collect target category IDs and all their hierarchical children
                $categoryIds = collect();
                foreach ($matchedCategories as $cat) {
                    $categoryIds->push($cat->id);
                    $childIds = Category::where('parent_id', $cat->id)->pluck('id');
                    $categoryIds = $categoryIds->merge($childIds);
                }

                $query->whereIn('category_id', $categoryIds->unique());
            } else {
                // If a non-existent category was explicitly requested, return 0 matching products
                $query->whereRaw('1 = 0');
            }
        }

        // Search by keyword (name, sku, description)
        if (!empty($params['search'])) {
            $term = trim($params['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('subtitle', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Filter by price range
        if (isset($params['minPrice']) && is_numeric($params['minPrice'])) {
            $query->where(function ($q) use ($params) {
                $q->where('regular_price', '>=', $params['minPrice'])
                    ->orWhere('sale_price', '>=', $params['minPrice']);
            });
        }
        if (isset($params['maxPrice']) && is_numeric($params['maxPrice'])) {
            $query->where(function ($q) use ($params) {
                $q->where('regular_price', '<=', $params['maxPrice'])
                    ->orWhere(function ($sq) use ($params) {
                        $sq->whereNotNull('sale_price')->where('sale_price', '<=', $params['maxPrice']);
                    });
            });
        }

        // Filter by sizes
        if (!empty($params['sizes'])) {
            $sizes = is_array($params['sizes']) ? $params['sizes'] : explode(',', $params['sizes']);
            $query->whereHas('variants', fn ($q) => $q->whereIn('size', $sizes));
        }

        // Filter by colors
        if (!empty($params['colors'])) {
            $colors = is_array($params['colors']) ? $params['colors'] : explode(',', $params['colors']);
            $query->whereHas('variants', fn ($q) => $q->whereIn('color', $colors));
        }

        // Filter in-stock only
        if (!empty($params['inStockOnly']) || (isset($params['in_stock']) && $params['in_stock'] === 'true')) {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }

        // Filter by collection flags
        if (!empty($params['is_featured'])) {
            $query->where('is_featured', true);
        }
        if (!empty($params['is_new'])) {
            $query->where('is_new', true);
        }
        if (!empty($params['is_bestseller'])) {
            $query->where('is_bestseller', true);
        }
        if (!empty($params['is_sale'])) {
            $query->whereNotNull('sale_price')->whereRaw('sale_price < regular_price');
        }

        // Sorting
        $sort = $params['sort'] ?? 'featured';
        match ($sort) {
            'newest' => $query->orderBy('created_at', 'desc'),
            'price-asc' => $query->orderByRaw('COALESCE(sale_price, regular_price) ASC'),
            'price-desc' => $query->orderByRaw('COALESCE(sale_price, regular_price) DESC'),
            'rating' => $query->orderBy('rating', 'desc')->orderBy('review_count', 'desc'),
            'popularity' => $query->orderBy('review_count', 'desc')->orderBy('rating', 'desc'),
            default => $query->orderBy('is_featured', 'desc')->orderBy('is_bestseller', 'desc')->orderBy('created_at', 'desc'),
        };

        $perPage = min(50, max(1, (int) ($params['limit'] ?? $params['per_page'] ?? 12)));
        return $query->paginate($perPage);
    }

    public function getProductBySlugOrId(string $identifier): ?Product
    {
        return Product::with([
            'category.parent',
            'brand',
            'variants.inventory',
            'images',
            'reviews' => fn ($q) => $q->where('is_approved', true)->with('user')->latest()->limit(20),
        ])
            ->where(function ($q) use ($identifier) {
                $q->where('slug', $identifier)
                    ->orWhere('id', $identifier)
                    ->orWhere('sku', $identifier);
            })
            ->first();
    }

    /**
     * Admin catalog listing with multi-parameter filtering, sorting, and pagination.
     */
    public function getAdminProducts(array $params): LengthAwarePaginator
    {
        $query = Product::with(['category', 'brand', 'variants.inventory', 'images']);

        if (!empty($params['search'])) {
            $term = trim($params['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('subtitle', 'like', "%{$term}%");
            });
        }

        if (!empty($params['category']) && $params['category'] !== 'all') {
            $catId = $params['category'];
            $query->where(function (Builder $q) use ($catId) {
                $q->where('category_id', $catId)
                    ->orWhereHas('category', fn ($c) => $c->where('parent_id', $catId));
            });
        }

        if (!empty($params['gender']) && $params['gender'] !== 'all') {
            $query->where('gender', $params['gender']);
        }

        if (!empty($params['brand']) && $params['brand'] !== 'all') {
            $query->where('brand_id', $params['brand']);
        }

        if (isset($params['status']) && $params['status'] !== 'all' && $params['status'] !== '') {
            $query->where('is_active', $params['status'] === 'active' || $params['status'] === '1');
        }

        if (!empty($params['stock_status'])) {
            if ($params['stock_status'] === 'in_stock') {
                $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 5));
            } elseif ($params['stock_status'] === 'low_stock') {
                $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0)->where('stock', '<=', 5));
            } elseif ($params['stock_status'] === 'out_of_stock') {
                $query->whereDoesntHave('variants', fn ($q) => $q->where('stock', '>', 0));
            }
        }

        // Sorting
        $sort = $params['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderBy('regular_price', 'asc'),
            'price_desc' => $query->orderBy('regular_price', 'desc'),
            'stock_asc' => $query->withSum('variants', 'stock')->orderBy('variants_sum_stock', 'asc'),
            'stock_desc' => $query->withSum('variants', 'stock')->orderBy('variants_sum_stock', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->latest(),
        };

        $perPage = min(100, max(5, (int) ($params['per_page'] ?? 15)));
        return $query->paginate($perPage);
    }

    /**
     * Create product with attributes, images, variants, and atomic inventory initialization.
     */
    public function createProductWithDetails(
        array $data,
        ?UploadedFile $mainImage = null,
        array $galleryImages = []
    ): Product {
        return DB::transaction(function () use ($data, $mainImage, $galleryImages) {
            // Slug generation: allow custom or generate unique
            if (!empty($data['slug'])) {
                $slug = Str::slug($data['slug']);
            } else {
                $slug = Str::slug($data['name']);
            }

            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-" . $count++;
            }
            $data['slug'] = $slug;

            // Extract sub-data
            $variantsData = $data['variants'] ?? [];
            $manualImageUrls = $data['images'] ?? [];
            unset($data['variants'], $data['images'], $data['main_image'], $data['gallery_images']);

            $product = Product::create($data);

            $order = 0;

            // 1. Save Main Uploaded Image
            if ($mainImage && $mainImage->isValid()) {
                $filename = 'prod_' . $product->id . '_main_' . time() . '.' . $mainImage->getClientOriginalExtension();
                $path = $mainImage->storeAs('products', $filename, 'public');
                $product->images()->create([
                    'url' => '/storage/' . $path,
                    'alt' => $product->name,
                    'sort_order' => $order++,
                    'is_primary' => true,
                ]);
            } elseif (!empty($data['main_image_url'])) {
                $product->images()->create([
                    'url' => $data['main_image_url'],
                    'alt' => $product->name,
                    'sort_order' => $order++,
                    'is_primary' => true,
                ]);
            }

            // 2. Save Gallery Uploaded Images
            foreach ($galleryImages as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $filename = 'prod_' . $product->id . '_gallery_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('products', $filename, 'public');
                    $product->images()->create([
                        'url' => '/storage/' . $path,
                        'alt' => $product->name,
                        'sort_order' => $order++,
                        'is_primary' => ($order === 1), // if no main image, first gallery is primary
                    ]);
                }
            }

            // 3. Save any direct URL images (for seeding/migration)
            foreach ($manualImageUrls as $url) {
                $cleanUrl = is_array($url) ? ($url['url'] ?? '') : (string) $url;
                if (!empty($cleanUrl)) {
                    $product->images()->create([
                        'url' => $cleanUrl,
                        'alt' => $product->name,
                        'sort_order' => $order++,
                        'is_primary' => ($order === 1),
                    ]);
                }
            }

            // 4. Create Variants & Synchronize Inventory
            $seenCombinations = [];
            foreach ($variantsData as $variant) {
                $color = trim($variant['color'] ?? '');
                $size = trim($variant['size'] ?? '');
                $comboKey = strtolower("{$color}_{$size}");

                if (isset($seenCombinations[$comboKey])) {
                    continue; // skip duplicate combination
                }
                $seenCombinations[$comboKey] = true;

                $stock = max(0, (int) ($variant['stock'] ?? 0));
                $price = !empty($variant['price']) ? (float) $variant['price'] : (float) ($data['sale_price'] ?? $data['regular_price']);

                $variantSku = !empty($variant['sku']) ? trim($variant['sku']) : "{$product->sku}-" . strtoupper(substr($color, 0, 3)) . "-{$size}";
                $origSku = $variantSku;
                $skuCount = 1;
                while (ProductVariant::where('sku', $variantSku)->exists()) {
                    $variantSku = "{$origSku}-" . $skuCount++;
                }

                $createdVariant = $product->variants()->create([
                    'sku' => $variantSku,
                    'color' => $color ?: null,
                    'color_hex' => $variant['color_hex'] ?? '#000000',
                    'size' => $size ?: null,
                    'price' => $price,
                    'compare_at_price' => !empty($variant['compare_at_price']) ? (float) $variant['compare_at_price'] : null,
                    'cost_price' => !empty($variant['cost_price']) ? (float) $variant['cost_price'] : ($data['cost_price'] ?? null),
                    'stock' => $stock,
                    'image' => $variant['image'] ?? null,
                    'is_active' => isset($variant['is_active']) ? (bool) $variant['is_active'] : true,
                ]);

                // Create Inventory record
                $inventory = Inventory::create([
                    'product_id' => $product->id,
                    'variant_id' => $createdVariant->id,
                    'quantity' => $stock,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 5,
                ]);

                // Log initial inventory movement
                if ($stock > 0) {
                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'variant_id' => $createdVariant->id,
                        'type' => 'initial',
                        'quantity_change' => $stock,
                        'quantity_before' => 0,
                        'quantity_after' => $stock,
                        'reason' => 'Product initial inventory setup',
                    ]);
                }
            }

            return $product->load(['variants.inventory', 'images', 'category', 'brand']);
        });
    }

    /**
     * Update product details, images, variants, and inventory.
     */
    public function updateProductWithDetails(
        Product $product,
        array $data,
        ?UploadedFile $mainImage = null,
        array $galleryImages = []
    ): Product {
        return DB::transaction(function () use ($product, $data, $mainImage, $galleryImages) {
            // Slug uniqueness check on update
            if (!empty($data['slug']) && $data['slug'] !== $product->slug) {
                $slug = Str::slug($data['slug']);
                $origSlug = $slug;
                $count = 1;
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = "{$origSlug}-" . $count++;
                }
                $data['slug'] = $slug;
            }

            $existingVariants = $data['existing_variants'] ?? [];
            $newVariants = $data['new_variants'] ?? [];
            $deletedVariantIds = $data['deleted_variant_ids'] ?? [];

            unset(
                $data['existing_variants'],
                $data['new_variants'],
                $data['deleted_variant_ids'],
                $data['main_image'],
                $data['gallery_images']
            );

            $product->update($data);

            // 1. Handle Main Image Replacement
            if ($mainImage && $mainImage->isValid()) {
                $product->images()->update(['is_primary' => false]);
                $filename = 'prod_' . $product->id . '_main_' . time() . '.' . $mainImage->getClientOriginalExtension();
                $path = $mainImage->storeAs('products', $filename, 'public');
                $product->images()->create([
                    'url' => '/storage/' . $path,
                    'alt' => $product->name,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);
            }

            // 2. Handle Additional Gallery Uploads
            if (!empty($galleryImages)) {
                $maxOrder = $product->images()->max('sort_order') ?? 0;
                foreach ($galleryImages as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $filename = 'prod_' . $product->id . '_gallery_' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('products', $filename, 'public');
                        $product->images()->create([
                            'url' => '/storage/' . $path,
                            'alt' => $product->name,
                            'sort_order' => ++$maxOrder,
                            'is_primary' => false,
                        ]);
                    }
                }
            }

            // 3. Update Existing Variants
            foreach ($existingVariants as $vId => $vData) {
                $variant = ProductVariant::where('product_id', $product->id)->find($vId);
                if (!$variant) continue;

                $newStock = max(0, (int) ($vData['stock'] ?? $variant->stock));
                $stockDiff = $newStock - $variant->stock;

                $variant->update([
                    'sku' => !empty($vData['sku']) ? trim($vData['sku']) : $variant->sku,
                    'price' => isset($vData['price']) ? (float) $vData['price'] : $variant->price,
                    'compare_at_price' => !empty($vData['compare_at_price']) ? (float) $vData['compare_at_price'] : null,
                    'cost_price' => !empty($vData['cost_price']) ? (float) $vData['cost_price'] : $variant->cost_price,
                    'stock' => $newStock,
                    'is_active' => isset($vData['is_active']) ? (bool) $vData['is_active'] : true,
                ]);

                // Synchronize Inventory row
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $product->id, 'variant_id' => $variant->id],
                    ['quantity' => $newStock, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]
                );

                if ($stockDiff !== 0) {
                    $qtyBefore = $inventory->quantity;
                    $inventory->update(['quantity' => $newStock]);

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'variant_id' => $variant->id,
                        'type' => 'manual_adjustment',
                        'quantity_change' => $stockDiff,
                        'quantity_before' => $qtyBefore,
                        'quantity_after' => $newStock,
                        'reason' => 'Admin manual product update',
                    ]);
                }
            }

            // 4. Create New Variants
            foreach ($newVariants as $nVar) {
                $color = trim($nVar['color'] ?? '');
                $size = trim($nVar['size'] ?? '');

                if (empty($color) && empty($size)) continue;

                // Prevent duplicate within existing
                if ($product->variants()->where('color', $color)->where('size', $size)->exists()) {
                    continue;
                }

                $nStock = max(0, (int) ($nVar['stock'] ?? 0));
                $nPrice = !empty($nVar['price']) ? (float) $nVar['price'] : (float) ($product->sale_price ?? $product->regular_price);
                $nSku = !empty($nVar['sku']) ? trim($nVar['sku']) : "{$product->sku}-" . strtoupper(substr($color, 0, 3)) . "-{$size}";

                $created = $product->variants()->create([
                    'sku' => $nSku,
                    'color' => $color ?: null,
                    'color_hex' => $nVar['color_hex'] ?? '#000000',
                    'size' => $size ?: null,
                    'price' => $nPrice,
                    'compare_at_price' => !empty($nVar['compare_at_price']) ? (float) $nVar['compare_at_price'] : null,
                    'cost_price' => !empty($nVar['cost_price']) ? (float) $nVar['cost_price'] : $product->cost_price,
                    'stock' => $nStock,
                    'is_active' => true,
                ]);

                $inv = Inventory::create([
                    'product_id' => $product->id,
                    'variant_id' => $created->id,
                    'quantity' => $nStock,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 5,
                ]);

                if ($nStock > 0) {
                    InventoryMovement::create([
                        'inventory_id' => $inv->id,
                        'variant_id' => $created->id,
                        'type' => 'initial',
                        'quantity_change' => $nStock,
                        'quantity_before' => 0,
                        'quantity_after' => $nStock,
                        'reason' => 'New variant added via admin update',
                    ]);
                }
            }

            // 5. Delete Selected Variants (Safe Order Item preservation)
            foreach ($deletedVariantIds as $delId) {
                $varToDel = ProductVariant::where('product_id', $product->id)->find($delId);
                if ($varToDel) {
                    if ($varToDel->orderItems()->exists()) {
                        // Safe deactivation to preserve historical order records
                        $varToDel->update(['is_active' => false]);
                    } else {
                        Inventory::where('variant_id', $varToDel->id)->delete();
                        $varToDel->delete();
                    }
                }
            }

            return $product->fresh(['variants.inventory', 'images', 'category', 'brand']);
        });
    }

    /**
     * Safe product deletion ensuring historical order integrity.
     */
    public function deleteProduct(Product $product): array
    {
        if ($product->orderItems()->exists()) {
            // Associated with existing orders: soft delete & deactivate
            $product->update(['is_active' => false]);
            $product->variants()->update(['is_active' => false]);
            $product->delete();

            return [
                'status' => 'archived_with_orders',
                'message' => "Garment '{$product->name}' was archived and removed from the active catalog. Historical customer order items are safely preserved.",
            ];
        }

        // No order dependencies: soft delete
        $product->variants()->update(['is_active' => false]);
        $product->delete();

        return [
            'status' => 'deleted',
            'message' => "Garment '{$product->name}' was safely removed from the catalog.",
        ];
    }

    /**
     * Delete a product image file and database record safely.
     */
    public function deleteProductImage(ProductImage $image): void
    {
        $product = $image->product;
        $wasPrimary = $image->is_primary;
        $url = $image->url;

        // If local storage file, remove from disk if not used elsewhere
        if (str_starts_with($url, '/storage/')) {
            $relativePath = str_replace('/storage/', '', $url);
            $usageCount = ProductImage::where('url', $url)->where('id', '!=', $image->id)->count();
            if ($usageCount === 0 && Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        $image->delete();

        // If primary image was deleted, promote first remaining image
        if ($wasPrimary && $product) {
            $firstRemaining = $product->images()->first();
            if ($firstRemaining) {
                $firstRemaining->update(['is_primary' => true, 'sort_order' => 0]);
            }
        }
    }

    /**
     * Set selected image as primary image for product.
     */
    public function setPrimaryProductImage(Product $product, ProductImage $image): void
    {
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true, 'sort_order' => 0]);
    }

    /**
     * Reorder images based on an array of image IDs in desired sequence.
     */
    public function reorderProductImages(Product $product, array $imageIds): void
    {
        foreach ($imageIds as $order => $id) {
            $product->images()->where('id', $id)->update(['sort_order' => $order]);
        }
    }

    /**
     * Add single variant directly.
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            $stock = max(0, (int) ($data['stock'] ?? 0));
            $variant = $product->variants()->create([
                'sku' => $data['sku'],
                'color' => $data['color'] ?? null,
                'color_hex' => $data['color_hex'] ?? '#000000',
                'size' => $data['size'] ?? null,
                'price' => (float) ($data['price'] ?? $product->effective_price),
                'compare_at_price' => !empty($data['compare_at_price']) ? (float) $data['compare_at_price'] : null,
                'cost_price' => !empty($data['cost_price']) ? (float) $data['cost_price'] : $product->cost_price,
                'stock' => $stock,
                'is_active' => true,
            ]);

            $inv = Inventory::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => $stock,
                'reserved_quantity' => 0,
                'low_stock_threshold' => 5,
            ]);

            if ($stock > 0) {
                InventoryMovement::create([
                    'inventory_id' => $inv->id,
                    'variant_id' => $variant->id,
                    'type' => 'initial',
                    'quantity_change' => $stock,
                    'quantity_before' => 0,
                    'quantity_after' => $stock,
                    'reason' => 'Created via direct variant action',
                ]);
            }

            return $variant;
        });
    }

    // Retain legacy method signature for backward compatibility
    public function createProduct(array $data): Product
    {
        return $this->createProductWithDetails($data);
    }
}

