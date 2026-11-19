<?php

namespace Database\Seeders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'alex.morgan@example.com')->first();
        if (!$customer) {
            $customer = User::first();
        }

        if (!$customer) {
            return;
        }

        $products = Product::with(['variants', 'images'])->get();
        if ($products->isEmpty()) {
            return;
        }

        $p1 = $products->firstWhere('slug', 'relaxed-fit-tee') ?? $products[0];
        $p2 = $products->firstWhere('slug', 'easy-chino-pants') ?? ($products[1] ?? $p1);
        $p3 = $products->firstWhere('slug', 'clean-sneakers') ?? ($products[2] ?? $p1);

        $v1 = $p1->variants->first();
        $v2 = $p2->variants->first();
        $v3 = $p3->variants->first();

        $address = [
            'id' => 'addr-1',
            'first_name' => 'Alex',
            'last_name' => 'Morgan',
            'street' => '742 Evergreen Terrace',
            'apartment' => 'Apt 4B',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country' => 'United States',
            'phone' => '+1 (555) 019-2834',
            'email' => $customer->email,
        ];

        // 1. Order 1: Dispatched
        $o1 = Order::firstOrCreate(
            ['order_number' => 'WD-88219'],
            [
                'customer_id' => $customer->id,
                'status' => OrderStatus::DISPATCHED,
                'payment_status' => PaymentStatus::PAID,
                'fulfillment_status' => FulfillmentStatus::FULFILLED,
                'subtotal' => 129.97,
                'discount' => 15.00,
                'shipping' => 0.00,
                'tax' => 9.75,
                'total' => 124.72,
                'coupon_code' => 'WEARDAY15',
                'shipping_method_id' => 'standard',
                'carrier' => 'FedEx Priority Ground',
                'tracking_number' => 'TRK-99482103US',
                'estimated_delivery' => now()->addDays(2),
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_method' => 'Visa ending in 8821',
                'payment_last4' => '8821',
                'created_at' => now()->subDays(3),
            ]
        );

        if ($o1->wasRecentlyCreated) {
            OrderItem::create([
                'order_id' => $o1->id,
                'product_id' => $p1->id,
                'variant_id' => $v1?->id,
                'product_name' => $p1->name,
                'product_sku' => $v1?->sku ?? $p1->sku,
                'product_slug' => $p1->slug,
                'selected_size' => $v1?->size ?? 'M',
                'selected_color' => $v1?->color ?? 'Oatmeal Heather',
                'image_url' => $p1->images->first()?->url ?? '',
                'unit_price' => $p1->regular_price,
                'quantity' => 1,
                'subtotal' => $p1->regular_price,
                'total' => $p1->regular_price,
            ]);

            OrderItem::create([
                'order_id' => $o1->id,
                'product_id' => $p2->id,
                'variant_id' => $v2?->id,
                'product_name' => $p2->name,
                'product_sku' => $v2?->sku ?? $p2->sku,
                'product_slug' => $p2->slug,
                'selected_size' => $v2?->size ?? '32',
                'selected_color' => $v2?->color ?? 'British Khaki',
                'image_url' => $p2->images->first()?->url ?? '',
                'unit_price' => $p2->regular_price,
                'quantity' => 1,
                'subtotal' => $p2->regular_price,
                'total' => $p2->regular_price,
            ]);

            OrderStatusHistory::create([
                'order_id' => $o1->id,
                'updated_by' => $customer->id,
                'previous_status' => null,
                'new_status' => OrderStatus::PLACED->value,
                'description' => 'Order placed online by customer',
                'location' => 'Brooklyn, NY',
                'created_at' => now()->subDays(3),
            ]);

            OrderStatusHistory::create([
                'order_id' => $o1->id,
                'updated_by' => null,
                'previous_status' => OrderStatus::PLACED->value,
                'new_status' => OrderStatus::PROCESSING->value,
                'description' => 'Payment authorized, warehouse picking inventory',
                'location' => 'Secaucus Fulfillment Center, NJ',
                'created_at' => now()->subDays(2),
            ]);

            OrderStatusHistory::create([
                'order_id' => $o1->id,
                'updated_by' => null,
                'previous_status' => OrderStatus::PROCESSING->value,
                'new_status' => OrderStatus::DISPATCHED->value,
                'description' => 'Package scanned and departs distribution hub',
                'location' => 'Secaucus Hub, NJ',
                'created_at' => now()->subDay(),
            ]);
        }

        // 2. Order 2: Processing
        $o2 = Order::firstOrCreate(
            ['order_number' => 'WD-84920'],
            [
                'customer_id' => $customer->id,
                'status' => OrderStatus::PROCESSING,
                'payment_status' => PaymentStatus::PAID,
                'fulfillment_status' => FulfillmentStatus::UNFULFILLED,
                'subtotal' => 69.99,
                'discount' => 0.00,
                'shipping' => 5.99,
                'tax' => 5.25,
                'total' => 81.23,
                'shipping_method_id' => 'standard',
                'carrier' => 'Standard Ground',
                'tracking_number' => 'Pending Allocation',
                'estimated_delivery' => now()->addDays(4),
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_method' => 'Apple Pay',
                'payment_last4' => '8821',
                'created_at' => now()->subDay(),
            ]
        );

        if ($o2->wasRecentlyCreated) {
            OrderItem::create([
                'order_id' => $o2->id,
                'product_id' => $p3->id,
                'variant_id' => $v3?->id,
                'product_name' => $p3->name,
                'product_sku' => $v3?->sku ?? $p3->sku,
                'product_slug' => $p3->slug,
                'selected_size' => $v3?->size ?? '42',
                'selected_color' => $v3?->color ?? 'All White',
                'image_url' => $p3->images->first()?->url ?? '',
                'unit_price' => $p3->regular_price,
                'quantity' => 1,
                'subtotal' => $p3->regular_price,
                'total' => $p3->regular_price,
            ]);

            OrderStatusHistory::create([
                'order_id' => $o2->id,
                'updated_by' => $customer->id,
                'previous_status' => null,
                'new_status' => OrderStatus::PLACED->value,
                'description' => 'Order placed online',
                'location' => 'Brooklyn, NY',
                'created_at' => now()->subDay(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $o2->id,
                'updated_by' => null,
                'previous_status' => OrderStatus::PLACED->value,
                'new_status' => OrderStatus::PROCESSING->value,
                'description' => 'Payment settled via Apple Pay. Packing items.',
                'location' => 'Secaucus Warehouse, NJ',
                'created_at' => now()->subHours(6),
            ]);
        }

        // 3. Order 3: Delivered (with Return Request & Review)
        $o3 = Order::firstOrCreate(
            ['order_number' => 'WD-79104'],
            [
                'customer_id' => $customer->id,
                'status' => OrderStatus::DELIVERED,
                'payment_status' => PaymentStatus::PAID,
                'fulfillment_status' => FulfillmentStatus::FULFILLED,
                'subtotal' => 39.99,
                'discount' => 0.00,
                'shipping' => 5.99,
                'tax' => 3.00,
                'total' => 48.98,
                'shipping_method_id' => 'standard',
                'carrier' => 'FedEx Ground',
                'tracking_number' => 'TRK-98124018US',
                'estimated_delivery' => now()->subDays(5),
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_method' => 'Visa ending in 8821',
                'payment_last4' => '8821',
                'created_at' => now()->subDays(10),
            ]
        );

        if ($o3->wasRecentlyCreated) {
            $item3 = OrderItem::create([
                'order_id' => $o3->id,
                'product_id' => $p1->id,
                'variant_id' => $v1?->id,
                'product_name' => $p1->name,
                'product_sku' => $v1?->sku ?? $p1->sku,
                'product_slug' => $p1->slug,
                'selected_size' => 'L',
                'selected_color' => 'Black',
                'image_url' => $p1->images->first()?->url ?? '',
                'unit_price' => 39.99,
                'quantity' => 1,
                'subtotal' => 39.99,
                'total' => 39.99,
            ]);

            OrderStatusHistory::create([
                'order_id' => $o3->id,
                'updated_by' => null,
                'previous_status' => OrderStatus::OUT_FOR_DELIVERY->value,
                'new_status' => OrderStatus::DELIVERED->value,
                'description' => 'Package safely handed to resident at front door',
                'location' => 'Brooklyn, NY',
                'created_at' => now()->subDays(5),
            ]);

            // Create Return for Order 3
            $ret = ReturnRequest::firstOrCreate(
                ['rma_number' => 'RMA-90412'],
                [
                    'order_id' => $o3->id,
                    'user_id' => $customer->id,
                    'status' => ReturnStatus::APPROVED,
                    'resolution' => 'exchange',
                    'exchange_details' => 'Exchange Size L for Size M in Black',
                    'refund_amount' => 39.99,
                    'tracking_number' => 'RET-UPS-884102',
                    'label_url' => '/returns/label-sample.pdf',
                    'created_at' => now()->subDays(2),
                ]
            );

            ReturnItem::firstOrCreate(
                ['return_id' => $ret->id, 'order_item_id' => $item3->id],
                [
                    'product_id' => $p1->id,
                    'variant_id' => $v1?->id,
                    'product_name' => $p1->name,
                    'selected_size' => 'L',
                    'selected_color' => 'Black',
                    'image_url' => $p1->images->first()?->url ?? '',
                    'unit_price' => 39.99,
                    'quantity' => 1,
                    'reason' => 'wrong_size',
                    'customer_comment' => 'Need a smaller size; fits slightly oversized.',
                ]
            );

        }

        // Create Verified Review
        ProductReview::firstOrCreate(
            ['product_id' => $p1->id, 'user_id' => $customer->id],
            [
                'order_id' => $o3->id,
                'rating' => 5,
                'title' => 'Exceptional fabric quality and drape',
                'comment' => 'Heavyweight cotton that holds its structure wash after wash. The collar lays perfectly flat.',
                'is_verified_purchase' => true,
                'is_approved' => true,
            ]
        );
    }
}
