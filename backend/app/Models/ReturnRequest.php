<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'rma_number',
        'order_id',
        'user_id',
        'status',
        'rejection_reason',
        'resolution',
        'exchange_details',
        'refund_amount',
        'tracking_number',
        'label_url',
        'admin_notes',
        'inspected_at',
        'inspection_passed',
        'restocked',
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'refund_amount' => 'decimal:2',
        'inspected_at' => 'datetime',
        'inspection_passed' => 'boolean',
        'restocked' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'return_id');
    }

    public function exchange(): HasOne
    {
        return $this->hasOne(Exchange::class, 'return_id');
    }

    public function getTimelineAttribute(): array
    {
        $allStages = [
            ReturnStatus::REQUESTED->value => 'Return Requested',
            ReturnStatus::APPROVED->value => 'Return Approved',
            ReturnStatus::PICKUP_SCHEDULED->value => 'Item Dropped Off / Pickup',
            ReturnStatus::INSPECTED->value => 'Quality Inspected',
            ReturnStatus::REFUNDED->value => 'Refund Issued',
        ];

        $currentVal = $this->status->value;
        $keys = array_keys($allStages);
        $currIdx = array_search($currentVal, $keys);

        $timeline = [];
        foreach ($allStages as $k => $label) {
            $stageIdx = array_search($k, $keys);
            $completed = ($currIdx !== false && $stageIdx <= $currIdx);
            $isCurrent = ($k === $currentVal);

            $timeline[] = [
                'status' => $k,
                'label' => $label,
                'date' => $completed ? $this->updated_at->toISOString() : '',
                'completed' => $completed,
                'current' => $isCurrent,
            ];
        }

        return $timeline;
    }
}

