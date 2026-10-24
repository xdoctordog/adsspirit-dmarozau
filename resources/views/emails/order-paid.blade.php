<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('mails.Order Paid') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #2c3e50;">{{ __('mails.Order Paid') }}</h2>

    <p>{{ __('mails.Hello!') }} {{ $order->customer->name }},</p>

    <p>{{ __('mails.Your order :number has been successfully paid.', ['number' => $order->number]) }}</p>

    <!-- Items Table -->
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 10px; text-align: left;">{{ __('mails.Item') }}</th>
                <th style="padding: 10px; text-align: center;">{{ __('mails.Qty') }}</th>
                <th style="padding: 10px; text-align: right;">{{ __('mails.Price') }}</th>
                <th style="padding: 10px; text-align: right;">{{ __('mails.Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;">{{ $item->name }}</td>
                    <td style="padding: 10px; text-align: center;">{{ $item->quantity }}</td>
                    <td style="padding: 10px; text-align: right;">{{ $item->price }} {{ \App\Enums\Currency::from($order->currency)->symbol() }}</td>
                    <td style="padding: 10px; text-align: right;">{{ $item->subtotal }} {{ \App\Enums\Currency::from($order->currency)->symbol() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Order Summary -->
    <table style="width: 100%; max-width: 300px; margin-left: auto; margin-top: 10px;">
        <tr>
            <td style="padding: 5px 10px; text-align: right; color: #666;">{{ __('mails.Delivery') }}:</td>
            <td style="padding: 5px 10px; text-align: right;">{{ $order->delivery_price }} {{ \App\Enums\Currency::from($order->currency)->symbol() }}</td>
        </tr>
        @if($order->discount > 0)
            <tr>
                <td style="padding: 5px 10px; text-align: right; color: #666;">{{ __('mails.Discount') }}:</td>
                <td style="padding: 5px 10px; text-align: right; color: #dc3545;">-{{ $order->discount }} {{ \App\Enums\Currency::from($order->currency)->symbol() }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 10px; text-align: right; font-weight: bold; border-top: 2px solid #dee2e6;">{{ __('mails.Total') }}:</td>
            <td style="padding: 10px; text-align: right; font-weight: bold; border-top: 2px solid #dee2e6; font-size: 18px;">
                {{ $order->total }} {{ \App\Enums\Currency::from($order->currency)->symbol() }}
            </td>
        </tr>
    </table>

    <p style="margin-top: 30px;">{{ __('mails.Thank you for your purchase!') }}</p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #777;">{{ __('mails.This is an automated message, please do not reply.') }}</p>
</body>
</html>
