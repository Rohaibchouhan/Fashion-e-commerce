<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'type',
        'amount',
        'reference_id',
        'status',
        'is_test',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_test' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

