<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Verify and reserve stock for an array of items atomically using row-level locking.
     * Items format: [['variant_id' => int, 'quantity' => int]]
     */
    public function reserveStock(string $sessionOrUserId, array $items, int $reservationMinutes = 30): array
    {
        return DB::transaction(function () use ($sessionOrUserId, $items, $reservationMinutes) {
            $reservations = [];
            $expiresAt = Carbon::now()->addMinutes($reservationMinutes);

            foreach ($items as $item) {
                $variantId = $item['variant_id'];
                $qty = (int) $item['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                // Lock inventory row with lockForUpdate()
                $inventory = Inventory::where('variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    throw new Exception("Inventory record not found for variant ID {$variantId}.");
                }

                $available = $inventory->quantity - $inventory->reserved_quantity;
                if ($available < $qty) {
                    $variant = ProductVariant::find($variantId);
                    $name = $variant ? "{$variant->product->name} ({$variant->color} / {$variant->size})" : "Item #{$variantId}";
                    throw new Exception("Insufficient stock for {$name}. Only {$available} available.");
                }

                // Increment reserved quantity
                $inventory->increment('reserved_quantity', $qty);

                // Record reservation
                $reservation = StockReservation::create([
                    'session_or_user_id' => $sessionOrUserId,
                    'variant_id' => $variantId,
                    'quantity' => $qty,
                    'status' => 'reserved',
                    'expires_at' => $expiresAt,
                ]);

                // Log movement
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'variant_id' => $variantId,
                    'type' => 'order_reservation',
                    'quantity_change' => -$qty,
                    'quantity_before' => $available,
                    'quantity_after' => $available - $qty,
                    'notes' => "Reserved {$qty} units for session/user {$sessionOrUserId}",
                ]);

                $reservations[] = $reservation;
            }

            return $reservations;
        });
    }

    /**
     * Commit reserved stock or deduct immediately upon confirmed order placement.
     */
    public function deductStockForOrder(int $orderId, array $items, ?string $sessionOrUserId = null): void
    {
        DB::transaction(function () use ($orderId, $items, $sessionOrUserId) {
            foreach ($items as $item) {
                $variantId = $item['variant_id'] ?? null;
                $qty = (int) $item['quantity'];

                if (!$variantId || $qty <= 0) {
                    continue;
                }

                $inventory = Inventory::where('variant_id', $variantId)->lockForUpdate()->first();
                if (!$inventory) {
                    continue;
                }

                $beforeQty = $inventory->quantity;

                // If reservation was in place, reduce reserved quantity as well
                if ($sessionOrUserId) {
                    $reservation = StockReservation::where('session_or_user_id', $sessionOrUserId)
                        ->where('variant_id', $variantId)
                        ->where('status', 'reserved')
                        ->first();

                    if ($reservation) {
                        $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $reservation->quantity);
                        $reservation->update(['status' => 'committed']);
                    }
                }

                $inventory->quantity = max(0, $inventory->quantity - $qty);
                $inventory->save();

                // Update product variant stock mirror
                $variant = ProductVariant::find($variantId);
                if ($variant) {
                    $variant->update(['stock' => $inventory->quantity]);
                }

                // Log inventory movement
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'variant_id' => $variantId,
                    'type' => 'order_deduction',
                    'quantity_change' => -$qty,
                    'quantity_before' => $beforeQty,
                    'quantity_after' => $inventory->quantity,
                    'reference_type' => 'App\Models\Order',
                    'reference_id' => (string) $orderId,
                    'notes' => "Order #{$orderId} fulfillment deduction",
                ]);
            }
        });
    }

    /**
     * Restore stock when an eligible order is cancelled.
     */
    public function restoreStockForOrder(int $orderId, array $items, ?string $reason = null): void
    {
        DB::transaction(function () use ($orderId, $items, $reason) {
            foreach ($items as $item) {
                $variantId = $item['variant_id'] ?? null;
                $qty = (int) $item['quantity'];

                if (!$variantId || $qty <= 0) {
                    continue;
                }

                $inventory = Inventory::where('variant_id', $variantId)->lockForUpdate()->first();
                if (!$inventory) {
                    continue;
                }

                $beforeQty = $inventory->quantity;
                $inventory->increment('quantity', $qty);

                $variant = ProductVariant::find($variantId);
                if ($variant) {
                    $variant->update(['stock' => $inventory->quantity]);
                }

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'variant_id' => $variantId,
                    'type' => 'order_cancellation_restore',
                    'quantity_change' => $qty,
                    'quantity_before' => $beforeQty,
                    'quantity_after' => $inventory->quantity,
                    'reference_type' => 'App\Models\Order',
                    'reference_id' => (string) $orderId,
                    'notes' => "Restored {$qty} units due to order cancellation: {$reason}",
                ]);
            }
        });
    }

    /**
     * Restock returned item ONLY after inspection determined item is resellable!
     */
    public function restockReturnedItem(int $returnId, int $variantId, int $quantity, ?int $userId = null): void
    {
        DB::transaction(function () use ($returnId, $variantId, $quantity, $userId) {
            $inventory = Inventory::where('variant_id', $variantId)->lockForUpdate()->first();
            if (!$inventory) {
                throw new Exception("Inventory record not found for variant ID {$variantId}.");
            }

            $beforeQty = $inventory->quantity;
            $inventory->increment('quantity', $quantity);

            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $variant->update(['stock' => $inventory->quantity]);
            }

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'variant_id' => $variantId,
                'type' => 'return_restock',
                'quantity_change' => $quantity,
                'quantity_before' => $beforeQty,
                'quantity_after' => $inventory->quantity,
                'reference_type' => 'App\Models\ReturnRequest',
                'reference_id' => (string) $returnId,
                'user_id' => $userId,
                'notes' => "Returned item approved and restocked after passing quality inspection",
            ]);
        });
    }

    /**
     * Manual stock adjustment by admin with audit movement.
     */
    public function adjustStock(int $variantId, int $newQuantity, ?string $reason = null, ?int $userId = null): Inventory
    {
        return DB::transaction(function () use ($variantId, $newQuantity, $reason, $userId) {
            $inventory = Inventory::where('variant_id', $variantId)->lockForUpdate()->first();
            if (!$inventory) {
                throw new Exception("Inventory record not found for variant ID {$variantId}.");
            }

            $beforeQty = $inventory->quantity;
            $diff = $newQuantity - $beforeQty;

            $inventory->update(['quantity' => $newQuantity]);

            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $variant->update(['stock' => $newQuantity]);
            }

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'variant_id' => $variantId,
                'type' => 'manual_adjustment',
                'quantity_change' => $diff,
                'quantity_before' => $beforeQty,
                'quantity_after' => $newQuantity,
                'user_id' => $userId,
                'notes' => $reason ?? 'Manual inventory count adjustment',
            ]);

            return $inventory;
        });
    }

    /**
     * Release stock reservations that have expired without checkout.
     */
    public function releaseExpiredReservations(): int
    {
        return DB::transaction(function () {
            $expired = StockReservation::where('status', 'reserved')
                ->where('expires_at', '<=', Carbon::now())
                ->get();

            $count = 0;
            foreach ($expired as $reservation) {
                $inventory = Inventory::where('variant_id', $reservation->variant_id)->lockForUpdate()->first();
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $reservation->quantity);
                    $inventory->save();

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'variant_id' => $reservation->variant_id,
                        'type' => 'reservation_expired',
                        'quantity_change' => $reservation->quantity,
                        'quantity_before' => $inventory->quantity - $inventory->reserved_quantity,
                        'quantity_after' => ($inventory->quantity - $inventory->reserved_quantity) + $reservation->quantity,
                        'notes' => "Released expired stock reservation for {$reservation->session_or_user_id}",
                    ]);
                }
                $reservation->update(['status' => 'released']);
                $count++;
            }
            return $count;
        });
    }
}
