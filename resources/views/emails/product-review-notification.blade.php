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
        @include('commero::emails.partials.brand')
        <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25">{{ $title }}</h1>
        <p style="margin:0 0 24px">{{ $intro }}</p>

        <table style="width:100%;border-collapse:collapse;margin:0 0 24px">
            <tr><td style="padding:7px 0;color:#666">{{ __('commero::app.order_notifications.product') }}</td><td style="padding:7px 0;text-align:right;font-weight:700">{{ $productName }}</td></tr>
            <tr><td style="padding:7px 0;color:#666">{{ __('commero::app.order_notifications.review_author') }}</td><td style="padding:7px 0;text-align:right">{{ $review->display_name }}</td></tr>
            @if(filled($review->email))
                <tr><td style="padding:7px 0;color:#666">{{ __('commero::app.order_notifications.email') }}</td><td style="padding:7px 0;text-align:right">{{ $review->email }}</td></tr>
            @endif
            <tr><td style="padding:7px 0;color:#666">{{ __('commero::app.order_notifications.rating') }}</td><td style="padding:7px 0;text-align:right;font-weight:700">{{ $review->rating }}/5</td></tr>
            @if(filled($review->title))
                <tr><td style="padding:7px 0;color:#666">{{ __('commero::app.order_notifications.review_title') }}</td><td style="padding:7px 0;text-align:right">{{ $review->title }}</td></tr>
            @endif
        </table>

        @if(filled($review->comment))
            <p style="margin:0 0 24px"><strong>{{ __('commero::app.order_notifications.message') }}:</strong><br>{{ $review->comment }}</p>
        @endif

        @if(filled($adminUrl))
            <p style="margin:28px 0 0"><a href="{{ $adminUrl }}" style="display:inline-block;background:#202020;color:#fff;text-decoration:none;padding:11px 16px">{{ __('commero::app.order_notifications.open_review') }}</a></p>
        @endif
    </div>
</div>
</body>
</html>
