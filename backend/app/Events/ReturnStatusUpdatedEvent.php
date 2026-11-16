<?php

namespace App\Events;

use App\Models\ReturnRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnStatusUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReturnRequest $returnRequest,
        public ?string $previousStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.' . $this->returnRequest->user_id),
            new PrivateChannel('admin.returns'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'return.status_updated';
    }

    public function broadcastWith(): array
    {
        return [
            'return_id' => $this->returnRequest->id,
            'rma_number' => $this->returnRequest->rma_number,
            'order_id' => $this->returnRequest->order_id,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'refund_amount' => (float) $this->returnRequest->refund_amount,
            'updated_at' => now()->toISOString(),
            'timeline' => $this->returnRequest->timeline,
        ];
    }
}

