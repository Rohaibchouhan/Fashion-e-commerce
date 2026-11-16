<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'store_name', 'value' => 'WearDay', 'group' => 'general', 'type' => 'string', 'description' => 'The brand name of the online store'],
            ['key' => 'store_tagline', 'value' => 'Modern Wardrobe Essentials for Men & Women', 'group' => 'general', 'type' => 'string', 'description' => 'Store tagline'],
            ['key' => 'store_email', 'value' => 'support@wearday.com', 'group' => 'general', 'type' => 'string', 'description' => 'Primary customer support email'],
            ['key' => 'store_phone', 'value' => '+1 (555) 019-2834', 'group' => 'general', 'type' => 'string', 'description' => 'Contact phone number'],
            ['key' => 'store_address', 'value' => '742 Evergreen Terrace, Brooklyn, NY 11201, United States', 'group' => 'general', 'type' => 'string', 'description' => 'Physical store and warehouse headquarters'],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'general', 'type' => 'boolean', 'description' => 'Enable maintenance mode for customers'],

            // Localization & Currency
            ['key' => 'currency', 'value' => 'USD', 'group' => 'localization', 'type' => 'string', 'description' => 'Default base store currency'],
            ['key' => 'currency_symbol', 'value' => '$', 'group' => 'localization', 'type' => 'string', 'description' => 'Currency display symbol'],
            ['key' => 'timezone', 'value' => 'America/New_York', 'group' => 'localization', 'type' => 'string', 'description' => 'Store operational timezone'],

            // Financial & Tax
            ['key' => 'tax_rate_percentage', 'value' => '8.00', 'group' => 'financial', 'type' => 'float', 'description' => 'Standard sales tax percentage rate'],

            // Shipping
            ['key' => 'free_shipping_threshold', 'value' => '50.00', 'group' => 'shipping', 'type' => 'float', 'description' => 'Minimum order cart value to unlock free ground shipping'],
            ['key' => 'flat_shipping_rate', 'value' => '10.00', 'group' => 'shipping', 'type' => 'float', 'description' => 'Default flat rate shipping fee when threshold is unmet'],

            // Returns & Policy
            ['key' => 'return_window_days', 'value' => '30', 'group' => 'returns', 'type' => 'integer', 'description' => 'Number of days from delivery date within which returns are accepted'],
            ['key' => 'restock_damaged_items', 'value' => '0', 'group' => 'returns', 'type' => 'boolean', 'description' => 'Whether damaged returned items can be automatically restocked (strictly false by policy)'],

            // Social links
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/wearday', 'group' => 'social', 'type' => 'string', 'description' => 'Instagram URL'],
            ['key' => 'social_twitter', 'value' => 'https://twitter.com/wearday', 'group' => 'social', 'type' => 'string', 'description' => 'X/Twitter URL'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}

