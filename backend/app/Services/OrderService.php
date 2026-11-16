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
                    ->orWhereJsonContains('shipping_address->email', $cleanCheck)
                    ->orWhereJsonContains('shipping_address->zipCode', $cleanCheck)
                    ->orWhereJsonContains('shipping_address->zip_code', $cleanCheck)
                    ->orWhereJsonContains('shipping_address->zip', $cleanCheck)
                    ->orWhereJsonContains('shipping_address->postal_code', $cleanCheck)
                    ->orWhereJsonContains('billing_address->email', $cleanCheck)
                    ->orWhereJsonContains('billing_address->zipCode', $cleanCheck)
                    ->orWhereJsonContains('billing_address->zip', $cleanCheck);
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
        if (in_array($order->status, [OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED])) {
            throw new Exception("Order cannot be cancelled because it has already been dispatched. Please initiate a return instead.");
        }

        return DB::transaction(function () use ($order, $reason, $actor) {
            $prevStatus = $order->status->value;

            $order->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Restore inventory
            $items = $order->items->map(fn ($item) => [
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
            ])->toArray();

            $this->inventoryService->restoreStockForOrder($order->id, $items, $reason);

            $order->statusHistories()->create([
                'previous_status' => $prevStatus,
                'new_status' => OrderStatus::CANCELLED->value,
                'updated_by' => $actor?->id,
                'label' => 'Order Cancelled',
                'description' => "Order cancelled: {$reason}",
                'notes' => $reason,
            ]);

            event(new OrderStatusUpdatedEvent($order, $prevStatus, OrderStatus::CANCELLED->value));

            return $order->fresh(['items', 'statusHistories', 'payments']);
        });
    }
}

