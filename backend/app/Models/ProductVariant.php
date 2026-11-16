<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'color',
        'color_hex',
        'size',
        'price',
        'compare_at_price',
        'cost_price',
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (ProductVariant $variant) {
            if ($variant->wasChanged('stock')) {
                $inv = $variant->inventory ?: $variant->inventory()->first();
                if ($inv) {
                    $inv->update(['quantity' => (int) $variant->stock]);
                }
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'variant_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function getInStockAttribute(): bool
    {
        return $this->available_stock > 0;
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->relationLoaded('inventory') && $this->inventory) {
            return min((int)$this->stock, max(0, (int) ($this->inventory->quantity - $this->inventory->reserved_quantity)));
        }

        if ($this->inventory()->exists()) {
            $inv = $this->inventory()->first();
            return min((int)$this->stock, max(0, (int) ($inv->quantity - $inv->reserved_quantity)));
        }

        return max(0, (int) ($this->stock ?? 0));
    }
}

