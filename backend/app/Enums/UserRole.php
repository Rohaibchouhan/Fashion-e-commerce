<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case STORE_MANAGER = 'store_manager';
    case PRODUCT_MANAGER = 'product_manager';
    case ORDER_MANAGER = 'order_manager';
    case CUSTOMER_SUPPORT = 'customer_support';
    case INVENTORY_MANAGER = 'inventory_manager';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::STORE_MANAGER => 'Store Manager',
            self::PRODUCT_MANAGER => 'Product Manager',
            self::ORDER_MANAGER => 'Order Manager',
            self::CUSTOMER_SUPPORT => 'Customer Support',
            self::INVENTORY_MANAGER => 'Inventory Manager',
            self::CUSTOMER => 'Customer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

