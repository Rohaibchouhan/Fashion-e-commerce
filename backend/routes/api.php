<?php

use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Categories\CategoryController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\Content\CmsController;
use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use App\Http\Controllers\Api\V1\Payments\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Products\ProductController;
use App\Http\Controllers\Api\V1\Returns\ReturnController;
use App\Http\Controllers\Api\V1\Reviews\ReviewController;
use App\Http\Controllers\Api\V1\Settings\SettingController;
use App\Http\Controllers\Api\V1\Wishlist\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WearDay Fashion API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // 1. Authentication & Password Flow
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'user']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
            Route::post('delete-account-request', [AuthController::class, 'requestAccountDeletion']);

            // Customer Addresses
            Route::post('addresses', [AuthController::class, 'addAddress']);
            Route::put('addresses/{id}', [AuthController::class, 'updateAddress']);
            Route::delete('addresses/{id}', [AuthController::class, 'deleteAddress']);
        });
    });

    // 2. Catalog & Products
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('featured', [ProductController::class, 'featured']);
        Route::get('trending', [ProductController::class, 'trending']);
        Route::get('bestsellers', [ProductController::class, 'bestsellers']);
        Route::get('new-arrivals', [ProductController::class, 'newArrivals']);
        Route::get('{slugOrId}', [ProductController::class, 'show']);

        // Product Reviews
        Route::get('{productId}/reviews', [ReviewController::class, 'index']);
        Route::post('{productId}/reviews', [ReviewController::class, 'store'])->middleware('auth:sanctum');
    });

    // 3. Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('{slug}', [CategoryController::class, 'show']);
    });

    // 4. Shopping Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'get']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::put('items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::post('coupon', [CartController::class, 'applyCoupon']);
        Route::delete('coupon', [CartController::class, 'removeCoupon']);
    });

    // 5. Checkout
    Route::post('checkout/preview', [CheckoutController::class, 'preview']);
    Route::post('checkout/quote', [CheckoutController::class, 'preview']);
    Route::post('checkout', [CheckoutController::class, 'process']);
    Route::post('checkout/process', [CheckoutController::class, 'process']);

    // 6. Orders & Tracking
    Route::prefix('orders')->group(function () {
        // Public Tracking
        Route::post('track', [OrderController::class, 'track']);
        Route::get('{orderNumber}/track', [OrderController::class, 'poll']);
        Route::get('{orderNumber}/poll', [OrderController::class, 'poll']);

        // Authenticated Customer Orders
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('{orderIdentifier}', [OrderController::class, 'show']);
            Route::post('{orderNumber}/cancel', [OrderController::class, 'cancel']);
        });
    });

    // 7. Returns & Refunds (Customer Account)
    Route::prefix('returns')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [ReturnController::class, 'index']);
        Route::post('/', [ReturnController::class, 'store']);
        Route::get('{rmaNumber}', [ReturnController::class, 'show']);
    });

    // 8. Wishlist
    Route::prefix('wishlist')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/', [WishlistController::class, 'add']);
        Route::delete('{productId}', [WishlistController::class, 'remove']);
        Route::get('check/{productId}', [WishlistController::class, 'check']);
    });

    // 9. Notifications
    Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('read-all', [NotificationController::class, 'markAllRead']);
    });

    // 10. CMS & Content
    Route::prefix('content')->group(function () {
        Route::get('banners', [CmsController::class, 'banners']);
        Route::get('homepage-sections', [CmsController::class, 'homepageSections']);
        Route::get('trust-badges', [CmsController::class, 'trustBadges']);
        Route::get('faqs', [CmsController::class, 'faqs']);
        Route::get('pages/{slug}', [CmsController::class, 'page']);
        Route::get('blog/posts', [CmsController::class, 'blogPosts']);
        Route::get('blog/posts/{slug}', [CmsController::class, 'blogPost']);
    });

    // 11. Public Store Settings
    Route::get('settings', [SettingController::class, 'publicSettings']);
    Route::get('settings/public', [SettingController::class, 'publicSettings']);

    // 12. Payment Gateway Webhook Callback
    Route::post('payments/webhook/{gateway}', [PaymentWebhookController::class, 'handle']);

    // 13. Administrative User & Staff Management (Sanctum + Admin Staff)
    Route::prefix('admin/users')->middleware(['auth:sanctum', 'admin.staff'])->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::post('/', [AdminUserController::class, 'store']);
        Route::get('roles', [AdminUserController::class, 'roles']);
        Route::get('{user}', [AdminUserController::class, 'show']);
        Route::put('{user}', [AdminUserController::class, 'update']);
        Route::patch('{user}', [AdminUserController::class, 'update']);
        Route::patch('{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::delete('{user}', [AdminUserController::class, 'destroy']);
        Route::get('{user}/addresses', [AdminUserController::class, 'addresses']);
        Route::post('{user}/addresses', [AdminUserController::class, 'addAddress']);
        Route::delete('{user}/addresses/{addressId}', [AdminUserController::class, 'deleteAddress']);
    });
});
