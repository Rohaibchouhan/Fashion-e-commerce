<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_sku',
        'product_slug',
        'selected_size',
        'selected_color',
        'image_url',
        'unit_price',
        'quantity',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function getReturnedQuantityAttribute(): int
    {
        return (int) $this->returnItems()
            ->whereHas('returnRequest', fn ($q) => $q->whereNotIn('status', ['rejected']))
            ->sum('quantity');
    }

    public function getEligibleReturnQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }
}

