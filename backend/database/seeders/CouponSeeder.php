<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::firstOrCreate(['code' => 'WELCOME10'], [
            'description' => '10% off your entire first fashion order',
            'type' => 'percentage',
            'value' => 10.00,
            'min_spend' => 30.00,
            'max_discount' => 50.00,
            'usage_limit_total' => 1000,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        Coupon::firstOrCreate(['code' => 'FASHION20'], [
            'description' => '20% VIP seasonal shopping discount on orders over $100',
            'type' => 'percentage',
            'value' => 20.00,
            'min_spend' => 100.00,
            'max_discount' => 80.00,
            'usage_limit_total' => 500,
            'usage_limit_per_customer' => 2,
            'is_active' => true,
        ]);

        Coupon::firstOrCreate(['code' => 'SAVE15'], [
            'description' => '$15 instant cash credit on orders over $75',
            'type' => 'fixed',
            'value' => 15.00,
            'min_spend' => 75.00,
            'usage_limit_total' => 200,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        Coupon::firstOrCreate(['code' => 'WEARDAY15'], [
            'description' => '15% off orders over $40',
            'type' => 'percentage',
            'value' => 15.00,
            'min_spend' => 40.00,
            'max_discount' => 50.00,
            'usage_limit_total' => 1000,
            'usage_limit_per_customer' => 5,
            'is_active' => true,
        ]);
    }
}

