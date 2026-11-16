<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnImage extends Model
{
    use HasFactory;

    protected $fillable = ['return_item_id', 'url'];

    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(ReturnItem::class);
    }
}

