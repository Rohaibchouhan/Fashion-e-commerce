<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
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
     * Variant IDs are sorted ascending to enforce consistent lock acquisition ordering.
     */
    public function reserveStock(string $sessionOrUserId, array $items, int $reservationMinutes = 60, ?int $orderId = null): array
    {
        return DB::transaction(function () use ($sessionOrUserId, $items, $reservationMinutes, $orderId) {
            $reservations = [];
            $expiresAt = Carbon::now()->addMinutes($reservationMinutes);

            // Sort items by variant_id ascending to guarantee deadlock-free lock ordering
            usort($items, fn ($a, $b) => ($a['variant_id'] ?? 0) <=> ($b['variant_id'] ?? 0));

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

                // Increment reserved quantity (Physical quantity is preserved!)
                $inventory->increment('reserved_quantity', $qty);

                // Record reservation linked to order if provided
                $reservation = StockReservation::create([
                    'session_or_user_id' => $sessionOrUserId,
                    'order_id' => $orderId,
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
                    'reference_type' => $orderId ? 'App\Models\Order' : null,
                    'reference_id' => $orderId ? (string) $orderId : null,
                    'notes' => $orderId
                        ? "Reserved {$qty} units for Order #{$orderId}"
                        : "Reserved {$qty} units for session/user {$sessionOrUserId}",
                ]);

                $reservations[] = $reservation;
            }

            return $reservations;
        }, 3);
    }

    /**
     * Commit active stock reservations for an order upon verified payment confirmation.
     * Deducts physical inventory exactly once and decrements reserved quantity.
     * Rejects payment confirmation if reservations are expired, released, or missing.
     */
    public function commitReservationsForOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $allReservations = StockReservation::where('order_id', $orderId)->get();

            if ($allReservations->isEmpty()) {
                throw new Exception("Cannot commit inventory: No stock reservation found for Order #{$orderId}.");
            }

            // Check if already committed idempotently
            $nonCommitted = $allReservations->filter(fn ($r) => $r->status !== 'committed');
            if ($nonCommitted->isEmpty()) {
                return;
            }

            // Check if any reservation was already released/cancelled
            $released = $allReservations->filter(fn ($r) => $r->status === 'released');
            if ($released->isNotEmpty()) {
                throw new Exception("Cannot commit inventory: Stock reservation for Order #{$orderId} was already released or cancelled.");
            }

            // Check expiration on active reservations
            $now = Carbon::now();
            $expired = $allReservations->filter(fn ($r) => $r->status === 'reserved' && $r->expires_at && Carbon::parse($r->expires_at)->lte($now));
            if ($expired->isNotEmpty()) {
                throw new Exception("Cannot commit inventory: Stock reservation for Order #{$orderId} has expired.");
            }

            // Verify reserved quantities cover the order items
            $order = Order::with('items')->find($orderId);
            if ($order && $order->items->isNotEmpty()) {
                $reservedByVariant = $allReservations->where('status', 'reserved')->groupBy('variant_id')->map->sum('quantity');
                foreach ($order->items as $orderItem) {
                    if ($orderItem->variant_id) {
                        $reservedQty = $reservedByVariant->get($orderItem->variant_id, 0);
                        if ($reservedQty < $orderItem->quantity) {
                            throw new Exception("Cannot commit inventory: Reserved quantity ({$reservedQty}) does not cover Order #{$orderId} item (needs {$orderItem->quantity}).");
                        }
                    }
                }
            }

            // Lock pending reservations in ascending variant_id order
            $reservations = StockReservation::where('order_id', $orderId)
                ->where('status', 'reserved')
                ->orderBy('variant_id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $res) {
                $inventory = Inventory::where('variant_id', $res->variant_id)->lockForUpdate()->first();
                if (!$inventory) {
                    continue;
                }

                $beforeQty = $inventory->quantity;

                // Decrement reserved quantity and deduct physical inventory
                $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $res->quantity);
                $inventory->quantity = max(0, $inventory->quantity - $res->quantity);
                $inventory->save();

                // Update mirror stock on variant
                $variant = ProductVariant::find($res->variant_id);
                if ($variant) {
                    $variant->update(['stock' => $inventory->quantity]);
                }

                // Mark reservation as committed
                $res->update(['status' => 'committed']);

                // Log authoritative order deduction movement
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'variant_id' => $res->variant_id,
                    'type' => 'order_deduction',
                    'quantity_change' => -$res->quantity,
                    'quantity_before' => $beforeQty,
                    'quantity_after' => $inventory->quantity,
                    'reference_type' => 'App\Models\Order',
                    'reference_id' => (string) $orderId,
                    'notes' => "Order #{$orderId} payment confirmed - inventory permanently committed",
                ]);
            }
        }, 3);
    }

    /**
     * Release active stock reservations for an unpaid or cancelled order.
     * Decrements reserved quantity back to zero without altering physical quantity.
     * Rows are locked in consistent ascending variant_id order.
     */
    public function releaseReservationsForOrder(int $orderId, ?string $reason = null): void
    {
        DB::transaction(function () use ($orderId, $reason) {
            $reservations = StockReservation::where('order_id', $orderId)
                ->where('status', 'reserved')
                ->orderBy('variant_id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $res) {
                $inventory = Inventory::where('variant_id', $res->variant_id)->lockForUpdate()->first();
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, $inventory->reserved_quantity - $res->quantity);
                    $inventory->save();

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'variant_id' => $res->variant_id,
                        'type' => 'reservation_expired',
                        'quantity_change' => $res->quantity,
                        'quantity_before' => $inventory->quantity - $inventory->reserved_quantity,
                        'quantity_after' => ($inventory->quantity - $inventory->reserved_quantity) + $res->quantity,
                        'reference_type' => 'App\Models\Order',
                        'reference_id' => (string) $orderId,
                        'notes' => "Released reserved stock for Order #{$orderId}: " . ($reason ?? 'Order cancelled / expired'),
                    ]);
                }

                $res->update(['status' => 'released']);
            }
        }, 3);
    }

    /**
     * Find and cancel expired unpaid pending orders and release their reserved stock.
     * Candidate orders and inventory rows are locked in consistent order.
     */
    public function cancelExpiredOrders(int $expiryMinutes = 60): int
    {
        return DB::transaction(function () use ($expiryMinutes) {
            $threshold = Carbon::now()->subMinutes($expiryMinutes);

            // Lock candidate orders ordered by id ascending
            $expiredOrders = Order::where('status', OrderStatus::PLACED)
                ->where('payment_status', PaymentStatus::PENDING)
                ->where('created_at', '<=', $threshold)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $cancelledCount = 0;
            foreach ($expiredOrders as $order) {
                // Release active reservations safely
                $this->releaseReservationsForOrder($order->id, 'Automatic expiration of unpaid pending order');

                $order->update([
                    'status' => OrderStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Pending payment reservation expired after ' . $expiryMinutes . ' minutes',
                ]);

                $order->statusHistories()->create([
                    'previous_status' => OrderStatus::PLACED->value,
                    'new_status' => OrderStatus::CANCELLED->value,
                    'label' => 'Order Expired & Cancelled',
                    'description' => 'Unpaid checkout reservation expired. Reserved inventory was released back to store.',
                ]);

                $cancelledCount++;
            }

            // Also clean up any loose guest/cart stock reservations that have expired
            $this->releaseExpiredReservations();

            return $cancelledCount;
        }, 3);
    }

    /**
     * Commit reserved stock or deduct directly for order fulfillment.
     * Variant IDs are sorted ascending for deadlock-free locking.
     */
    public function deductStockForOrder(int $orderId, array $items, ?string $sessionOrUserId = null): void
    {
        DB::transaction(function () use ($orderId, $items, $sessionOrUserId) {
            usort($items, fn ($a, $b) => ($a['variant_id'] ?? 0) <=> ($b['variant_id'] ?? 0));

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

                $variant = ProductVariant::find($variantId);
                if ($variant) {
                    $variant->update(['stock' => $inventory->quantity]);
                }

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
        }, 3);
    }

    /**
     * Restore stock when an eligible order is cancelled.
     * Protected by idempotency guard to prevent multiple restorations.
     * Variant IDs sorted ascending for deadlock prevention.
     */
    public function restoreStockForOrder(int $orderId, array $items, ?string $reason = null): void
    {
        DB::transaction(function () use ($orderId, $items, $reason) {
            // Idempotency guard: prevent duplicate restoration for the same order
            $alreadyRestored = InventoryMovement::where('reference_type', 'App\Models\Order')
                ->where('reference_id', (string) $orderId)
                ->where('type', 'order_cancellation_restore')
                ->exists();

            if ($alreadyRestored) {
                return;
            }

            // Sort items by variant_id ascending for consistent lock order
            usort($items, fn ($a, $b) => ($a['variant_id'] ?? 0) <=> ($b['variant_id'] ?? 0));

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
        }, 3);
    }

    /**
     * Release loose stock reservations (without an order) that have expired without checkout.
     * CRITICAL: Must strictly filter whereNull('order_id') so order-linked reservations
     * are NEVER released independently of their order cancellation lifecycle!
     */
    public function releaseExpiredReservations(): int
    {
        return DB::transaction(function () {
            $expired = StockReservation::where('status', 'reserved')
                ->whereNull('order_id')
                ->where('expires_at', '<=', Carbon::now())
                ->orderBy('variant_id', 'asc')
                ->lockForUpdate()
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
        }, 3);
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
        }, 3);
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
        }, 3);
    }
}
