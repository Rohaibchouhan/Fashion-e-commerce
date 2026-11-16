<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WearDay Order Confirmation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f8; margin: 0; padding: 20px; color: #1a1a1a; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e7; }
        .header { background: #111111; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 30px; }
        .order-badge { background: #f0fdf4; color: #166534; padding: 6px 12px; border-radius: 4px; display: inline-block; font-weight: 600; font-size: 13px; }
        .item-row { display: flex; padding: 12px 0; border-bottom: 1px solid #f0f0f2; }
        .item-info { flex: 1; padding-left: 12px; }
        .summary-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .total-row { display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid #111111; font-size: 18px; font-weight: 700; }
        .footer { background: #fafafa; padding: 20px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #e5e5e7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WearDay</h1>
        </div>
        <div class="content">
            <div style="text-align: center; margin-bottom: 24px;">
                <span class="order-badge">Order Confirmed</span>
                <h2 style="margin: 12px 0 4px 0;">Thank you for your order!</h2>
                <p style="color: #666666; margin: 0;">Order #<strong>{{ $order->order_number }}</strong></p>
            </div>

            <p>Hi {{ $order->customer->name }},</p>
            <p>We've received your order and our atelier has begun preparing your garments with meticulous care.</p>

            <h3 style="border-bottom: 1px solid #eeeeee; padding-bottom: 8px; margin-top: 24px;">Order Items</h3>
            <table style="width: 100%; border-collapse: collapse;">
                @foreach($order->items as $item)
                <tr style="border-bottom: 1px solid #f0f0f2;">
                    <td style="padding: 10px 0; width: 60px;">
                        @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->product_name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        @endif
                    </td>
                    <td style="padding: 10px 12px;">
                        <strong>{{ $item->product_name }}</strong><br>
                        <span style="font-size: 12px; color: #666;">Size: {{ $item->selected_size ?? 'N/A' }} | Color: {{ $item->selected_color ?? 'N/A' }} | Qty: {{ $item->quantity }}</span>
                    </td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600;">
                        ${{ number_format($item->subtotal, 2) }}
                    </td>
                </tr>
                @endforeach
            </table>

            <div style="margin-top: 20px;">
                <table style="width: 100%;">
                    <tr><td>Subtotal</td><td style="text-align: right;">${{ number_format($order->subtotal, 2) }}</td></tr>
                    @if($order->discount > 0)
                    <tr><td style="color: #166534;">Discount ({{ $order->coupon_code }})</td><td style="text-align: right; color: #166534;">-${{ number_format($order->discount, 2) }}</td></tr>
                    @endif
                    <tr><td>Shipping ({{ $order->carrier }})</td><td style="text-align: right;">${{ number_format($order->shipping, 2) }}</td></tr>
                    <tr><td>Tax</td><td style="text-align: right;">${{ number_format($order->tax, 2) }}</td></tr>
                    <tr style="font-size: 18px; font-weight: bold; border-top: 2px solid #111;">
                        <td style="padding-top: 10px;">Total</td>
                        <td style="text-align: right; padding-top: 10px;">${{ number_format($order->total, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div style="background: #fbfbfb; border-radius: 6px; padding: 15px; margin-top: 24px; font-size: 13px;">
                <strong>Shipping To:</strong><br>
                {{ $order->shipping_address['firstName'] ?? $order->shipping_address['first_name'] ?? '' }} {{ $order->shipping_address['lastName'] ?? $order->shipping_address['last_name'] ?? '' }}<br>
                {{ $order->shipping_address['street'] ?? '' }} {{ $order->shipping_address['apartment'] ?? '' }}<br>
                {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['zipCode'] ?? $order->shipping_address['zip_code'] ?? '' }}<br>
                {{ $order->shipping_address['country'] ?? '' }}
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} WearDay Fashion Atelier. All rights reserved.<br>
            Questions? Contact <a href="mailto:support@wearday.com" style="color: #111;">support@wearday.com</a></p>
        </div>
    </div>
</body>
</html>

