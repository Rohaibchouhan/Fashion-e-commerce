<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $status,
        public ?string $location = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update on WearDay Order #{$this->order->order_number}: {$this->order->status->label()}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-update',
        );
    }
}

