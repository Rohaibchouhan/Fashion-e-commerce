<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'sessionId' => $this->session_id,
            'itemsCount' => (int) $this->items->sum('quantity'),
            'items' => $this->items->map(fn ($item) => [
                'id' => (string) $item->id,
                'productId' => (string) $item->product_id,
                'variantId' => $item->variant_id ? (string) $item->variant_id : null,
                'sku' => $item->variant ? $item->variant->sku : $item->product->sku,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'maxStock' => $item->variant ? $item->variant->available_stock : (int) ($item->product->variants->sum('stock') ?? 0),
                'selectedColor' => $item->selected_color,
                'selectedSize' => $item->selected_size,
                'image' => $item->variant?->image ?? $item->product->images->first()?->url,
                'subtotal' => (float) $item->subtotal,
            ])->toArray(),
            'couponCode' => $this->coupon_code,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount_amount,
            'shipping' => (float) $this->shipping_amount,
            'tax' => (float) $this->tax_amount,
            'total' => (float) $this->total,
        ];
    }
}
