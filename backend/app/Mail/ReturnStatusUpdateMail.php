<?php

namespace App\Mail;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnStatusUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ReturnRequest $returnRequest,
        public string $status
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "WearDay Return #{$this->returnRequest->rma_number}: {$this->returnRequest->status->label()}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.returns.status-update',
        );
    }
}

