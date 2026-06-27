<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Thanks for your rating</title>
    <link rel="shortcut icon" href="{{ settings()->favicon_image }}" />
    <style>
        html, body { margin: 0; background: #f6f7f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Cairo', sans-serif; color: #1f2937; }
        .wrap { max-width: 480px; margin: 48px auto; padding: 0 16px; text-align: center; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); padding: 32px 24px; }
        .check { width: 64px; height: 64px; border-radius: 50%; background: #ecfdf5; color: #047857; display: inline-flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 12px; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        p { color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="check">✓</div>
            <h1>Thanks!</h1>
            <p>Your feedback helps us deliver better.</p>
            <p style="font-size:12px;color:#9ca3af;">Tracking {{ $parcel->tracking_id }}</p>
        </div>
    </div>
</body>
</html>
