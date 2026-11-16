<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdatedEvent;
use App\Mail\OrderStatusUpdateMail;
use App\Models\EmailLog;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected NotificationService $notificationService) {}

    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->order;
        $customer = $order->customer;

        // In-app notification
        $this->notificationService->sendCustomerNotification(
            $customer,
            "Order #{$order->order_number}: {$order->status->label()}",
            $order->status->description() . ($event->location ? " Current location: {$event->location}." : ""),
            "/account/orders/{$order->order_number}",
            'shipping'
        );

        // Queue status email update
        Mail::to($customer->email)->queue(new OrderStatusUpdateMail($order, $event->newStatus, $event->location));

        EmailLog::create([
            'recipient_email' => $customer->email,
            'recipient_name' => $customer->name,
            'subject' => "Update on WearDay Order #{$order->order_number}: {$order->status->label()}",
            'template_name' => 'emails.orders.status-update',
            'status' => 'queued',
            'payload' => [
                'order_id' => $order->id,
                'status' => $event->newStatus,
                'location' => $event->location,
            ],
            'sent_at' => now(),
        ]);
    }
}

