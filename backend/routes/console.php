<?php

use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ecommerce:release-expired-reservations', function (InventoryService $inventoryService) {
    $this->info('Releasing expired stock reservations...');
    $count = $inventoryService->releaseExpiredReservations();
    $this->info("Successfully released {$count} expired reservation(s).");
})->purpose('Release stock reservations that have exceeded the 30-minute hold window');

Artisan::command('ecommerce:cancel-expired-orders', function (InventoryService $inventoryService) {
    $this->info('Scanning for unpaid pending orders older than 60 minutes...');
    $count = $inventoryService->cancelExpiredOrders(60);
    $this->info("Successfully cancelled {$count} expired pending order(s) and restored reserved inventory.");
})->purpose('Cancel unpaid pending orders past the 60-minute reservation window and release their reserved inventory');

Artisan::command('ecommerce:check-low-stock', function () {
    $this->info('Scanning inventory for low-stock warnings...');
    $lowStockItems = Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')
        ->with(['product', 'variant'])
        ->get();

    if ($lowStockItems->isEmpty()) {
        $this->info('All inventory levels are within normal operating parameters.');
        return;
    }

    $this->warn("Found {$lowStockItems->count()} low-stock item(s):");
    foreach ($lowStockItems as $inv) {
        $pName = $inv->product->name ?? 'Unknown';
        $vSku = $inv->variant->sku ?? 'No-SKU';
        $this->line("- [{$vSku}] {$pName}: {$inv->quantity} left (Threshold: {$inv->low_stock_threshold})");
        Log::warning("Low stock alert: [{$vSku}] {$pName} quantity={$inv->quantity}, threshold={$inv->low_stock_threshold}");
    }
})->purpose('Audit inventory levels against defined thresholds and trigger alert logs');

// Scheduled Tasks
Schedule::command('ecommerce:release-expired-reservations')->everyTenMinutes()->withoutOverlapping();
Schedule::command('ecommerce:cancel-expired-orders')->everyTenMinutes()->withoutOverlapping();
Schedule::command('ecommerce:check-low-stock')->hourly()->withoutOverlapping();
