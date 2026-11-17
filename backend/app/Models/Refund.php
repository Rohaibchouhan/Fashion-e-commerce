<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_number',
        'return_id',
        'order_id',
        'payment_id',
        'amount',
        'reason',
        'status',
        'gateway_refund_id',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class);
    }

    public function scopeReal($query)
    {
        return $query->whereHas('order', fn ($q) => $q->real());
    }

    public function scopeTest($query)
    {
        return $query->whereHas('order', fn ($q) => $q->test());
    }

    public function getIsTestAttribute(): bool
    {
        return $this->order?->is_test ?? false;
    }
}

