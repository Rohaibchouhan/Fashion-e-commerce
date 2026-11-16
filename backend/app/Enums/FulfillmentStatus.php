<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case UNFULFILLED = 'unfulfilled';
    case PARTIALLY_FULFILLED = 'partially_fulfilled';
    case FULFILLED = 'fulfilled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

