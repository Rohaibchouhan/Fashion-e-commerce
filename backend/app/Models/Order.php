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
        'idempotency_hash',
        'guest_session_id',
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

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
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

    public function scopeReal($query)
    {
        return $query->whereDoesntHave('payments', fn ($q) => $q->where('is_test', true));
    }

    public function scopeTest($query)
    {
        return $query->whereHas('payments', fn ($q) => $q->where('is_test', true));
    }

    public function getIsTestAttribute(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains('is_test', true);
        }
        return (bool) $this->payments()->where('is_test', true)->exists();
    }

    public function getTrackingUrlAttribute(): ?string
    {
        if (!$this->tracking_number || $this->tracking_number === 'Pending Allocation') {
            return null;
        }

        $carrier = strtolower($this->carrier ?? '');
        $tracking = urlencode(trim($this->tracking_number));

        if (str_contains($carrier, 'fedex')) {
            return "https://www.fedex.com/fedextrack/?trknbr={$tracking}";
        }
        if (str_contains($carrier, 'ups')) {
            return "https://www.ups.com/track?tracknum={$tracking}";
        }
        if (str_contains($carrier, 'usps')) {
            return "https://tools.usps.com/go/TrackConfirmAction?tLabels={$tracking}";
        }
        if (str_contains($carrier, 'dhl')) {
            return "https://www.dhl.com/en/express/tracking.html?AWB={$tracking}";
        }

        return null;
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

