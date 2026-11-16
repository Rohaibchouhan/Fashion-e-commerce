<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web & Admin Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Interactive API Documentation (Swagger UI)
Route::get('/api/documentation', function () {
    return view('swagger');
})->name('api.documentation');

// Admin Authentication (Public)
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    if (app()->isLocal()) {
        Route::get('/dev-login', function () {
            Auth::login(App\Models\User::where('email', 'admin@wearday.com')->first());
            return redirect(request('redirect', route('admin.users')));
        });
    }
});

// Protected Admin Portal (Requires Auth + Admin Staff Role)
Route::prefix('admin')->middleware(['auth', 'admin.staff'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'));

    // Activity Audit Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity_logs');

    // Products Catalog
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('admin.products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::post('/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::post('/products/{product}/toggle', [ProductController::class, 'toggleStatus'])->name('admin.products.toggle');
    Route::match(['post', 'delete'], '/products/{product}/delete', [ProductController::class, 'destroy'])->name('admin.products.destroy');
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::match(['post', 'delete'], '/products/{product}/images/{image}/delete', [ProductController::class, 'deleteImage'])->name('admin.products.images.delete');
    Route::post('/products/{product}/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->name('admin.products.images.primary');
    Route::post('/products/{product}/images/reorder', [ProductController::class, 'reorderImages'])->name('admin.products.images.reorder');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::match(['post', 'delete'], '/categories/{category}/delete', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Brands
    Route::get('/brands', [BrandController::class, 'index'])->name('admin.brands');
    Route::post('/brands', [BrandController::class, 'store'])->name('admin.brands.store');
    Route::match(['post', 'delete'], '/brands/{brand}/delete', [BrandController::class, 'destroy'])->name('admin.brands.destroy');

    // Inventory & Stock Adjustments
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory');
    Route::post('/inventory/{variantId}/adjust', [InventoryController::class, 'adjust'])->name('admin.inventory.adjust');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('admin.inventory.movements');

    // Orders & Tracking
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('admin.orders.cancel');

    // Payments & Transactions
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments');

    // Shipping & Delivery Methods
    Route::get('/shipping', [ShippingController::class, 'index'])->name('admin.shipping');
    Route::post('/shipping/methods/{method}', [ShippingController::class, 'updateMethod'])->name('admin.shipping.method.update');

    // Returns & Inspection
    Route::get('/returns', [ReturnController::class, 'index'])->name('admin.returns');
    Route::get('/returns/{return}', [ReturnController::class, 'show'])->name('admin.returns.show');
    Route::post('/returns/{return}/approve', [ReturnController::class, 'approve'])->name('admin.returns.approve');
    Route::post('/returns/{return}/reject', [ReturnController::class, 'reject'])->name('admin.returns.reject');
    Route::post('/returns/{return}/inspect', [ReturnController::class, 'inspect'])->name('admin.returns.inspect');
    Route::post('/returns/{return}/refund', [ReturnController::class, 'refund'])->name('admin.returns.refund');

    // Refunds
    Route::get('/refunds', [RefundController::class, 'index'])->name('admin.refunds');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');
    Route::post('/customers/{customer}/toggle', [CustomerController::class, 'toggleStatus'])->name('admin.customers.toggle');
    Route::match(['post', 'delete'], '/customers/{customer}/delete', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);

    // Coupons
    Route::get('/coupons', [CouponController::class, 'index'])->name('admin.coupons');
    Route::post('/coupons', [CouponController::class, 'store'])->name('admin.coupons.store');
    Route::post('/coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('admin.coupons.toggle');
    Route::match(['post', 'delete'], '/coupons/{coupon}/delete', [CouponController::class, 'destroy'])->name('admin.coupons.destroy');

    // Reviews Moderation
    Route::get('/reviews', [ReviewController::class, 'index'])->name('admin.reviews');
    Route::post('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('admin.reviews.approve');
    Route::post('/reviews/{review}/reject', [ReviewController::class, 'reject'])->name('admin.reviews.reject');

    // CMS & Store Content
    Route::get('/cms/banners', [CmsController::class, 'banners'])->name('admin.cms.banners');
    Route::post('/cms/banners', [CmsController::class, 'storeBanner'])->name('admin.cms.banners.store');
    Route::get('/cms/pages', [CmsController::class, 'pages'])->name('admin.cms.pages');
    Route::post('/cms/pages', [CmsController::class, 'storePage'])->name('admin.cms.pages.store');
    Route::post('/cms/faqs', [CmsController::class, 'storeFaq'])->name('admin.cms.faqs.store');
    Route::get('/cms/blog', [CmsController::class, 'blog'])->name('admin.cms.blog');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update');

    // Admin Users & Roles
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::post('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::match(['post', 'delete'], '/users/{user}/delete', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

    // Email Delivery Audit Logs
    Route::get('/email-logs', [EmailLogController::class, 'index'])->name('admin.email_logs');
});
