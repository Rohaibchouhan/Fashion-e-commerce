<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Exception;

class CartService
{
    public function getOrCreateCart(?User $user, ?string $sessionId = null): Cart
    {
        if ($user) {
            $cart = Cart::with(['items.product.images', 'items.variant'])->firstOrCreate(
                ['user_id' => $user->id]
            );

            // Merge guest cart if sessionId is supplied
            if ($sessionId) {
                $guestCart = Cart::with('items')->where('session_id', $sessionId)->whereNull('user_id')->first();
                if ($guestCart && $guestCart->id !== $cart->id) {
                    $this->mergeCarts($guestCart, $cart);
                }
            }

            $cart->recalculateTotals();
            return $cart->fresh(['items.product.images', 'items.variant']);
        }

        if (!$sessionId) {
            $sessionId = 'sess_' . bin2hex(random_bytes(16));
        }

        $cart = Cart::with(['items.product.images', 'items.variant'])->firstOrCreate(
            ['session_id' => $sessionId, 'user_id' => null]
        );

        $cart->recalculateTotals();
        return $cart->fresh(['items.product.images', 'items.variant']);
    }

    public function addItem(Cart $cart, int $productId, ?int $variantId, int $quantity = 1, ?string $size = null, ?string $color = null): CartItem
    {
        if ($quantity < 1) {
            throw new Exception("Quantity must be at least 1 unit.");
        }

        $product = Product::findOrFail($productId);

        if (!$product->is_active) {
            throw new Exception("This garment is currently inactive and cannot be added to bag.");
        }

        $variant = null;

        if ($variantId) {
            // Verify variant exists and belongs to the given product
            $variant = ProductVariant::where('product_id', $productId)->findOrFail($variantId);

            if (!$variant->is_active) {
                throw new Exception("Selected size/color option is currently unavailable.");
            }

            $size = $variant->size ?? $size;
            $color = $variant->color ?? $color;
            $unitPrice = (float) $variant->price;

            $availableStock = $variant->available_stock;
            if ($availableStock < $quantity) {
                throw new Exception("Selected size/color combination has only {$availableStock} units in stock.");
            }
        } else {
            $unitPrice = (float) $product->effective_price;
            $availableStock = (int) $product->variants->sum('stock');
            if ($availableStock < $quantity && $product->variants()->count() > 0) {
                throw new Exception("Product has only {$availableStock} units in stock.");
            }
        }

        $existingItem = $cart->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
            $availableStock = $variant ? $variant->available_stock : (int) $product->variants->sum('stock');
            if ($availableStock < $newQuantity) {
                throw new Exception("Cannot add more units. Only {$availableStock} in stock.");
            }
            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price' => $unitPrice,
            ]);
            $item = $existingItem;
        } else {
            $item = $cart->items()->create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'selected_size' => $size,
                'selected_color' => $color,
            ]);
        }

        $cart->recalculateTotals();
        return $item;
    }

    public function updateItemQuantity(Cart $cart, int $itemId, int $quantity): Cart
    {
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();
        } else {
            if ($item->variant) {
                $available = $item->variant->available_stock;
                if ($available < $quantity) {
                    throw new Exception("Only {$available} units are currently available in stock.");
                }
            }
            $item->update(['quantity' => $quantity]);
        }

        $cart->recalculateTotals();
        return $cart->fresh(['items.product.images', 'items.variant']);
    }

    public function removeItem(Cart $cart, int $itemId): Cart
    {
        $cart->items()->where('id', $itemId)->delete();
        $cart->recalculateTotals();
        return $cart->fresh(['items.product.images', 'items.variant']);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update([
            'coupon_code' => null,
            'subtotal' => 0.00,
            'discount_amount' => 0.00,
            'shipping_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 0.00,
        ]);
    }

    public function applyCoupon(Cart $cart, string $code): array
    {
        $code = strtoupper(trim($code));
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            throw new Exception("Coupon code '{$code}' is invalid.");
        }

        if (!$coupon->isValidForCart((float) $cart->subtotal, $cart->user_id)) {
            if ($coupon->min_spend && (float)$cart->subtotal < (float)$coupon->min_spend) {
                throw new Exception("Coupon '{$code}' requires a minimum spend of \${$coupon->min_spend}.");
            }
            throw new Exception("Coupon '{$code}' cannot be applied or has expired.");
        }

        $cart->coupon_code = $code;
        $cart->save();
        $cart->recalculateTotals();

        return [
            'success' => true,
            'message' => "Coupon '{$code}' applied successfully!",
            'discount_amount' => (float) $cart->discount_amount,
            'cart' => $cart->fresh(['items.product.images', 'items.variant']),
        ];
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->coupon_code = null;
        $cart->save();
        $cart->recalculateTotals();
        return $cart->fresh(['items.product.images', 'items.variant']);
    }

    private function mergeCarts(Cart $source, Cart $target): void
    {
        foreach ($source->items as $srcItem) {
            $destItem = $target->items()
                ->where('product_id', $srcItem->product_id)
                ->where('variant_id', $srcItem->variant_id)
                ->first();

            if ($destItem) {
                $destItem->increment('quantity', $srcItem->quantity);
            } else {
                $srcItem->update(['cart_id' => $target->id]);
            }
        }

        if ($source->coupon_code && !$target->coupon_code) {
            $target->coupon_code = $source->coupon_code;
        }

        $source->delete();
    }
}

