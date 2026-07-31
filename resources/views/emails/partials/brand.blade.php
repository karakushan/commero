@php
    $notificationLogoUrl = config('commero.notifications.logo_url');
    $notificationSiteName = config('app.name', 'ShopHats');

    try {
        $notificationSiteSetting = \Commero\Models\SiteSetting::query()->first();
        $notificationLogoPath = $notificationSiteSetting?->logo_path ?: $notificationSiteSetting?->footer_logo_path;
        $notificationLogoUrl ??= filled($notificationLogoPath)
            ? url(Storage::disk('public')->url($notificationLogoPath))
            : null;
        $notificationSiteName = $notificationSiteSetting?->site_name ?: $notificationSiteName;
    } catch (\Throwable) {
        // Keep email rendering available even before site settings are installed.
    }
@endphp

<div style="margin:0 0 24px;text-align:center">
    @if(filled($notificationLogoUrl))
        <img src="{{ $notificationLogoUrl }}" alt="{{ $notificationSiteName }}" style="display:inline-block;max-width:220px;max-height:72px;width:auto;height:auto">
    @else
        <div style="font-size:20px;font-weight:700">{{ $notificationSiteName }}</div>
    @endif
</div>
