{{-- Analytics in <head>: Google Tag Manager, Google Analytics 4 (gtag.js), Meta Pixel and custom head code (site settings). --}}
@php
    $gtmId = \Commero\Marketing\Tracking::googleTagManagerId();
    $gaId = \Commero\Marketing\Tracking::googleAnalyticsId();
    $pixelId = \Commero\Marketing\Tracking::facebookPixelId();
    $headCode = \Commero\Marketing\Tracking::headCode();
@endphp
@if ($gtmId)
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@js($gtmId));
    </script>
@endif
@if ($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @js($gaId));
    </script>
@endif
@if ($pixelId)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @js($pixelId));
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1"></noscript>
@endif
@if ($headCode)
    {!! $headCode !!}
@endif
