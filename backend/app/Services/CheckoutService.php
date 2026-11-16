<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlacedEvent;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected AuthService $authService,
        protected InventoryService $inventoryService,
        protected PaymentService $paymentService,
        protected CartService $cartService
    ) {}

    public function processCheckout(array $payload, ?User $authenticatedUser = null): array
    {
        // 1. Idempotency Check
        if (!empty($payload['idempotency_key'])) {
            $existingOrder = Order::where('idempotency_key', $payload['idempotency_key'])
                ->with(['items', 'payments', 'customer'])
                ->first();

            if ($existingOrder) {
                return [
                    'order' => $existingOrder,
                    'user' => $existingOrder->customer,
                    'token' => null,
                    'is_new_customer' => false,
                    'idempotent_replay' => true,
                ];
            }
        }

        return DB::transaction(function () use ($payload, $authenticatedUser) {
            // 2. Authentication or Automatic Customer Account Creation
            $isNewCustomer = false;
            $token = null;
            $activationToken = null;

            if ($authenticatedUser) {
                $customer = $authenticatedUser;
            } else {
                // Customer details are required in payload
                $customerInfo = $payload['customer'] ?? $payload['autoRegister'] ?? [
                    'name' => ($payload['shippingAddress']['firstName'] ?? $payload['shippingAddress']['first_name'] ?? '') . ' ' .
                              ($payload['shippingAddress']['lastName'] ?? $payload['shippingAddress']['last_name'] ?? ''),
                    'email' => $payload['email'] ?? $payload['shippingAddress']['email'] ?? null,
                    'phone' => $payload['phone'] ?? $payload['shippingAddress']['phone'] ?? null,
                ];

                if (empty($customerInfo['email'])) {
                    throw new Exception("Customer email is required for checkout account creation.");
                }

                $authResult = $this->authService->findOrCreateCustomerForCheckout($customerInfo);
                $customer = $authResult['user'];
                $token = $authResult['token'];
                $isNewCustomer = $authResult['is_new'];
                $activationToken = $authResult['activation_token'];
            }

            // 3. Process & Recalculate Items from Database
            $itemsPayload = $payload['items'] ?? [];
            if (empty($itemsPayload)) {
                throw new Exception("Checkout requires at least one cart item.");
            }

            $orderItemsData = [];
            $subtotal = 0.00;
            $inventoryReservationItems = [];

            foreach ($itemsPayload as $item) {
                $productId = $item['productId'] ?? $item['product_id'];
                $variantId = $item['variantId'] ?? $item['variant_id'] ?? null;
                $quantity = max(1, (int) $item['quantity']);

                $product = Product::with(['images', 'variants.inventory'])->findOrFail($productId);

                if (!$product->is_active) {
                    throw new Exception("Product '{$product->name}' is currently unavailable for purchase.");
                }

                $variant = null;
                $color = $item['color'] ?? $item['selectedColor'] ?? null;
                $size = $item['size'] ?? $item['selectedSize'] ?? null;

                if ($variantId) {
                    $variant = $product->variants->firstWhere('id', (int) $variantId);
                    if (!$variant || !$variant->is_active) {
                        throw new Exception("Selected variant for '{$product->name}' is inactive or no longer available.");
                    }
                    $unitPrice = (float) $variant->price;
                    $size = $variant->size ?? $size;
                    $color = $variant->color ?? $color;
                    $sku = $variant->sku;
                    $imageUrl = $variant->image ?? ($product->images->first()?->url ?? '');
                } else {
                    // If product has variants, require strict resolution by color and size
                    $activeVariants = $product->variants->where('is_active', true);
                    if ($activeVariants->isNotEmpty()) {
                        $candidates = $activeVariants;
                        if ($color) {
                            $candidates = $candidates->filter(fn ($v) => strcasecmp((string)$v->color, (string)$color) === 0);
                        }
                        if ($size) {
                            $candidates = $candidates->filter(fn ($v) => strcasecmp((string)$v->size, (string)$size) === 0);
                        }

                        if ($candidates->isEmpty()) {
                            throw new Exception("The selected size ({$size}) and color ({$color}) for '{$product->name}' is invalid or out of stock.");
                        }

                        if ($candidates->count() > 1) {
                            throw new Exception("Ambiguous variant selection for '{$product->name}'. Please specify exact variant ID.");
                        }

                        $variant = $candidates->first();
                        $unitPrice = (float) $variant->price;
                        $size = $variant->size ?? $size;
                        $color = $variant->color ?? $color;
                        $sku = $variant->sku;
                        $imageUrl = $variant->image ?? ($product->images->first()?->url ?? '');
                    } else {
                        $unitPrice = (float) $product->effective_price;
                        $sku = $product->sku;
                        $imageUrl = $product->images->first()?->url ?? '';
                    }
                }

                // Check authoritative stock
                if ($variant && $variant->available_stock < $quantity) {
                    throw new Exception("Insufficient stock for {$product->name} ({$variant->color} / {$variant->size}). Only {$variant->available_stock} available.");
                }

                $itemSubtotal = round($unitPrice * $quantity, 2);
                $subtotal += $itemSubtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'product_sku' => $sku,
                    'product_slug' => $product->slug,
                    'selected_size' => $size,
                    'selected_color' => $color,
                    'image_url' => $imageUrl,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                    'tax_amount' => 0.00,
                    'discount_amount' => 0.00,
                    'total' => $itemSubtotal,
                ];

                if ($variant) {
                    $inventoryReservationItems[] = [
                        'variant_id' => $variant->id,
                        'quantity' => $quantity,
                    ];
                }
            }

            // 4. Validate and Deduct/Reserve Inventory Atomically
            $this->inventoryService->reserveStock((string) $customer->id, $inventoryReservationItems, 60);

            // 5. Coupon Validation & Recalculation
            $discount = 0.00;
            $couponCode = $payload['couponCode'] ?? $payload['coupon_code'] ?? null;
            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
                if ($coupon && $coupon->isValidForCart($subtotal, $customer->id)) {
                    $discount = $coupon->calculateDiscount($subtotal);
                } else {
                    $couponCode = null; // ignore invalid or expired coupon
                }
            }

            // 6. Calculate Shipping & Tax
            $shippingMethodCode = $payload['shippingMethodId'] ?? $payload['shipping_method_id'] ?? 'standard';
            $shippingMethod = ShippingMethod::where('code', $shippingMethodCode)->first();
            $shippingCharge = 10.00;

            if ($shippingMethod) {
                $shippingCharge = $shippingMethod->calculateCost($subtotal - $discount);
            } else {
                $freeShippingThreshold = (float) Setting::get('free_shipping_threshold', 50);
                $shippingCharge = ($subtotal - $discount) >= $freeShippingThreshold ? 0.00 : 10.00;
            }

            $taxRate = (float) Setting::get('tax_rate_percentage', 8.00) / 100;
            $taxAmount = round(($subtotal - $discount) * $taxRate, 2);

            $finalTotal = max(0.00, round($subtotal - $discount + $shippingCharge + $taxAmount, 2));

            // 7. Order Number Generation: WD-xxxxx
            $orderNumber = 'WD-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            while (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'WD-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            }

            // 8. Shipping & Billing Addresses
            $shippingAddress = $payload['shippingAddress'] ?? $payload['shipping_address'] ?? [];
            $billingAddress = $payload['billingAddress'] ?? $payload['billing_address'] ?? $shippingAddress;

            // 9. Payment Details
            $paymentMethodData = $payload['paymentMethod'] ?? $payload['payment_method'] ?? ['type' => 'card'];
            $paymentType = is_array($paymentMethodData) ? ($paymentMethodData['type'] ?? 'card') : $paymentMethodData;

            $carrier = ($shippingMethodCode === 'express') ? 'FedEx Express' : 'Standard Ground';
            $estimatedDays = $shippingMethod ? $shippingMethod->estimated_days_max : 5;
            $estimatedDelivery = now()->addDays($estimatedDays)->toDateString();

            // 10. Create Order Record
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'idempotency_key' => $payload['idempotency_key'] ?? Str::uuid()->toString(),
                'status' => OrderStatus::PLACED,
                'payment_status' => PaymentStatus::PENDING,
                'fulfillment_status' => FulfillmentStatus::UNFULFILLED,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shippingCharge,
                'tax' => $taxAmount,
                'total' => $finalTotal,
                'coupon_code' => $couponCode,
                'shipping_method_id' => $shippingMethodCode,
                'carrier' => $carrier,
                'tracking_number' => 'Pending Allocation',
                'estimated_delivery' => $estimatedDelivery,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'payment_method' => $paymentType === 'apple_pay' ? 'Apple Pay' : ($paymentType === 'paypal' ? 'PayPal' : 'Credit / Debit Card'),
                'payment_last4' => isset($paymentMethodData['cardNumber']) ? substr($paymentMethodData['cardNumber'], -4) : '8821',
                'notes' => $payload['notes'] ?? null,
            ]);

            // 11. Create Order Items
            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // 12. Create Status History Entry
            $order->statusHistories()->create([
                'previous_status' => null,
                'new_status' => OrderStatus::PLACED->value,
                'label' => OrderStatus::PLACED->label(),
                'description' => OrderStatus::PLACED->description(),
            ]);

            // 13. Process Payment via Payment Service
            $this->paymentService->processOrderPayment($order, is_array($paymentMethodData) ? $paymentMethodData : ['type' => $paymentType]);

            // 14. Commit Stock Deduction
            $this->inventoryService->deductStockForOrder($order->id, $orderItemsData, (string) $customer->id);

            // 15. Record Coupon Usage
            if ($couponCode && isset($coupon)) {
                $coupon->increment('used_count');
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $customer->id,
                    'order_number' => $order->order_number,
                    'discount_amount' => $discount,
                ]);
            }

            // 16. Clear Customer's Cart
            $cart = $customer->cart;
            if ($cart) {
                $this->cartService->clearCart($cart);
            }

            // 17. Save Address to Customer Address Book if not saved
            if (!empty($shippingAddress['street'])) {
                $customer->addresses()->firstOrCreate(
                    [
                        'street' => $shippingAddress['street'],
                        'zip_code' => $shippingAddress['zipCode'] ?? $shippingAddress['zip_code'] ?? '',
                    ],
                    [
                        'first_name' => $shippingAddress['firstName'] ?? $shippingAddress['first_name'] ?? $customer->name,
                        'last_name' => $shippingAddress['lastName'] ?? $shippingAddress['last_name'] ?? '',
                        'apartment' => $shippingAddress['apartment'] ?? null,
                        'city' => $shippingAddress['city'] ?? '',
                        'state' => $shippingAddress['state'] ?? '',
                        'country' => $shippingAddress['country'] ?? 'United States',
                        'phone' => $shippingAddress['phone'] ?? $customer->phone,
                        'type' => 'shipping',
                        'is_default_shipping' => true,
                    ]
                );
            }

            // 18. Dispatch Events & Notification
            event(new OrderPlacedEvent($order, $isNewCustomer, $activationToken));

            return [
                'order' => $order->fresh(['items', 'payments', 'statusHistories', 'customer']),
                'user' => $customer,
                'token' => $token,
                'is_new_customer' => $isNewCustomer,
                'activation_token' => $activationToken,
            ];
        });
    }
}

