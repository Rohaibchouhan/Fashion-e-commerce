<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_code',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing(['items.product', 'items.variant']);
        $subtotal = 0;

        foreach ($this->items as $item) {
            if (!$item->product) {
                $item->delete();
                continue;
            }
            $unitPrice = $item->variant ? (float)$item->variant->price : (float)$item->product->effective_price;
            $item->update(['unit_price' => $unitPrice]);
            $subtotal += $unitPrice * $item->quantity;
        }

        $discount = 0;
        if ($this->coupon_code) {
            $coupon = Coupon::where('code', $this->coupon_code)->first();
            if ($coupon && $coupon->isValidForCart($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
            } else {
                $this->coupon_code = null;
            }
        }

        // Free shipping if subtotal - discount >= 50, otherwise flat 10
        $freeShippingThreshold = (float) Setting::get('free_shipping_threshold', 50);
        $shipping = ($subtotal - $discount) >= $freeShippingThreshold ? 0.00 : (float) Setting::get('flat_shipping_rate', 10);

        // Tax e.g. 8%
        $taxRate = (float) Setting::get('tax_rate_percentage', 8) / 100;
        $tax = round(($subtotal - $discount) * $taxRate, 2);

        $total = max(0, $subtotal - $discount + $shipping + $tax);

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total' => $total,
        ]);
    }
}

