<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'code',
        'name',
        'description',
        'cost',
        'min_order_free',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'min_order_free' => 'decimal:2',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }

    public function calculateCost(float $subtotal): float
    {
        if ($this->min_order_free && $subtotal >= (float)$this->min_order_free) {
            return 0.00;
        }

        return (float) $this->cost;
    }
}

