<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'rmaNumber' => $this->rma_number,
            'orderId' => (string) $this->order_id,
            'orderNumber' => $this->order->order_number,
            'createdAt' => $this->created_at->toISOString(),
            'status' => $this->status->value,
            'items' => $this->items->map(fn ($item) => [
                'orderItemId' => (string) $item->order_item_id,
                'productId' => (string) $item->product_id,
                'name' => $item->product_name,
                'color' => $item->selected_color ?? '',
                'size' => $item->selected_size ?? '',
                'image' => $item->image_url ?? '',
                'price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'reason' => $item->reason,
                'comment' => $item->customer_comment,
            ])->toArray(),
            'resolution' => $this->resolution,
            'exchangeDetails' => $this->exchange_details,
            'refundAmount' => (float) $this->refund_amount,
            'trackingNumber' => $this->tracking_number,
            'labelUrl' => $this->label_url,
            'timeline' => $this->timeline,
        ];
    }
}

