<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlacedEvent;
use App\Exceptions\IdempotencyConflictException;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected AuthService $authService,
        protected InventoryService $inventoryService,
        protected PaymentService $paymentService,
        protected CartService $cartService
    ) {}

    /**
     * Calculate an authoritative checkout quote without persisting orders or reserving inventory.
     */
    public function calculateQuote(array $payload, ?User $authenticatedUser = null): array
    {
        $itemsPayload = $payload['items'] ?? [];
        if (empty($itemsPayload)) {
            throw new Exception("Checkout requires at least one cart item.");
        }

        $subtotal = 0.00;
        $itemQuotes = [];

        foreach ($itemsPayload as $item) {
            $productId = $item['productId'] ?? $item['product_id'] ?? null;
            $variantId = $item['variantId'] ?? $item['variant_id'] ?? null;
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            if (!$productId) {
                throw new Exception("Each cart item must have a valid product ID.");
            }

            $product = Product::with(['images', 'variants.inventory'])->findOrFail($productId);

            if (!$product->is_active) {
                throw new Exception("Product '{$product->name}' is currently unavailable for purchase.");
            }

            $variant = null;
            $color = $item['color'] ?? $item['selectedColor'] ?? null;
            $size = $item['size'] ?? $item['selectedSize'] ?? null;

            if ($variantId) {
                $variant = $product->variants->firstWhere('id', (int) $variantId);
                if (!$variant) {
                    throw new Exception("Variant ID {$variantId} does not belong to product '{$product->name}'.");
                }
                if (!$variant->is_active) {
                    throw new Exception("Selected variant for '{$product->name}' is inactive or no longer available.");
                }
                $unitPrice = (float) $variant->price;
                $size = $variant->size ?? $size;
                $color = $variant->color ?? $color;
                $sku = $variant->sku;
                $imageUrl = $variant->image ?? ($product->images->first()?->url ?? '');
            } else {
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

            $availableStock = $variant ? $variant->available_stock : 999;
            if ($variant && $availableStock < $quantity) {
                throw new Exception("Insufficient stock for {$product->name} ({$variant->color} / {$variant->size}). Only {$availableStock} available.");
            }

            $itemSubtotal = round($unitPrice * $quantity, 2);
            $subtotal += $itemSubtotal;

            $itemQuotes[] = [
                'productId' => (string) $product->id,
                'categoryId' => $product->category_id,
                'variantId' => $variant ? (string) $variant->id : null,
                'name' => $product->name,
                'sku' => $sku,
                'slug' => $product->slug,
                'color' => $color,
                'size' => $size,
                'image' => $imageUrl,
                'unitPrice' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $itemSubtotal,
                'availableStock' => $availableStock,
            ];
        }

        // Coupon calculation
        $discount = 0.00;
        $appliedCouponCode = null;
        $couponCode = $payload['couponCode'] ?? $payload['coupon_code'] ?? null;
        $customerId = $authenticatedUser?->id;

        if ($couponCode) {
            $coupon = Coupon::with(['products', 'categories'])
                ->where('code', strtoupper(trim($couponCode)))
                ->first();
            if (!$coupon) {
                throw new Exception("Coupon code '{$couponCode}' is invalid.");
            }
            $couponResult = $this->calculateCouponDiscount($coupon, $itemQuotes, $customerId);
            $discount = $couponResult['discount'];
            $appliedCouponCode = $coupon->code;
        }

        // Strict Shipping validation
        $shippingMethodCode = $payload['shippingMethodId'] ?? $payload['shipping_method_id'] ?? 'standard';
        $shippingMethod = ShippingMethod::where('code', $shippingMethodCode)->first();

        if (!$shippingMethod) {
            throw new Exception("Shipping method '{$shippingMethodCode}' is invalid or does not exist.");
        }

        if (!$shippingMethod->is_active) {
            throw new Exception("Shipping method '{$shippingMethod->name}' is currently unavailable.");
        }

        $shippingCharge = $shippingMethod->calculateCost($subtotal - $discount);
        $shippingMethodName = $shippingMethod->name;
        $estimatedDays = $shippingMethod->estimated_days_max ?? 5;

        // Tax calculation (8.00% standard configured rate)
        $taxRatePercentage = (float) Setting::get('tax_rate_percentage', 8.00);
        $taxRate = $taxRatePercentage / 100;
        $taxAmount = round(($subtotal - $discount) * $taxRate, 2);

        $finalTotal = max(0.00, round($subtotal - $discount + $shippingCharge + $taxAmount, 2));

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shippingCharge, 2),
            'shippingMethodId' => $shippingMethodCode,
            'shippingMethodName' => $shippingMethodName,
            'estimatedDays' => $estimatedDays,
            'tax' => round($taxAmount, 2),
            'taxRate' => $taxRatePercentage,
            'total' => round($finalTotal, 2),
            'couponCode' => $appliedCouponCode,
            'items' => $itemQuotes,
        ];
    }

    /**
     * Process checkout and create an authoritative pending order.
     */
    public function processCheckout(array $payload, ?User $authenticatedUser = null, ?string $guestSessionId = null): array
    {
        // 1. Idempotency Check & Fingerprinting
        $incomingHash = $this->generateIdempotencyFingerprint($payload, $authenticatedUser, $guestSessionId);
        $idempotencyKey = $payload['idempotency_key'] ?? null;

        if (!empty($idempotencyKey)) {
            $existingOrder = Order::where('idempotency_key', $idempotencyKey)
                ->with(['items.product.images', 'items.variant', 'payments', 'customer', 'statusHistories'])
                ->first();

            if ($existingOrder) {
                // Verify ownership:
                // Customer Security: authenticated customer cannot access another customer's order
                if ($authenticatedUser && $existingOrder->customer_id !== $authenticatedUser->id) {
                    throw new Exception("Access denied: Idempotency conflict with another customer order.");
                }

                // Guest Security: verify guest ownership
                if (!$authenticatedUser) {
                    if ($existingOrder->guest_session_id && $guestSessionId && $existingOrder->guest_session_id !== $guestSessionId) {
                        throw new Exception("Access denied: Idempotency conflict with another guest session order.");
                    }
                    $incomingEmail = strtolower(trim((string) ($payload['customer']['email'] ?? $payload['autoRegister']['email'] ?? '')));
                    if ($incomingEmail && $existingOrder->customer && strtolower($existingOrder->customer->email) !== $incomingEmail) {
                        throw new Exception("Access denied: Idempotency key belongs to another customer account.");
                    }
                }

                // Payload mismatch check: if request was submitted with different parameters
                if ($existingOrder->idempotency_hash && $existingOrder->idempotency_hash !== $incomingHash) {
                    throw new IdempotencyConflictException("Idempotency key '{$idempotencyKey}' was previously used with different order details.");
                }

                return [
                    'order' => $existingOrder,
                    'user' => $existingOrder->customer,
                    'token' => null,
                    'is_new_customer' => false,
                    'idempotent_replay' => true,
                ];
            }
        }

        try {
            return DB::transaction(function () use ($payload, $authenticatedUser, $guestSessionId, $idempotencyKey, $incomingHash) {
            // 2. Authentication or Automatic Customer Account Creation
            $isNewCustomer = false;
            $token = null;
            $activationToken = null;

            if ($authenticatedUser) {
                $customer = $authenticatedUser;
            } else {
                $customerInfo = $payload['customer'] ?? $payload['autoRegister'] ?? null;

                if (!$customerInfo || empty($customerInfo['email'])) {
                    throw new Exception("Customer authentication or account creation details (name and email) are required for checkout.");
                }

                $authResult = $this->authService->findOrCreateCustomerForCheckout($customerInfo);
                $customer = $authResult['user'];
                $token = $authResult['token'];
                $isNewCustomer = $authResult['is_new'];
                $activationToken = $authResult['activation_token'];
            }

            // 3. Address Resolution and Security Ownership Check
            $shippingAddressSnapshot = $this->resolveShippingAddress($payload, $customer);
            $billingAddressSnapshot = $this->resolveBillingAddress($payload, $customer, $shippingAddressSnapshot);

            // 4. Process & Recalculate Items from Database
            $itemsPayload = $payload['items'] ?? [];
            if (empty($itemsPayload)) {
                throw new Exception("Checkout requires at least one cart item.");
            }

            $orderItemsData = [];
            $subtotal = 0.00;
            $inventoryReservationItems = [];

            foreach ($itemsPayload as $item) {
                $productId = $item['productId'] ?? $item['product_id'] ?? null;
                $variantId = $item['variantId'] ?? $item['variant_id'] ?? null;
                $quantity = max(1, (int) ($item['quantity'] ?? 1));

                if (!$productId) {
                    throw new Exception("Each cart item must have a valid product ID.");
                }

                $product = Product::with(['images', 'variants.inventory'])->findOrFail($productId);

                if (!$product->is_active) {
                    throw new Exception("Product '{$product->name}' is currently unavailable for purchase.");
                }

                $variant = null;
                $color = $item['color'] ?? $item['selectedColor'] ?? null;
                $size = $item['size'] ?? $item['selectedSize'] ?? null;

                if ($variantId) {
                    $variant = $product->variants->firstWhere('id', (int) $variantId);
                    if (!$variant) {
                        throw new Exception("Variant ID {$variantId} does not belong to product '{$product->name}'.");
                    }
                    if (!$variant->is_active) {
                        throw new Exception("Selected variant for '{$product->name}' is inactive or no longer available.");
                    }
                    $unitPrice = (float) $variant->price;
                    $size = $variant->size ?? $size;
                    $color = $variant->color ?? $color;
                    $sku = $variant->sku;
                    $imageUrl = $variant->image ?? ($product->images->first()?->url ?? '');
                } else {
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

                // Check stock using authoritative formula: quantity - reserved_quantity
                $available = $variant ? $variant->available_stock : 999;
                if ($variant && $available < $quantity) {
                    throw new Exception("Insufficient stock for {$product->name} ({$variant->color} / {$variant->size}). Only {$available} available.");
                }

                $itemSubtotal = round($unitPrice * $quantity, 2);
                $subtotal += $itemSubtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
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

            // 5. Strict Coupon Validation & Recalculation with Pessimistic Locking
            $discount = 0.00;
            $appliedCoupon = null;
            $eligibleItemIndices = [];
            $couponCode = $payload['couponCode'] ?? $payload['coupon_code'] ?? null;
            if ($couponCode) {
                $coupon = Coupon::with(['products', 'categories'])
                    ->where('code', strtoupper(trim($couponCode)))
                    ->lockForUpdate()
                    ->first();
                if (!$coupon) {
                    throw new Exception("Coupon code '{$couponCode}' is invalid.");
                }
                $couponResult = $this->calculateCouponDiscount($coupon, $orderItemsData, $customer->id);
                $discount = $couponResult['discount'];
                $eligibleItemIndices = $couponResult['eligibleIndices'];
                $appliedCoupon = $coupon;
            }

            // 6. Strict Shipping Method Validation
            $shippingMethodCode = $payload['shippingMethodId'] ?? $payload['shipping_method_id'] ?? 'standard';
            $shippingMethod = ShippingMethod::where('code', $shippingMethodCode)->first();

            if (!$shippingMethod) {
                throw new Exception("Shipping method '{$shippingMethodCode}' is invalid or does not exist.");
            }

            if (!$shippingMethod->is_active) {
                throw new Exception("Shipping method '{$shippingMethod->name}' is currently unavailable.");
            }

            $shippingCharge = $shippingMethod->calculateCost($subtotal - $discount);
            $carrier = match ($shippingMethod->code) {
                'express' => 'FedEx Express Courier',
                'overnight' => 'UPS Next Day Air',
                default => 'Standard Ground',
            };
            $estimatedDays = $shippingMethod->estimated_days_max ?? 5;

            // 7. Calculate Tax & Final Total
            $taxRatePercentage = (float) Setting::get('tax_rate_percentage', 8.00);
            $taxRate = $taxRatePercentage / 100;
            $taxAmount = round(($subtotal - $discount) * $taxRate, 2);
            $finalTotal = max(0.00, round($subtotal - $discount + $shippingCharge + $taxAmount, 2));

            // Distribute tax proportionally across all items, and discount only across eligible items
            $eligibleSubtotal = 0.0;
            if (!empty($eligibleItemIndices)) {
                foreach ($eligibleItemIndices as $idx) {
                    $eligibleSubtotal += $orderItemsData[$idx]['subtotal'];
                }
            }

            foreach ($orderItemsData as $idx => &$itemData) {
                if ($subtotal > 0) {
                    $itemRatio = $itemData['subtotal'] / $subtotal;
                    $itemData['tax_amount'] = round($taxAmount * $itemRatio, 2);
                }
                if (in_array($idx, $eligibleItemIndices, true) && $eligibleSubtotal > 0) {
                    $discRatio = $itemData['subtotal'] / $eligibleSubtotal;
                    $itemData['discount_amount'] = round($discount * $discRatio, 2);
                } else {
                    $itemData['discount_amount'] = 0.00;
                }
                $itemData['total'] = max(0.00, round($itemData['subtotal'] - $itemData['discount_amount'] + $itemData['tax_amount'], 2));
                unset($itemData['category_id']);
            }
            unset($itemData);

            // 8. Order Number Generation: WD-xxxxx
            $orderNumber = 'WD-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            while (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'WD-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            }

            $estimatedDelivery = now()->addDays($estimatedDays)->toDateString();

            // Payment metadata
            $paymentMethodData = $payload['payment'] ?? $payload['paymentMethod'] ?? $payload['payment_method'] ?? ['type' => 'card'];
            $paymentType = is_array($paymentMethodData) ? ($paymentMethodData['type'] ?? $paymentMethodData['method'] ?? 'card') : $paymentMethodData;

            $isTestPayment = in_array(strtolower($paymentType), ['test', 'test_payment', 'sandbox'], true);
            if ($isTestPayment) {
                if (app()->environment('production')) {
                    throw new Exception("Test payment method is strictly forbidden in production environments.");
                }
                if (!Setting::get('test_payment_mode_enabled', config('services.test_payment.enabled', false))) {
                    throw new Exception("Test payment method is currently disabled by store administrator.");
                }
            }

            $paymentTitle = match ($paymentType) {
                'apple_pay' => 'Apple Pay',
                'paypal' => 'PayPal',
                'cod' => 'Cash on Delivery',
                'test', 'test_payment', 'sandbox' => 'Test Sandbox Payment',
                default => 'Credit / Debit Card',
            };
            $paymentLast4 = isset($paymentMethodData['cardNumber'])
                ? substr(preg_replace('/\D/', '', $paymentMethodData['cardNumber']), -4)
                : '8821';

            // 9. Create Order with PENDING payment status, Idempotency Fingerprint and Guest Session
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'idempotency_key' => $idempotencyKey ?? Str::uuid()->toString(),
                'idempotency_hash' => $incomingHash,
                'guest_session_id' => $authenticatedUser ? null : $guestSessionId,
                'status' => OrderStatus::PLACED,
                'payment_status' => PaymentStatus::PENDING,
                'fulfillment_status' => FulfillmentStatus::UNFULFILLED,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shippingCharge,
                'tax' => $taxAmount,
                'total' => $finalTotal,
                'coupon_code' => $appliedCoupon?->code,
                'shipping_method_id' => $shippingMethodCode,
                'carrier' => $carrier,
                'tracking_number' => 'Pending Allocation',
                'estimated_delivery' => $estimatedDelivery,
                'shipping_address' => $shippingAddressSnapshot,
                'billing_address' => $billingAddressSnapshot,
                'payment_method' => $paymentTitle,
                'payment_last4' => $paymentLast4,
                'notes' => $payload['notes'] ?? null,
            ]);

            // 10. Create Order Items
            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // 11. Create Status History Entry
            $order->statusHistories()->create([
                'previous_status' => null,
                'new_status' => OrderStatus::PLACED->value,
                'label' => OrderStatus::PLACED->label(),
                'description' => OrderStatus::PLACED->description(),
            ]);

            // 12. Reserve Stock for Order with Expiration (Physical stock is preserved until verified payment)
            // Sort items ascending by variant_id to guarantee deadlock-free locking
            usort($inventoryReservationItems, fn ($a, $b) => ($a['variant_id'] ?? 0) <=> ($b['variant_id'] ?? 0));
            $this->inventoryService->reserveStock((string) $customer->id, $inventoryReservationItems, 60, $order->id);

            // 13. Create Payment Record (or process test payment if selected)
            if ($isTestPayment) {
                $this->paymentService->processOrderPayment(
                    $order,
                    is_array($paymentMethodData) ? $paymentMethodData : ['type' => 'test', 'scenario' => 'success']
                );
            } else {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $paymentType,
                    'transaction_id' => 'TXN_PENDING_' . Str::upper(Str::random(12)),
                    'amount' => $order->total,
                    'currency' => 'USD',
                    'status' => PaymentStatus::PENDING,
                    'is_test' => false,
                    'gateway_response' => [
                        'status' => 'pending',
                        'method' => $paymentType,
                        'message' => 'Order placed. Awaiting customer payment confirmation.',
                    ],
                    'paid_at' => null,
                ]);
            }

            // 13. Record Coupon Usage
            if ($appliedCoupon) {
                $appliedCoupon->increment('used_count');
                CouponUsage::create([
                    'coupon_id' => $appliedCoupon->id,
                    'user_id' => $customer->id,
                    'order_number' => $order->order_number,
                    'discount_amount' => $discount,
                ]);
            }

            // 14. Clear Customer's Cart only after successful order persistence & reservation
            $cart = $customer->cart;
            if ($cart) {
                $this->cartService->clearCart($cart);
            }

            // 15. Save address to address book if requested or new
            $this->saveAddressToCustomerBook($customer, $shippingAddressSnapshot);

            // 16. Dispatch Events & Notification
            event(new OrderPlacedEvent($order, $isNewCustomer, $activationToken));

            return [
                'order' => $order->fresh(['items.product.images', 'items.variant', 'payments', 'statusHistories', 'customer']),
                'user' => $customer,
                'token' => $token,
                'is_new_customer' => $isNewCustomer,
                'activation_token' => $activationToken,
            ];
        }, 3);
        } catch (\Illuminate\Database\UniqueConstraintViolationException | \Illuminate\Database\QueryException $e) {
            $isDuplicateKey = false;
            if ($e instanceof \Illuminate\Database\UniqueConstraintViolationException) {
                $isDuplicateKey = true;
            } elseif (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
                $isDuplicateKey = true;
            } elseif (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                $isDuplicateKey = true;
            }

            if ($isDuplicateKey && !empty($idempotencyKey)) {
                $committedOrder = Order::where('idempotency_key', $idempotencyKey)
                    ->with(['items.product.images', 'items.variant', 'payments', 'customer', 'statusHistories'])
                    ->first();

                if ($committedOrder) {
                    if ($authenticatedUser && $committedOrder->customer_id !== $authenticatedUser->id) {
                        throw new Exception("Access denied: Idempotency conflict with another customer order.");
                    }

                    if (!$authenticatedUser) {
                        if ($committedOrder->guest_session_id && $guestSessionId && $committedOrder->guest_session_id !== $guestSessionId) {
                            throw new Exception("Access denied: Idempotency conflict with another guest session order.");
                        }
                        $incomingEmail = strtolower(trim((string) ($payload['customer']['email'] ?? $payload['autoRegister']['email'] ?? '')));
                        if ($incomingEmail && $committedOrder->customer && strtolower($committedOrder->customer->email) !== $incomingEmail) {
                            throw new Exception("Access denied: Idempotency key belongs to another customer account.");
                        }
                    }

                    if ($committedOrder->idempotency_hash && $committedOrder->idempotency_hash !== $incomingHash) {
                        throw new IdempotencyConflictException("Idempotency key '{$idempotencyKey}' was previously used with different order details.");
                    }

                    return [
                        'order' => $committedOrder,
                        'user' => $committedOrder->customer,
                        'token' => null,
                        'is_new_customer' => false,
                        'idempotent_replay' => true,
                    ];
                }
            }

            throw $e;
        }
    }

    /**
     * Resolve and validate shipping address snapshot, checking ownership of saved addresses.
     */
    protected function resolveShippingAddress(array $payload, User $customer): array
    {
        $addressId = $payload['shipping_address_id'] ?? null;

        if ($addressId) {
            $savedAddress = Address::find($addressId);

            if (!$savedAddress || $savedAddress->user_id !== $customer->id) {
                throw new Exception("The selected shipping address is invalid or does not belong to your account.");
            }

            return [
                'firstName' => $savedAddress->first_name,
                'lastName' => $savedAddress->last_name,
                'first_name' => $savedAddress->first_name,
                'last_name' => $savedAddress->last_name,
                'street' => $savedAddress->street,
                'apartment' => $savedAddress->apartment ?? '',
                'city' => $savedAddress->city,
                'state' => $savedAddress->state,
                'zipCode' => $savedAddress->zip_code,
                'postal_code' => $savedAddress->zip_code,
                'country' => $savedAddress->country ?? 'United States',
                'phone' => $savedAddress->phone ?? $customer->phone ?? '',
                'email' => $customer->email,
            ];
        }

        $addr = $payload['shippingAddress'] ?? $payload['shipping_address'] ?? [];
        if (empty($addr['street']) || empty($addr['city']) || (empty($addr['zipCode']) && empty($addr['zip_code']) && empty($addr['postal_code']))) {
            throw new Exception("Please provide complete shipping address details (street, city, state, zip code).");
        }

        $firstName = $addr['firstName'] ?? $addr['first_name'] ?? explode(' ', $customer->name)[0] ?? '';
        $lastName = $addr['lastName'] ?? $addr['last_name'] ?? (explode(' ', $customer->name)[1] ?? '');

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street' => $addr['street'],
            'apartment' => $addr['apartment'] ?? '',
            'city' => $addr['city'],
            'state' => $addr['state'] ?? '',
            'zipCode' => $addr['zipCode'] ?? $addr['zip_code'] ?? $addr['postal_code'] ?? '',
            'postal_code' => $addr['zipCode'] ?? $addr['zip_code'] ?? $addr['postal_code'] ?? '',
            'country' => $addr['country'] ?? 'United States',
            'phone' => $addr['phone'] ?? $customer->phone ?? '',
            'email' => $addr['email'] ?? $customer->email,
        ];
    }

    /**
     * Resolve billing address snapshot.
     */
    protected function resolveBillingAddress(array $payload, User $customer, array $shippingSnapshot): array
    {
        $sameAsShipping = $payload['billing_same_as_shipping'] ?? true;
        if ($sameAsShipping && empty($payload['billing_address_id']) && empty($payload['billingAddress']) && empty($payload['billing_address'])) {
            return $shippingSnapshot;
        }

        $billingId = $payload['billing_address_id'] ?? null;
        if ($billingId) {
            $savedAddress = Address::find($billingId);
            if (!$savedAddress || $savedAddress->user_id !== $customer->id) {
                throw new Exception("The selected billing address is invalid or does not belong to your account.");
            }

            return [
                'firstName' => $savedAddress->first_name,
                'lastName' => $savedAddress->last_name,
                'street' => $savedAddress->street,
                'apartment' => $savedAddress->apartment ?? '',
                'city' => $savedAddress->city,
                'state' => $savedAddress->state,
                'zipCode' => $savedAddress->zip_code,
                'country' => $savedAddress->country ?? 'United States',
                'phone' => $savedAddress->phone ?? '',
            ];
        }

        $addr = $payload['billingAddress'] ?? $payload['billing_address'] ?? null;
        if (!$addr) {
            return $shippingSnapshot;
        }

        return [
            'firstName' => $addr['firstName'] ?? $addr['first_name'] ?? $shippingSnapshot['firstName'],
            'lastName' => $addr['lastName'] ?? $addr['last_name'] ?? $shippingSnapshot['lastName'],
            'street' => $addr['street'] ?? $shippingSnapshot['street'],
            'apartment' => $addr['apartment'] ?? '',
            'city' => $addr['city'] ?? $shippingSnapshot['city'],
            'state' => $addr['state'] ?? $shippingSnapshot['state'],
            'zipCode' => $addr['zipCode'] ?? $addr['zip_code'] ?? $addr['postal_code'] ?? $shippingSnapshot['zipCode'],
            'country' => $addr['country'] ?? $shippingSnapshot['country'],
            'phone' => $addr['phone'] ?? $shippingSnapshot['phone'],
        ];
    }

    /**
     * Save address to customer address book if it does not already exist.
     */
    protected function saveAddressToCustomerBook(User $customer, array $addr): void
    {
        if (empty($addr['street']) || empty($addr['city'])) {
            return;
        }

        $zip = $addr['zipCode'] ?? $addr['postal_code'] ?? '';
        $existing = $customer->addresses()
            ->where('street', $addr['street'])
            ->where('zip_code', $zip)
            ->first();

        if (!$existing) {
            $isFirst = $customer->addresses()->count() === 0;
            $customer->addresses()->create([
                'first_name' => $addr['firstName'] ?? $addr['first_name'] ?? $customer->name,
                'last_name' => $addr['lastName'] ?? $addr['last_name'] ?? '',
                'street' => $addr['street'],
                'apartment' => $addr['apartment'] ?? null,
                'city' => $addr['city'],
                'state' => $addr['state'] ?? '',
                'zip_code' => $zip,
                'country' => $addr['country'] ?? 'United States',
                'phone' => $addr['phone'] ?? $customer->phone ?? '',
                'type' => 'shipping',
                'is_default_shipping' => $isFirst,
                'is_default_billing' => $isFirst,
            ]);
        }
    }

    /**
     * Validate coupon rules strictly and throw informative user-facing exceptions.
     */
    protected function validateCouponOrFail(Coupon $coupon, float $subtotal, ?int $customerId): void
    {
        if (!$coupon->is_active) {
            throw new Exception("Coupon '{$coupon->code}' is no longer active.");
        }

        $now = Carbon::now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            throw new Exception("Coupon '{$coupon->code}' has not started yet.");
        }

        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            throw new Exception("Coupon '{$coupon->code}' has expired.");
        }

        if ($coupon->usage_limit_total && $coupon->used_count >= $coupon->usage_limit_total) {
            throw new Exception("Coupon '{$coupon->code}' has reached its maximum total usage limit.");
        }

        if ($coupon->min_spend && $subtotal < (float) $coupon->min_spend) {
            $required = number_format((float) $coupon->min_spend, 2);
            throw new Exception("Coupon '{$coupon->code}' requires a minimum cart subtotal of \${$required}.");
        }

        if ($customerId && $coupon->usage_limit_per_customer) {
            $userUsage = $coupon->usages()->where('user_id', $customerId)->count();
            if ($userUsage >= $coupon->usage_limit_per_customer) {
                throw new Exception("You have already used coupon '{$coupon->code}' the maximum allowed number of times.");
            }
        }
    }

    /**
     * Calculate coupon discount respecting product and category restrictions.
     */
    public function calculateCouponDiscount(Coupon $coupon, array $itemsData, ?int $customerId): array
    {
        $hasProductRestrictions = $coupon->products()->exists();
        $hasCategoryRestrictions = $coupon->categories()->exists();

        $allowedProductIds = $hasProductRestrictions ? $coupon->products()->pluck('products.id')->toArray() : [];
        $allowedCategoryIds = $hasCategoryRestrictions ? $coupon->categories()->pluck('categories.id')->toArray() : [];

        $eligibleSubtotal = 0.00;
        $eligibleItemIndices = [];

        foreach ($itemsData as $idx => $item) {
            $productId = (int) ($item['productId'] ?? $item['product_id'] ?? 0);
            $categoryId = (int) ($item['categoryId'] ?? $item['category_id'] ?? 0);

            $isEligible = true;
            if ($hasProductRestrictions || $hasCategoryRestrictions) {
                $matchesProduct = $hasProductRestrictions && in_array($productId, $allowedProductIds, true);
                $matchesCategory = $hasCategoryRestrictions && in_array($categoryId, $allowedCategoryIds, true);
                $isEligible = ($matchesProduct || $matchesCategory);
            }

            if ($isEligible) {
                $eligibleSubtotal += (float) ($item['subtotal'] ?? 0);
                $eligibleItemIndices[] = $idx;
            }
        }

        if (($hasProductRestrictions || $hasCategoryRestrictions) && empty($eligibleItemIndices)) {
            throw new Exception("Coupon '{$coupon->code}' is not applicable to any items in your cart.");
        }

        // Validate basic rules against eligible subtotal
        $this->validateCouponOrFail($coupon, $eligibleSubtotal, $customerId);

        $discount = $coupon->calculateDiscount($eligibleSubtotal);

        return [
            'discount' => $discount,
            'eligibleSubtotal' => $eligibleSubtotal,
            'eligibleIndices' => $eligibleItemIndices,
        ];
    }

    /**
     * Generate authoritative SHA-256 idempotency fingerprint from normalized order inputs.
     */
    public function generateIdempotencyFingerprint(array $payload, ?User $user, ?string $guestSessionId = null): string
    {
        $items = [];
        foreach ($payload['items'] ?? [] as $item) {
            $items[] = [
                'product_id' => (int) ($item['productId'] ?? $item['product_id'] ?? 0),
                'variant_id' => isset($item['variantId']) || isset($item['variant_id'])
                    ? (int) ($item['variantId'] ?? $item['variant_id'])
                    : null,
                'quantity' => (int) ($item['quantity'] ?? 1),
            ];
        }
        usort($items, function ($a, $b) {
            if ($a['product_id'] !== $b['product_id']) {
                return $a['product_id'] <=> $b['product_id'];
            }
            return ($a['variant_id'] ?? 0) <=> ($b['variant_id'] ?? 0);
        });

        $shippingMethod = strtolower(trim((string) ($payload['shippingMethodId'] ?? $payload['shipping_method_id'] ?? 'standard')));
        $paymentDetails = $payload['payment'] ?? [];
        $paymentMethod = strtolower(trim((string) (
            $paymentDetails['method']
            ?? $paymentDetails['type']
            ?? $payload['paymentMethod']
            ?? $payload['payment_method']
            ?? $payload['paymentMethodId']
            ?? $payload['payment_type']
            ?? 'card'
        )));
        $couponCode = strtoupper(trim((string) ($payload['couponCode'] ?? $payload['coupon_code'] ?? '')));

        // Recursively sort associative array keys so key order differences never produce hash mismatches
        $normalizeArray = function ($item) use (&$normalizeArray) {
            if (!is_array($item)) {
                return is_string($item) ? strtolower(trim($item)) : $item;
            }
            ksort($item);
            foreach ($item as $k => $v) {
                $item[$k] = $normalizeArray($v);
            }
            return $item;
        };

        $shippingAddr = $normalizeArray($payload['shipping_address_id']
            ?? $payload['shippingAddress']
            ?? $payload['shipping_address']
            ?? []);
        $billingAddr = $normalizeArray($payload['billing_address_id']
            ?? $payload['billingAddress']
            ?? $payload['billing_address']
            ?? []);

        $customerEmail = strtolower(trim((string) ($payload['customer']['email'] ?? $payload['autoRegister']['email'] ?? '')));
        $identity = $user ? "user:{$user->id}" : "guest:{$guestSessionId}:{$customerEmail}";

        $normalized = [
            'identity' => $identity,
            'items' => $items,
            'shipping_method' => $shippingMethod,
            'payment_method' => $paymentMethod,
            'coupon_code' => $couponCode,
            'shipping_address' => $shippingAddr,
            'billing_address' => $billingAddr,
        ];

        return hash('sha256', json_encode($normalized));
    }
}
