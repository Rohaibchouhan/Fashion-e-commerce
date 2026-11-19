<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case PACKED = 'packed';
    case DISPATCHED = 'dispatched';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLACED => 'Order Placed',
            self::CONFIRMED => 'Order Confirmed',
            self::PROCESSING => 'Processing',
            self::PACKED => 'Packed',
            self::DISPATCHED => 'Dispatched',
            self::IN_TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PLACED => 'Thank you for your order. We are validating payment.',
            self::CONFIRMED => 'Your order has been verified and confirmed.',
            self::PROCESSING => 'Your garments are being tailored and prepared.',
            self::PACKED => 'Your order has been packed and readied for carrier dispatch.',
            self::DISPATCHED => 'Package handed off to courier for delivery.',
            self::IN_TRANSIT => 'Shipment is en route through courier logistics hub.',
            self::OUT_FOR_DELIVERY => 'Courier driver has loaded your package for today.',
            self::DELIVERED => 'Package delivered safely to destination address.',
            self::CANCELLED => 'Order was cancelled.',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get allowed target statuses from the current status.
     *
     * @return array<OrderStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PLACED => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::PACKED, self::CANCELLED],
            self::PACKED => [self::DISPATCHED, self::CANCELLED],
            self::DISPATCHED => [self::IN_TRANSIT, self::OUT_FOR_DELIVERY, self::DELIVERED],
            self::IN_TRANSIT => [self::OUT_FOR_DELIVERY, self::DELIVERED],
            self::OUT_FOR_DELIVERY => [self::DELIVERED],
            self::DELIVERED => [], // Terminal fulfillment state
            self::CANCELLED => [], // Terminal cancellation state
        };
    }

    /**
     * Check if transitioning to the target status is permitted.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return in_array($target, $this->allowedTransitions(), true);
    }
}

