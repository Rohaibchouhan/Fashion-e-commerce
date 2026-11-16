<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function publicSettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'store_name' => Setting::get('store_name', 'WearDay'),
                'store_tagline' => Setting::get('store_tagline', 'Modern Wardrobe Essentials'),
                'store_email' => Setting::get('store_email', 'support@wearday.com'),
                'store_phone' => Setting::get('store_phone', '+1 (555) 019-2834'),
                'store_address' => Setting::get('store_address', '742 Evergreen Terrace, Brooklyn, NY 11201'),
                'currency' => Setting::get('currency', 'USD'),
                'currency_symbol' => Setting::get('currency_symbol', '$'),
                'free_shipping_threshold' => (float) Setting::get('free_shipping_threshold', 50),
                'flat_shipping_rate' => (float) Setting::get('flat_shipping_rate', 10),
                'tax_rate_percentage' => (float) Setting::get('tax_rate_percentage', 8),
                'return_window_days' => (int) Setting::get('return_window_days', 30),
                'social' => [
                    'instagram' => Setting::get('social_instagram', 'https://instagram.com/wearday'),
                    'twitter' => Setting::get('social_twitter', 'https://twitter.com/wearday'),
                ],
            ],
        ]);
    }
}

