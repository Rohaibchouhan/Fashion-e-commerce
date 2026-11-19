<?php

namespace App\Providers;

use App\Events\OrderPlacedEvent;
use App\Events\OrderStatusUpdatedEvent;
use App\Events\ReturnStatusUpdatedEvent;
use App\Listeners\SendOrderConfirmationListener;
use App\Listeners\SendOrderStatusNotificationListener;
use App\Listeners\SendReturnStatusNotificationListener;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(OrderPlacedEvent::class, SendOrderConfirmationListener::class);
        Event::listen(OrderStatusUpdatedEvent::class, SendOrderStatusNotificationListener::class);
        Event::listen(ReturnStatusUpdatedEvent::class, SendReturnStatusNotificationListener::class);

        // Super Admin bypass for all gates
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Permission based gates
        Gate::define('manage-catalog', fn (User $user) => $user->hasPermission('products.view') || $user->hasRole(['admin', 'store_manager', 'product_manager']));
        Gate::define('manage-orders', fn (User $user) => $user->hasPermission('orders.view') || $user->hasRole(['admin', 'store_manager', 'order_manager']));
        Gate::define('manage-inventory', fn (User $user) => $user->hasPermission('inventory.view') || $user->hasRole(['admin', 'store_manager', 'inventory_manager']));
        Gate::define('manage-returns', fn (User $user) => $user->hasPermission('returns.view') || $user->hasRole(['admin', 'store_manager', 'customer_support']));
        Gate::define('manage-settings', fn (User $user) => $user->hasPermission('settings.manage') || $user->hasRole(['admin']));
        Gate::define('manage-users', fn (User $user) => $user->hasPermission('admin_users.manage'));
        Gate::define('access-admin', fn (User $user) => $user->isAdminStaff());

        // Password Reset Frontend URL
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');
            return "{$frontendUrl}/auth/reset-password?token={$token}&email=" . urlencode($user->email);
        });

        // Authentication Rate Limiters
        RateLimiter::for('auth.login', function (Request $request) {
            $email = (string) $request->input('email');
            $max = (int) config('auth.rate_limiting.login', 10);
            return [
                Limit::perMinute($max)->by($request->ip()),
                Limit::perMinute($max)->by($email ? strtolower($email) . '|' . $request->ip() : $request->ip()),
            ];
        });

        RateLimiter::for('auth.register', function (Request $request) {
            $max = (int) config('auth.rate_limiting.register', 10);
            return Limit::perMinute($max)->by($request->ip());
        });

        RateLimiter::for('auth.forgot-password', function (Request $request) {
            $email = (string) $request->input('email');
            $max = (int) config('auth.rate_limiting.forgot_password', 5);
            return [
                Limit::perMinute($max)->by($request->ip()),
                Limit::perMinute($max)->by($email ? strtolower($email) . '|' . $request->ip() : $request->ip()),
            ];
        });

        RateLimiter::for('auth.reset-password', function (Request $request) {
            $email = (string) $request->input('email');
            $max = (int) config('auth.rate_limiting.reset_password', 5);
            return [
                Limit::perMinute($max)->by($request->ip()),
                Limit::perMinute($max)->by($email ? strtolower($email) . '|' . $request->ip() : $request->ip()),
            ];
        });
    }
}
