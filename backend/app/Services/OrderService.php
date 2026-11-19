<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusUpdatedEvent;
use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\User;
use App\Services\PaymentService;
use Exception;
use InvalidArgumentException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected PaymentService $paymentService;

    public function __construct(
        protected InventoryService $inventoryService,
        ?PaymentService $paymentService = null
    ) {
        $this->paymentService = $paymentService ?? app(PaymentService::class);
    }

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
        $cleanNumber = strtoupper(trim(ltrim($orderNumber, '#')));
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
                $q->whereHas('customer', fn ($c) => $c->whereRaw('LOWER(email) = ?', [$cleanCheck]))
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.email'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(billing_address, '$.email'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.postal_code'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.zipCode'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.zip_code'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.zip'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(billing_address, '$.postal_code'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(billing_address, '$.zipCode'))) = ?", [$cleanCheck])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(billing_address, '$.zip'))) = ?", [$cleanCheck])
                    ->orWhere('shipping_address->email', $cleanCheck)
                    ->orWhere('billing_address->email', $cleanCheck)
                    ->orWhere('shipping_address->postal_code', $cleanCheck)
                    ->orWhere('shipping_address->zipCode', $cleanCheck)
                    ->orWhere('billing_address->postal_code', $cleanCheck);
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
        ?string $carrier = null,
        ?string $estimatedDelivery = null
    ): Order {
        $statusEnum = is_string($newStatus) ? OrderStatus::tryFrom($newStatus) : $newStatus;
        if (!$statusEnum) {
            throw new InvalidArgumentException("Invalid order status: {$newStatus}");
        }

        return DB::transaction(function () use ($order, $statusEnum, $location, $notes, $adminUserId, $trackingNumber, $carrier, $estimatedDelivery) {
            $orderLocked = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $currentStatus = $orderLocked->status;

            // Enforce state transition rules
            if ($currentStatus !== $statusEnum) {
                if (!$currentStatus->canTransitionTo($statusEnum)) {
                    $allowed = array_map(fn ($s) => $s->label(), $currentStatus->allowedTransitions());
                    $allowedStr = empty($allowed) ? 'None (terminal state)' : implode(', ', $allowed);
                    throw new InvalidArgumentException(
                        "Cannot transition Order #{$orderLocked->order_number} from '{$currentStatus->label()}' to '{$statusEnum->label()}'. Allowed next transitions: {$allowedStr}."
                    );
                }
            }

            // If transitioning to CANCELLED, execute comprehensive cancellation with stock restoration
            if ($statusEnum === OrderStatus::CANCELLED) {
                $userActor = $adminUserId ? User::find($adminUserId) : null;
                return $this->cancelOrder($orderLocked, $notes ?? 'Administrative cancellation', $userActor);
            }

            $prevStatus = $currentStatus->value;
            $updateData = ['status' => $statusEnum];

            if ($trackingNumber) {
                $updateData['tracking_number'] = $trackingNumber;
            }
            if ($carrier) {
                $updateData['carrier'] = $carrier;
            }
            if ($estimatedDelivery) {
                $updateData['estimated_delivery'] = $estimatedDelivery;
            }

            // Sync fulfillment status (Strict separation: NEVER modify payment status on fulfillment update)
            if (in_array($statusEnum, [OrderStatus::PACKED, OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT, OrderStatus::OUT_FOR_DELIVERY])) {
                $updateData['fulfillment_status'] = FulfillmentStatus::PARTIALLY_FULFILLED;
            } elseif ($statusEnum === OrderStatus::DELIVERED) {
                $updateData['fulfillment_status'] = FulfillmentStatus::FULFILLED;
            }

            $orderLocked->update($updateData);

            // Synchronize Shipment model & events
            $activeCarrier = $carrier ?? $orderLocked->carrier;
            $activeTracking = $trackingNumber ?? $orderLocked->tracking_number;

            if ($activeTracking && $activeTracking !== 'Pending Allocation') {
                $shipment = $orderLocked->shipments()->firstOrCreate(
                    ['order_id' => $orderLocked->id],
                    [
                        'carrier' => $activeCarrier ?? 'Standard Ground',
                        'tracking_number' => $activeTracking,
                        'status' => 'pending',
                    ]
                );

                $shipmentUpdate = [];
                if ($activeCarrier) {
                    $shipmentUpdate['carrier'] = $activeCarrier;
                }
                if ($activeTracking) {
                    $shipmentUpdate['tracking_number'] = $activeTracking;
                }

                if ($statusEnum === OrderStatus::DISPATCHED) {
                    $shipmentUpdate['status'] = 'in_transit';
                    $shipmentUpdate['shipped_at'] = $shipment->shipped_at ?? now();
                } elseif ($statusEnum === OrderStatus::DELIVERED) {
                    $shipmentUpdate['status'] = 'delivered';
                    $shipmentUpdate['delivered_at'] = $shipment->delivered_at ?? now();
                }

                if (!empty($shipmentUpdate)) {
                    $shipment->update($shipmentUpdate);
                }

                if ($location || $notes || $statusEnum === OrderStatus::DISPATCHED) {
                    $shipment->events()->create([
                        'status' => $statusEnum->value,
                        'location' => $location,
                        'description' => $notes ?? $statusEnum->description(),
                        'occurred_at' => now(),
                    ]);
                }
            }

            // Record status history audit trail
            $orderLocked->statusHistories()->create([
                'previous_status' => $prevStatus,
                'new_status' => $statusEnum->value,
                'updated_by' => $adminUserId,
                'label' => $statusEnum->label(),
                'description' => $notes ?? $statusEnum->description(),
                'location' => $location,
                'notes' => $notes,
            ]);

            // Dispatch Real-Time and Notification Event
            event(new OrderStatusUpdatedEvent($orderLocked, $prevStatus, $statusEnum->value, $location));

            return $orderLocked->fresh(['items', 'statusHistories', 'payments', 'customer', 'shipments.events']);
        });
    }

    public function cancelOrder(Order $order, string $reason, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $reason, $actor) {
            // Consistent lock order: Order -> Payment -> StockReservation -> Inventory
            $orderLocked = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Idempotent: if already cancelled, return existing order
            if ($orderLocked->status === OrderStatus::CANCELLED) {
                return $orderLocked->fresh(['items', 'statusHistories', 'payments', 'refunds']);
            }

            if (in_array($orderLocked->status, [OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED])) {
                throw new Exception("Order cannot be cancelled because it has already been dispatched. Please initiate a return instead.");
            }

            // Lock payment record if present
            $payment = \App\Models\Payment::where('order_id', $orderLocked->id)->lockForUpdate()->latest()->first();

            // Authoritatively inspect locked payment status
            $wasPaid = ($orderLocked->payment_status === PaymentStatus::PAID || ($payment && $payment->status === PaymentStatus::PAID));
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

            // Automatic refund processing and tracked refund obligation
            $refund = null;
            if ($wasPaid) {
                $refundAmount = (float) ($payment ? $payment->amount : $orderLocked->total);
                if ($refundAmount > 0) {
                    $existingRefund = Refund::where('order_id', $orderLocked->id)->first();
                    if (!$existingRefund) {
                        $refundNumber = 'REF-ORD-' . strtoupper(bin2hex(random_bytes(5)));
                        $refund = Refund::create([
                            'refund_number' => $refundNumber,
                            'return_id' => null,
                            'order_id' => $orderLocked->id,
                            'payment_id' => $payment?->id,
                            'amount' => $refundAmount,
                            'reason' => "Order #{$orderLocked->order_number} cancelled: {$reason}",
                            'status' => 'pending',
                            'processed_by' => $actor?->id,
                            'processed_at' => null,
                            'notes' => $reason,
                        ]);

                        if ($payment && $payment->status === PaymentStatus::PAID) {
                            try {
                                $result = $this->paymentService->refundPayment(
                                    $payment,
                                    $refundAmount,
                                    "Order #{$orderLocked->order_number} cancelled: {$reason}",
                                    $refundNumber
                                );

                                $refund->update([
                                    'status' => 'processed',
                                    'gateway_refund_id' => $result['refund_id'] ?? null,
                                    'processed_at' => now(),
                                ]);

                                RefundTransaction::create([
                                    'refund_id' => $refund->id,
                                    'amount' => $refundAmount,
                                    'gateway' => $payment->payment_method ?? 'card',
                                    'reference' => $result['refund_id'] ?? null,
                                    'status' => 'success',
                                    'response_payload' => $result,
                                ]);
                            } catch (Exception $e) {
                                $refund->update([
                                    'status' => 'failed',
                                    'notes' => "Automatic gateway refund failed: {$e->getMessage()}",
                                ]);

                                RefundTransaction::create([
                                    'refund_id' => $refund->id,
                                    'amount' => $refundAmount,
                                    'gateway' => $payment->payment_method ?? 'card',
                                    'status' => 'failed',
                                    'response_payload' => ['error' => $e->getMessage()],
                                ]);
                            }
                        }
                    } else {
                        $refund = $existingRefund;
                    }
                }
            }

            $historyDescription = "Order cancelled: {$reason}";
            if ($refund) {
                if ($refund->status === 'processed') {
                    $historyDescription .= " (Refund #{$refund->refund_number} processed: \${$refund->amount})";
                } elseif ($refund->status === 'failed') {
                    $historyDescription .= " (Refund #{$refund->refund_number} failed: {$refund->notes})";
                } elseif ($refund->status === 'pending') {
                    $historyDescription .= " (Refund #{$refund->refund_number} pending manual processing: \${$refund->amount})";
                }
            }

            $orderLocked->statusHistories()->create([
                'previous_status' => $prevStatus,
                'new_status' => OrderStatus::CANCELLED->value,
                'updated_by' => $actor?->id,
                'label' => 'Order Cancelled',
                'description' => $historyDescription,
                'notes' => $reason,
            ]);

            event(new OrderStatusUpdatedEvent($orderLocked, $prevStatus, OrderStatus::CANCELLED->value));

            return $orderLocked->fresh(['items', 'statusHistories', 'payments', 'refunds']);
        }, 3);
    }
}

