<?php

namespace App\Listeners;

use App\Events\ReturnStatusUpdatedEvent;
use App\Mail\ReturnStatusUpdateMail;
use App\Models\EmailLog;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendReturnStatusNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected NotificationService $notificationService) {}

    public function handle(ReturnStatusUpdatedEvent $event): void
    {
        $returnRequest = $event->returnRequest;
        $user = $returnRequest->user;

        // In-app notification
        $this->notificationService->sendCustomerNotification(
            $user,
            "Return #{$returnRequest->rma_number}: {$returnRequest->status->label()}",
            "Your return request for Order #{$returnRequest->order->order_number} has been updated to {$returnRequest->status->label()}.",
            "/account/returns/{$returnRequest->rma_number}",
            'returns'
        );

        // Queue email update
        Mail::to($user->email)->queue(new ReturnStatusUpdateMail($returnRequest, $event->newStatus));

        EmailLog::create([
            'recipient_email' => $user->email,
            'recipient_name' => $user->name,
            'subject' => "WearDay Return #{$returnRequest->rma_number}: {$returnRequest->status->label()}",
            'template_name' => 'emails.returns.status-update',
            'status' => 'queued',
            'payload' => [
                'return_id' => $returnRequest->id,
                'status' => $event->newStatus,
            ],
            'sent_at' => now(),
        ]);
    }
}

