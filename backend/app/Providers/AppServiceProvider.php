<?php

namespace App\Providers;

use App\Events\OrderPlacedEvent;
use App\Events\OrderStatusUpdatedEvent;
use App\Events\ReturnStatusUpdatedEvent;
use App\Listeners\SendOrderConfirmationListener;
use App\Listeners\SendOrderStatusNotificationListener;
use App\Listeners\SendReturnStatusNotificationListener;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('access-admin', fn (User $user) => $user->isAdminStaff());
    }
}
