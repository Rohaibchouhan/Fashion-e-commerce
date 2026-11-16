<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WearDay Return Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f8; margin: 0; padding: 20px; color: #1a1a1a; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e7; }
        .header { background: #111111; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 30px; }
        .status-badge { background: #fef3c7; color: #b45309; padding: 6px 14px; border-radius: 4px; font-weight: 600; display: inline-block; }
        .btn { display: inline-block; background: #111111; color: #ffffff !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 15px; }
        .footer { background: #fafafa; padding: 20px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #e5e5e7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WearDay</h1>
        </div>
        <div class="content">
            <div style="text-align: center; margin-bottom: 20px;">
                <span class="status-badge">{{ $returnRequest->status->label() }}</span>
                <h2>Return RMA Update</h2>
                <p style="color: #666;">Return #<strong>{{ $returnRequest->rma_number }}</strong> (Order #{{ $returnRequest->order->order_number }})</p>
            </div>

            <p>Hi {{ $returnRequest->user->name }},</p>
            <p>We wanted to let you know that your return request status has changed to: <strong>{{ $returnRequest->status->label() }}</strong>.</p>

            @if($returnRequest->status->value === 'approved' && $returnRequest->label_url)
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <h4 style="margin: 0 0 8px 0; color: #166534;">Your Prepaid Return Label is Ready!</h4>
                <p style="margin: 0 0 10px 0; font-size: 13px;">Please print the label below, place items in the original box, and drop off at your nearest carrier depot.</p>
                <a href="{{ $returnRequest->label_url }}" class="btn" style="background: #166534;">Download Return Label PDF</a>
            </div>
            @endif

            @if($returnRequest->status->value === 'refunded')
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <h4 style="margin: 0 0 8px 0; color: #166534;">Refund Issued</h4>
                <p style="margin: 0; font-size: 14px;">A refund of <strong>${{ number_format($returnRequest->refund_amount, 2) }}</strong> has been credited back to your original payment method.</p>
            </div>
            @endif

            @if($returnRequest->status->value === 'rejected')
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; margin: 20px 0; color: #991b1b;">
                <h4 style="margin: 0 0 8px 0;">Return Request Declined</h4>
                <p style="margin: 0; font-size: 13px;">Reason: {{ $returnRequest->rejection_reason ?? 'Does not satisfy return conditions.' }}</p>
            </div>
            @endif
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} WearDay Fashion Atelier. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

