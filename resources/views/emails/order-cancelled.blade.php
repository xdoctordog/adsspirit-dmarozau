<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('mails.Order Cancelled') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #dc3545;">{{ __('mails.Order Cancelled') }}</h2>

    <p>{{ __('mails.Hello!') }} {{ $order->customer->name }},</p>

    <p>{{ __('mails.We regret to inform you that your order :number has been cancelled.', ['number' => $orderNumber]) }}</p>

    @if(!empty($cancelReason))
    <p style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #dc3545;">
        <strong>{{ __('mails.Cancellation reason') }}:</strong> {{ $cancelReason }}
    </p>
    @endif

    <!-- Refund Notice (Conditional) -->
    @if($order->paid_at)
        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; color: #856404;">
            <strong>{{ __('mails.Refund Notice') }}:</strong>
            {{ __('mails.Since your order was already paid, the refund will be processed and returned to your original payment method within 10 days.') }}
        </div>
    @endif

    <p>{{ __('mails.If you have any questions, please contact our support team.') }}</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #777;">{{ __('mails.This is an automated message, please do not reply.') }}</p>
</body>
</html>
