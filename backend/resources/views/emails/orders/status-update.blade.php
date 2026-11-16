<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WearDay Order Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f8; margin: 0; padding: 20px; color: #1a1a1a; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e7; }
        .header { background: #111111; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 30px; }
        .status-badge { background: #eff6ff; color: #1d4ed8; padding: 6px 14px; border-radius: 4px; font-weight: 600; display: inline-block; }
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
                <span class="status-badge">{{ $order->status->label() }}</span>
                <h2>Shipment Status Update</h2>
                <p style="color: #666;">Order #<strong>{{ $order->order_number }}</strong></p>
            </div>

            <p>Hi {{ $order->customer->name }},</p>
            <p>{{ $order->status->description() }}</p>

            @if($location)
            <p><strong>Current Checkpoint Location:</strong> {{ $location }}</p>
            @endif

            @if($order->tracking_number && $order->tracking_number !== 'Pending Allocation')
            <div style="background: #f7f7f8; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <p style="margin: 0;"><strong>Carrier:</strong> {{ $order->carrier }}</p>
                <p style="margin: 4px 0 0 0;"><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
            </div>
            @endif

            <div style="text-align: center; margin-top: 25px;">
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/track?order={{ $order->order_number }}" class="btn">View Live Tracking Timeline</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} WearDay Fashion Atelier. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

