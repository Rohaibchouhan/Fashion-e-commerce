<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Services\InventoryService;
use Carbon\Carbon;
use Database\Seeders\CategoryBrandSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(CategoryBrandSeeder::class);
        $this->seed(ProductSeeder::class);

        $this->inventoryService = app(InventoryService::class);
    }

    public function test_atomic_stock_reservation_holds_inventory(): void
    {
        $variant = ProductVariant::first();
        $inventory = $variant->inventory;
        $initialQty = $inventory->quantity;

        // Reserve 3 units
        $reservations = $this->inventoryService->reserveStock('session-abc', [
            ['variant_id' => $variant->id, 'quantity' => 3],
        ]);

        $this->assertCount(1, $reservations);
        $this->assertEquals(3, $reservations[0]->quantity);

        $inventory->refresh();
        $this->assertEquals(3, $inventory->reserved_quantity);
        $this->assertEquals($initialQty - 3, $inventory->available_quantity);

        // Verify inventory movement was logged
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'type' => 'order_reservation',
            'quantity_change' => -3,
        ]);
    }

    public function test_stock_reservation_fails_when_exceeding_available_inventory(): void
    {
        $variant = ProductVariant::first();
        $inventory = $variant->inventory;
        $inventory->update(['quantity' => 5, 'reserved_quantity' => 0]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/i');

        // Attempt to reserve 6 units when only 5 exist
        $this->inventoryService->reserveStock('session-xyz', [
            ['variant_id' => $variant->id, 'quantity' => 6],
        ]);
    }

    public function test_expired_reservations_are_automatically_released(): void
    {
        $variant = ProductVariant::first();
        $inventory = $variant->inventory;
        $inventory->update(['quantity' => 10, 'reserved_quantity' => 4]);

        // Create an expired reservation
        StockReservation::create([
            'session_or_user_id' => 'expired-session',
            'variant_id' => $variant->id,
            'quantity' => 4,
            'status' => 'reserved',
            'expires_at' => Carbon::now()->subMinutes(35),
        ]);

        $releasedCount = $this->inventoryService->releaseExpiredReservations();
        $this->assertEquals(1, $releasedCount);

        $inventory->refresh();
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals(10, $inventory->available_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'type' => 'reservation_expired',
        ]);
    }

    public function test_order_cancellation_restores_inventory(): void
    {
        $variant = ProductVariant::first();
        $inventory = $variant->inventory;
        $inventory->update(['quantity' => 20]);

        // Simulate order deduction of 5
        $this->inventoryService->deductStockForOrder(101, [
            ['variant_id' => $variant->id, 'quantity' => 5],
        ]);

        $inventory->refresh();
        $this->assertEquals(15, $inventory->quantity);

        // Cancel order and restore stock
        $this->inventoryService->restoreStockForOrder(101, [
            ['variant_id' => $variant->id, 'quantity' => 5],
        ], 'Customer requested cancellation');

        $inventory->refresh();
        $this->assertEquals(20, $inventory->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'type' => 'order_cancellation_restore',
            'quantity_change' => 5,
        ]);
    }

    public function test_restocking_returned_item_updates_inventory_and_logs_movement(): void
    {
        $admin = \App\Models\User::factory()->create();
        $variant = ProductVariant::first();
        $inventory = $variant->inventory;
        $initialQty = $inventory->quantity;

        // Restock 1 resellable item
        $this->inventoryService->restockReturnedItem(501, $variant->id, 1, $admin->id);

        $inventory->refresh();
        $this->assertEquals($initialQty + 1, $inventory->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'type' => 'return_restock',
            'quantity_change' => 1,
            'reference_id' => '501',
            'user_id' => $admin->id,
        ]);
    }
}

