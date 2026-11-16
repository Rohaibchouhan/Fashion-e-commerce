<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $zone = ShippingZone::firstOrCreate(['name' => 'Domestic (US)'], [
            'countries' => ['US', 'USA', 'United States'],
            'is_active' => true,
        ]);

        ShippingMethod::firstOrCreate(['code' => 'standard'], [
            'zone_id' => $zone->id,
            'name' => 'Standard Ground Shipping',
            'description' => 'Reliable door-to-door delivery within 3-5 business days. Free on orders $50+.',
            'cost' => 10.00,
            'min_order_free' => 50.00,
            'estimated_days_min' => 3,
            'estimated_days_max' => 5,
            'is_active' => true,
        ]);

        ShippingMethod::firstOrCreate(['code' => 'express'], [
            'zone_id' => $zone->id,
            'name' => 'FedEx Express Courier',
            'description' => 'Fast expedited 2-day delivery with real-time GPS tracking.',
            'cost' => 25.00,
            'min_order_free' => null,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
            'is_active' => true,
        ]);

        ShippingMethod::firstOrCreate(['code' => 'overnight'], [
            'zone_id' => $zone->id,
            'name' => 'Next-Day Priority Air',
            'description' => 'Guaranteed overnight delivery by 10:30 AM next business morning.',
            'cost' => 40.00,
            'min_order_free' => null,
            'estimated_days_min' => 1,
            'estimated_days_max' => 1,
            'is_active' => true,
        ]);
    }
}

