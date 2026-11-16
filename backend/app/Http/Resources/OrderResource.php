<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'orderNumber' => $this->order_number,
            'date' => $this->created_at->toISOString(),
            'status' => $this->status->value,
            'paymentStatus' => $this->payment_status->value,
            'fulfillmentStatus' => $this->fulfillment_status->value,
            'trackingNumber' => $this->tracking_number ?? 'Pending Allocation',
            'carrier' => $this->carrier ?? 'Standard Ground',
            'estimatedDelivery' => $this->estimated_delivery ? $this->estimated_delivery->toDateString() : null,
            'items' => $this->items->map(fn ($item) => [
                'id' => (string) $item->id,
                'productId' => (string) $item->product_id,
                'slug' => $item->product_slug,
                'name' => $item->product_name,
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'color' => $item->selected_color ?? '',
                'size' => $item->selected_size ?? '',
                'image' => $item->image_url ?? '',
            ])->toArray(),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'shipping' => (float) $this->shipping,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'shippingAddress' => $this->shipping_address,
            'billingAddress' => $this->billing_address,
            'paymentMethod' => $this->payment_method,
            'paymentLast4' => $this->payment_last4 ?? '8821',
            'timeline' => $this->timeline,
            'notes' => $this->notes,
        ];
    }
}

