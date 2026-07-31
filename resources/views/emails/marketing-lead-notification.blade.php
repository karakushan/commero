<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }}</title></head>
<body style="margin:0;background:#f4f4f4;color:#202020;font-family:Arial,sans-serif;line-height:1.5">
<div style="max-width:680px;margin:0 auto;padding:32px 16px"><div style="background:#fff;border:1px solid #e5e5e5;padding:28px">
    <h1 style="margin:0 0 12px;font-size:24px">{{ $title }}</h1>
    <p style="margin:0 0 20px"><strong>{{ __('commero::app.order_notifications.lead_type') }}:</strong> {{ $leadType }}</p>
    <table style="width:100%;border-collapse:collapse">
        @foreach($fields as $label => $value)
            @if(filled($value))<tr><td style="padding:8px 0;color:#666;vertical-align:top;width:35%">{{ $label }}</td><td style="padding:8px 0;white-space:pre-wrap">{{ $value }}</td></tr>@endif
        @endforeach
    </table>
    @if(filled($adminUrl))<p style="margin:28px 0 0"><a href="{{ $adminUrl }}" style="display:inline-block;background:#202020;color:#fff;text-decoration:none;padding:11px 16px">{{ __('commero::app.order_notifications.open_lead') }}</a></p>@endif
</div></div>
</body>
</html>
