<?php

namespace App\Listeners;

use App\Events\OrderPlacedEvent;
use App\Mail\AccountSetupMail;
use App\Mail\OrderConfirmationMail;
use App\Models\EmailLog;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected NotificationService $notificationService) {}

    public function handle(OrderPlacedEvent $event): void
    {
        $order = $event->order;
        $customer = $order->customer;

        // 1. Send in-app notification
        $this->notificationService->sendCustomerNotification(
            $customer,
            "Order Confirmed: #{$order->order_number}",
            "Thank you! We've received your order totaling \${$order->total}.",
            "/account/orders/{$order->order_number}",
            'order'
        );

        // 2. Queue Order Confirmation Email
        Mail::to($customer->email)->queue(new OrderConfirmationMail($order));

        EmailLog::create([
            'recipient_email' => $customer->email,
            'recipient_name' => $customer->name,
            'subject' => "Your WearDay Order Confirmation [{$order->order_number}]",
            'template_name' => 'emails.orders.confirmation',
            'status' => 'queued',
            'payload' => ['order_id' => $order->id, 'total' => $order->total],
            'sent_at' => now(),
        ]);

        // 3. If new customer auto-registered at checkout, send password setup email
        if ($event->isNewCustomer && $event->activationToken) {
            Mail::to($customer->email)->queue(new AccountSetupMail($customer, $event->activationToken));

            EmailLog::create([
                'recipient_email' => $customer->email,
                'recipient_name' => $customer->name,
                'subject' => "Welcome to WearDay — Set Your Password",
                'template_name' => 'emails.auth.account-setup',
                'status' => 'queued',
                'payload' => ['user_id' => $customer->id],
                'sent_at' => now(),
            ]);
        }
    }
}

