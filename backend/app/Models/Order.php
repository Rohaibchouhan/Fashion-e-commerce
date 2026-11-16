<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'idempotency_key',
        'status',
        'payment_status',
        'fulfillment_status',
        'subtotal',
        'discount',
        'shipping',
        'tax',
        'total',
        'coupon_code',
        'shipping_method_id',
        'carrier',
        'tracking_number',
        'estimated_delivery',
        'shipping_address',
        'billing_address',
        'payment_method',
        'payment_last4',
        'notes',
        'admin_notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'fulfillment_status' => FulfillmentStatus::class,
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'estimated_delivery' => 'date',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function getTimelineAttribute(): array
    {
        $allStages = [
            OrderStatus::PLACED->value => 'Order Placed',
            OrderStatus::CONFIRMED->value => 'Order Confirmed',
            OrderStatus::PROCESSING->value => 'Processing',
            OrderStatus::PACKED->value => 'Packed',
            OrderStatus::DISPATCHED->value => 'Dispatched',
            OrderStatus::IN_TRANSIT->value => 'In Transit',
            OrderStatus::OUT_FOR_DELIVERY->value => 'Out for Delivery',
            OrderStatus::DELIVERED->value => 'Delivered',
        ];

        $currentStatusVal = $this->status->value;
        $orderStages = array_keys($allStages);
        $currentIndex = array_search($currentStatusVal, $orderStages);
        if ($currentIndex === false && $currentStatusVal === OrderStatus::CANCELLED->value) {
            $currentIndex = -1;
        }

        $historyMap = $this->statusHistories->keyBy('new_status');

        $timeline = [];
        foreach ($allStages as $key => $label) {
            $stageIndex = array_search($key, $orderStages);
            $history = $historyMap->get($key);
            $isCompleted = ($currentIndex !== false && $currentIndex >= 0 && $stageIndex <= $currentIndex);
            $isCurrent = ($key === $currentStatusVal);

            $statusEnum = OrderStatus::tryFrom($key);

            $timeline[] = [
                'status' => $key,
                'label' => $label,
                'description' => $history?->description ?? ($statusEnum ? $statusEnum->description() : ''),
                'timestamp' => $history ? $history->created_at->toISOString() : '',
                'completed' => $isCompleted,
                'current' => $isCurrent,
                'location' => $history?->location,
            ];
        }

        return $timeline;
    }
}

