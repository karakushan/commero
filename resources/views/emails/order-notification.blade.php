<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#f4f4f4;color:#202020;font-family:Arial,sans-serif;line-height:1.5">
<div style="max-width:680px;margin:0 auto;padding:32px 16px">
    <div style="background:#fff;border:1px solid #e5e5e5;padding:28px">
        <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25">{{ $title }}</h1>
        <p style="margin:0 0 24px">{{ $intro }}</p>

        <table style="width:100%;border-collapse:collapse;margin:0 0 24px">
            <tr><td style="padding:7px 0;color:#666">{{ __('commero::admin.order.number') }}</td><td style="padding:7px 0;text-align:right;font-weight:700">{{ $order->number }}</td></tr>
            <tr><td style="padding:7px 0;color:#666">{{ __('commero::admin.order.customer_name') }}</td><td style="padding:7px 0;text-align:right">{{ $order->customer_name }}</td></tr>
            @if(filled($order->customer_email))
                <tr><td style="padding:7px 0;color:#666">{{ __('commero::admin.order.customer_email') }}</td><td style="padding:7px 0;text-align:right">{{ $order->customer_email }}</td></tr>
            @endif
            @if(isset($previousStatusLabel))
                <tr><td style="padding:7px 0;color:#666">{{ __('commero::app.order_notifications.previous_status') }}</td><td style="padding:7px 0;text-align:right">{{ $previousStatusLabel }}</td></tr>
            @endif
            <tr><td style="padding:7px 0;color:#666">{{ __('commero::admin.common.status') }}</td><td style="padding:7px 0;text-align:right;font-weight:700">{{ $statusLabel }}</td></tr>
        </table>

        <h2 style="font-size:18px;margin:0 0 10px">{{ __('commero::admin.order.items') }}</h2>
        <table style="width:100%;border-collapse:collapse;border-top:1px solid #ddd">
            <thead><tr>
                <th style="padding:10px 4px;text-align:left;border-bottom:1px solid #ddd">{{ __('commero::admin.order.product') }}</th>
                <th style="padding:10px 4px;text-align:right;border-bottom:1px solid #ddd">{{ __('commero::admin.order.quantity') }}</th>
                <th style="padding:10px 4px;text-align:right;border-bottom:1px solid #ddd">{{ __('commero::admin.order.total_amount') }}</th>
            </tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td style="padding:10px 4px;border-bottom:1px solid #eee">
                        <strong>{{ $item->product_name }}</strong>
                        @if(filled($item->variant_name))<br><span style="color:#666;font-size:13px">{{ $item->variant_name }}</span>@endif
                        @if(filled($item->product_sku))<br><span style="color:#888;font-size:12px">SKU: {{ $item->product_sku }}</span>@endif
                    </td>
                    <td style="padding:10px 4px;text-align:right;border-bottom:1px solid #eee">{{ $item->quantity }}</td>
                    <td style="padding:10px 4px;text-align:right;border-bottom:1px solid #eee">{{ number_format((float) $item->unit_price * (int) $item->quantity, 2, '.', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr>
                <td colspan="2" style="padding:14px 4px;text-align:right;font-weight:700">{{ __('commero::admin.order.total_amount') }}</td>
                <td style="padding:14px 4px;text-align:right;font-weight:700">{{ number_format((float) $order->total_amount, 2, '.', ' ') }}</td>
            </tr></tfoot>
        </table>

        @if(filled($order->comment))<p style="margin:24px 0 0"><strong>{{ __('commero::admin.order.comment') }}:</strong><br>{{ $order->comment }}</p>@endif
        @if(filled($adminUrl))
            <p style="margin:28px 0 0"><a href="{{ $adminUrl }}" style="display:inline-block;background:#202020;color:#fff;text-decoration:none;padding:11px 16px">{{ __('commero::app.order_notifications.open_order') }}</a></p>
        @endif
    </div>
</div>
</body>
</html>
