<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected ProductService $productService) {}

    /**
     * Display product catalog listing with full search, filters, and sorting.
     */
    public function index(Request $request): View
    {
        $products = $this->productService->getAdminProducts($request->all());
        $products->withQueryString();

        $categories = Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
        $brands = Brand::active()->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    /**
     * Show form to create a new product with variants and image uploaders.
     */
    public function create(): View
    {
        $categories = Category::with('parent')->orderBy('name')->get();
        $brands = Brand::active()->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    /**
     * Store new product with images, variants, and atomic inventory initialization.
     */
    public function store(CreateProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_new'] = $request->boolean('is_new');
        $validated['is_bestseller'] = $request->boolean('is_bestseller');
        $validated['is_trending'] = $request->boolean('is_trending');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $mainImage = $request->file('main_image');
        $galleryImages = $request->file('gallery_images') ?? [];

        $product = $this->productService->createProductWithDetails($validated, $mainImage, $galleryImages);

        ActivityLog::log('product.created', "Created garment {$product->name} (SKU: {$product->sku}) with {$product->variants->count()} variants.", $product);

        return redirect()->route('admin.products')->with('success', "Garment '{$product->name}' created successfully with {$product->variants->count()} variants.");
    }

    /**
     * Display detailed product overview with variant inventory breakdown.
     */
    public function show(Product $product): View
    {
        $product->load([
            'variants.inventory',
            'images',
            'category.parent',
            'brand',
            'orderItems.order',
        ]);

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show form to edit existing product, manage images, and update variants.
     */
    public function edit(Product $product): View
    {
        $product->load(['variants.inventory', 'images', 'category', 'brand']);
        $categories = Category::with('parent')->orderBy('name')->get();
        $brands = Brand::active()->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Update product details, process uploaded images, and synchronize variants.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_new'] = $request->boolean('is_new');
        $validated['is_bestseller'] = $request->boolean('is_bestseller');
        $validated['is_trending'] = $request->boolean('is_trending');
        $validated['is_active'] = $request->boolean('is_active');

        $mainImage = $request->file('main_image');
        $galleryImages = $request->file('gallery_images') ?? [];

        $this->productService->updateProductWithDetails($product, $validated, $mainImage, $galleryImages);

        ActivityLog::log('product.updated', "Updated product information, images, and inventory for {$product->name}", $product);

        return redirect()->route('admin.products')->with('success', "Garment '{$product->name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => !$product->is_active]);

        $statusStr = $product->is_active ? 'active' : 'inactive';
        ActivityLog::log('product.status_toggle', "Toggled active state of {$product->name} to {$statusStr}", $product);

        return back()->with('success', "Garment '{$product->name}' is now {$statusStr}.");
    }

    /**
     * Safe product deletion ensuring order item integrity.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $result = $this->productService->deleteProduct($product);

        ActivityLog::log('product.deleted', "Deleted/Archived garment {$name} ({$result['status']})", $product);

        return redirect()->route('admin.products')->with('success', $result['message']);
    }

    /**
     * Delete a single product image file and database record.
     */
    public function deleteImage(Product $product, ProductImage $image): RedirectResponse
    {
        if ($image->product_id !== $product->id) {
            abort(403, 'Unauthorized image access.');
        }

        $this->productService->deleteProductImage($image);

        ActivityLog::log('product.image_deleted', "Removed image #{$image->id} from product {$product->name}", $product);

        return back()->with('success', 'Image removed from gallery.');
    }

    /**
     * Set selected image as primary cover image.
     */
    public function setPrimaryImage(Product $product, ProductImage $image): RedirectResponse
    {
        if ($image->product_id !== $product->id) {
            abort(403, 'Unauthorized image access.');
        }

        $this->productService->setPrimaryProductImage($product, $image);

        ActivityLog::log('product.primary_image_set', "Set primary cover image #{$image->id} for {$product->name}", $product);

        return back()->with('success', 'Primary cover image updated.');
    }

    /**
     * Reorder gallery images.
     */
    public function reorderImages(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'integer|exists:product_images,id',
        ]);

        $this->productService->reorderProductImages($product, $request->image_ids);

        ActivityLog::log('product.images_reordered', "Reordered gallery images for {$product->name}", $product);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Image order updated.']);
        }

        return back()->with('success', 'Image order updated.');
    }
}
