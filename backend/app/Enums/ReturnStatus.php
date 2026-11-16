<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case REQUESTED = 'requested';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PICKUP_SCHEDULED = 'pickup_scheduled';
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case INSPECTED = 'inspected';
    case REFUND_PROCESSING = 'refund_processing';
    case REFUNDED = 'refunded';
    case EXCHANGE_PROCESSING = 'exchange_processing';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'Return Requested',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Return Approved',
            self::REJECTED => 'Return Rejected',
            self::PICKUP_SCHEDULED => 'Pickup Scheduled',
            self::IN_TRANSIT => 'Return In Transit',
            self::RECEIVED => 'Received at Warehouse',
            self::INSPECTED => 'Quality Inspection Completed',
            self::REFUND_PROCESSING => 'Refund Processing',
            self::REFUNDED => 'Refunded',
            self::EXCHANGE_PROCESSING => 'Exchange Order Processing',
            self::COMPLETED => 'Return Completed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

