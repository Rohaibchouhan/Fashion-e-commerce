<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to WearDay — Set Your Password</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f8; margin: 0; padding: 20px; color: #1a1a1a; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e7; }
        .header { background: #111111; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 35px; text-align: center; }
        .btn { display: inline-block; background: #111111; color: #ffffff !important; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600; margin: 20px 0; }
        .footer { background: #fafafa; padding: 20px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #e5e5e7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WearDay</h1>
        </div>
        <div class="content">
            <h2>Welcome to WearDay</h2>
            <p>Hello {{ $user->name }},</p>
            <p>Your customer account was automatically created during your recent checkout so you can monitor real-time order tracking, access invoice receipts, and manage future orders.</p>
            <p>Please click below to set up your personal password and activate your account:</p>
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/auth/reset-password?token={{ $activationToken }}&email={{ urlencode($user->email) }}" class="btn">Set My Password</a>
            <p style="font-size: 12px; color: #888;">If you did not make this purchase, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} WearDay Fashion Atelier. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

