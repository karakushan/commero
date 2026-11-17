{{-- Right after <body>: Google Tag Manager (noscript). --}}
@php($gtmId = \Commero\Marketing\Tracking::googleTagManagerId())
@if ($gtmId)
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
