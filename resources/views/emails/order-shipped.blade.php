<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('mails.Order Shipped') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #2c3e50;">{{ __('mails.Your order is on the way!') }}</h2>

    <p>{{ __('mails.Hello!') }} {{ $order->customer->name }},</p>

    <p>{{ __('mails.Good news! Your order :number has been shipped.', ['number' => $orderNumber]) }}</p>

    <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #17a2b8; margin: 20px 0;">
        <p><strong>{{ __('mails.Carrier') }}:</strong> {{ $carrier }}</p>
        <p><strong>{{ __('mails.Tracking number') }}:</strong> {{ $trackingNumber }}</p>
        <p style="margin-bottom: 0;">
            <a href="{{ $trackingUrl }}" style="color: #007bff; text-decoration: none;">
                {{ __('mails.Track your parcel') }} &rarr;
            </a>
        </p>
    </div>

    <p>{{ __('mails.Thank you for choosing us!') }}</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #777;">{{ __('mails.This is an automated message, please do not reply.') }}</p>
</body>
</html>
