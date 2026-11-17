<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusUpdatedEvent;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function getCustomerOrders(User $user, ?string $statusFilter = null): LengthAwarePaginator
    {
        $query = Order::where('customer_id', $user->id)
            ->with(['items.product', 'items.variant', 'statusHistories', 'payments'])
            ->latest();

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        return $query->paginate(15);
    }

    public function getOrderForCustomer(User $user, string $orderIdentifier): Order
    {
        $order = Order::with(['items.product.images', 'items.variant', 'statusHistories.updatedBy', 'payments', 'returns'])
            ->where('customer_id', $user->id)
            ->where(function (Builder $q) use ($orderIdentifier) {
                $q->where('order_number', $orderIdentifier)
                    ->orWhere('id', $orderIdentifier);
            })
            ->first();

        if (!$order) {
            throw new Exception("Order not found or access denied.");
        }

        return $order;
    }

    public function trackOrder(string $orderNumber, string $emailOrZip): Order
    {
        $cleanNumber = strtoupper(trim($orderNumber));
        $cleanCheck = strtolower(trim($emailOrZip));

        if (empty($cleanCheck)) {
            throw new Exception("Verification email or postal ZIP code is strictly required to protect order privacy.");
        }

        $order = Order::with(['items.product', 'statusHistories', 'payments', 'customer'])
            ->where(function (Builder $q) use ($cleanNumber) {
                $q->where('order_number', $cleanNumber)
                    ->orWhere('id', $cleanNumber)
                    ->orWhere('tracking_number', $cleanNumber);
            })
            ->where(function (Builder $q) use ($cleanCheck) {
                $q->whereHas('customer', fn ($c) => $c->where('email', $cleanCheck))
                    ->orWhere('shipping_address->email', $cleanCheck)
                    ->orWhere('shipping_address->zipCode', $cleanCheck)
                    ->orWhere('shipping_address->zip_code', $cleanCheck)
                    ->orWhere('shipping_address->zip', $cleanCheck)
                    ->orWhere('shipping_address->postal_code', $cleanCheck)
                    ->orWhere('shipping_address->zipCode', strtoupper($cleanCheck))
                    ->orWhere('shipping_address->postal_code', strtoupper($cleanCheck))
                    ->orWhere('billing_address->email', $cleanCheck)
                    ->orWhere('billing_address->zipCode', $cleanCheck)
                    ->orWhere('billing_address->zip', $cleanCheck);
            })
            ->first();

        if (!$order) {
            throw new Exception("No order found matching the provided order number and verification credentials.");
        }

        return $order;
    }

    public function updateOrderStatus(
        Order $order,
        OrderStatus|string $newStatus,
        ?string $location = null,
        ?string $notes = null,
        ?int $adminUserId = null,
        ?string $trackingNumber = null,
        ?string $carrier = null
    ): Order {
        $statusEnum = is_string($newStatus) ? OrderStatus::tryFrom($newStatus) : $newStatus;
        if (!$statusEnum) {
            throw new Exception("Invalid order status: {$newStatus}");
        }

        return DB::transaction(function () use ($order, $statusEnum, $location, $notes, $adminUserId, $trackingNumber, $carrier) {
            $prevStatus = $order->status->value;

            $updateData = ['status' => $statusEnum];

            if ($trackingNumber) {
                $updateData['tracking_number'] = $trackingNumber;
            }
            if ($carrier) {
                $updateData['carrier'] = $carrier;
            }

            // Sync fulfillment status
            if (in_array($statusEnum, [OrderStatus::PACKED, OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT, OrderStatus::OUT_FOR_DELIVERY])) {
                $updateData['fulfillment_status'] = FulfillmentStatus::PARTIALLY_FULFILLED;
            } elseif ($statusEnum === OrderStatus::DELIVERED) {
                $updateData['fulfillment_status'] = FulfillmentStatus::FULFILLED;
            }

            $order->update($updateData);

            // Record status history
            $order->statusHistories()->create([
                'previous_status' => $prevStatus,
                'new_status' => $statusEnum->value,
                'updated_by' => $adminUserId,
                'label' => $statusEnum->label(),
                'description' => $notes ?? $statusEnum->description(),
                'location' => $location,
                'notes' => $notes,
            ]);

            // Dispatch Real-Time and Notification Event
            event(new OrderStatusUpdatedEvent($order, $prevStatus, $statusEnum->value, $location));

            return $order->fresh(['items', 'statusHistories', 'payments', 'customer']);
        });
    }

    public function cancelOrder(Order $order, string $reason, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $reason, $actor) {
            // Consistent lock order: Order -> Payment -> StockReservation -> Inventory
            $orderLocked = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Idempotent: if already cancelled, return existing order
            if ($orderLocked->status === OrderStatus::CANCELLED) {
                return $orderLocked->fresh(['items', 'statusHistories', 'payments']);
            }

            if (in_array($orderLocked->status, [OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED])) {
                throw new Exception("Order cannot be cancelled because it has already been dispatched. Please initiate a return instead.");
            }

            // Lock payment record if present
            \App\Models\Payment::where('order_id', $orderLocked->id)->lockForUpdate()->get();

            // Authoritatively inspect locked payment status
            $wasPaid = ($orderLocked->payment_status === PaymentStatus::PAID);
            $prevStatus = $orderLocked->status->value;

            $orderLocked->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Release any active stock reservations
            $this->inventoryService->releaseReservationsForOrder($orderLocked->id, $reason);

            // Only restore physical inventory if the order was paid/committed (physical stock was deducted)
            if ($wasPaid) {
                $items = $orderLocked->items->map(fn ($item) => [
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                ])->toArray();

                $this->inventoryService->restoreStockForOrder($orderLocked->id, $items, $reason);
            }

            $orderLocked->statusHistories()->create([
                'previous_status' => $prevStatus,
                'new_status' => OrderStatus::CANCELLED->value,
                'updated_by' => $actor?->id,
                'label' => 'Order Cancelled',
                'description' => "Order cancelled: {$reason}",
                'notes' => $reason,
            ]);

            event(new OrderStatusUpdatedEvent($orderLocked, $prevStatus, OrderStatus::CANCELLED->value));

            return $orderLocked->fresh(['items', 'statusHistories', 'payments']);
        }, 3);
    }
}

