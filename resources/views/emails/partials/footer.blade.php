@php
    $footerLocale = $settingsLocale ?? $order->locale ?? app()->getLocale();
    $footerEmailLocale = $emailLocale ?? app()->getLocale();
    $footerSiteName = config('app.name', 'ShopHats');
    $footerContacts = [];
    $footerSocialLinks = [];
    $footerDefaultIconUrls = [
        'telegram' => 'https://cdn.simpleicons.org/telegram/229ED9',
        'viber' => 'https://cdn.simpleicons.org/viber/7360F2',
        'whatsapp' => 'https://cdn.simpleicons.org/whatsapp/25D366',
        'facebook' => 'https://cdn.simpleicons.org/facebook/1877F2',
        'instagram' => 'https://cdn.simpleicons.org/instagram/E4405F',
        'youtube' => 'https://cdn.simpleicons.org/youtube/FF0000',
        'tiktok' => 'https://cdn.simpleicons.org/tiktok/000000',
    ];
    $footerPlainSocialIdentifiers = ['telegram', 'viber'];

    try {
        $footerSetting = \Commero\Models\SiteSetting::query()->first();
        $footerSiteName = $footerSetting?->getSiteNameForLocale($footerLocale) ?: $footerSiteName;
        $footerContacts = $footerSetting?->getContactsForLocale($footerLocale) ?? [];
        $footerSocialLinks = $footerSetting?->getSocialLinksForLocale($footerLocale, false) ?? [];
    } catch (\Throwable) {
        // Keep email rendering available before site settings are installed.
    }

    $footerContactHref = static function (array $contact): ?string {
        $value = trim((string) ($contact['value'] ?? ''));
        $identifier = strtolower(trim((string) ($contact['identifier'] ?? '')));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '://')) {
            return $value;
        }

        if (in_array($identifier, ['phone', 'tel', 'mobile', 'viber', 'whatsapp', 'telegram'], true)) {
            return 'tel:'.preg_replace('/[^+\d]/', '', $value);
        }

        if (in_array($identifier, ['email', 'mail'], true) || filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'mailto:'.$value;
        }

        return 'https://'.$value;
    };

    $footerIconUrl = static function (?string $path, ?string $identifier = null) use ($footerDefaultIconUrls): ?string {
        if (filled($path)) {
            return url(Storage::disk('public')->url($path));
        }

        return $footerDefaultIconUrls[strtolower(trim((string) $identifier))] ?? null;
    };

    $footerContactLabels = [
        'phone' => __('commero::app.order_notifications.contact_phone', [], $footerEmailLocale),
        'tel' => __('commero::app.order_notifications.contact_phone', [], $footerEmailLocale),
        'mobile' => __('commero::app.order_notifications.contact_phone', [], $footerEmailLocale),
        'address' => __('commero::app.order_notifications.contact_address', [], $footerEmailLocale),
        'working_hours' => __('commero::app.order_notifications.contact_working_hours', [], $footerEmailLocale),
        'email' => __('commero::app.order_notifications.contact_email', [], $footerEmailLocale),
        'mail' => __('commero::app.order_notifications.contact_email', [], $footerEmailLocale),
    ];
@endphp

<div style="padding:22px 8px 0;text-align:center;color:#666;font-size:12px;line-height:1.5">
    @if($footerContacts !== [])
        <div style="margin:0 0 12px">
            @foreach($footerContacts as $contact)
                @php($contactHref = $footerContactHref($contact))
                @php($contactIcon = $footerIconUrl($contact['icon'] ?? null, $contact['identifier'] ?? null))
                @php($contactIdentifier = strtolower(trim((string) ($contact['identifier'] ?? ''))))
                @if(filled($contactHref))
                    <a href="{{ $contactHref }}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;margin:0 8px 8px;color:#444;text-decoration:none">
                        @if(filled($contactIcon))
                            <img src="{{ $contactIcon }}" alt="" width="22" height="22" style="display:inline-block;width:22px;height:22px;object-fit:contain;margin-right:5px;vertical-align:middle">
                        @endif
                        <span>{{ $footerContactLabels[$contactIdentifier] ?? $contact['label'] ?? $contact['value'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    @if($footerSocialLinks !== [])
        <div style="margin:0 0 12px;text-align:center">
            <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" style="margin:0 auto;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt">
                <tr>
                @foreach($footerSocialLinks as $social)
                    @php($socialUrl = trim((string) ($social['url'] ?? '')))
                    @php($socialIcon = $footerIconUrl($social['icon'] ?? null, $social['identifier'] ?? null))
                    @php($socialLabel = $social['label'] ?? $social['identifier'] ?? $socialUrl)
                    @php($isPlainSocial = in_array(strtolower(trim((string) ($social['identifier'] ?? ''))), $footerPlainSocialIdentifiers, true))
                    @if(filled($socialUrl))
                        <td align="center" valign="middle" style="padding:0 5px;vertical-align:middle;line-height:0;font-size:0">
                            @if(filled($socialIcon))
                                @if($isPlainSocial)
                                    <img src="{{ $socialIcon }}" alt="{{ $socialLabel }}" width="28" height="28" style="display:block;width:28px;height:28px;object-fit:contain;margin:0 auto;border:0">
                                @else
                                    <a href="{{ $socialUrl }}" target="_blank" rel="noopener noreferrer" style="display:block;line-height:0;font-size:0;text-decoration:none">
                                        <img src="{{ $socialIcon }}" alt="{{ $socialLabel }}" width="28" height="28" style="display:block;width:28px;height:28px;object-fit:contain;margin:0 auto;border:0">
                                    </a>
                                @endif
                            @elseif($isPlainSocial)
                                <span style="color:#444;font-size:12px;line-height:28px;white-space:nowrap">{{ $socialLabel }}</span>
                            @else
                                <a href="{{ $socialUrl }}" target="_blank" rel="noopener noreferrer" style="display:block;color:#444;text-decoration:none;font-size:12px;line-height:28px;white-space:nowrap">
                                    {{ $socialLabel }}
                                </a>
                            @endif
                        </td>
                    @endif
                @endforeach
                </tr>
            </table>
        </div>
    @endif

    <div>{{ __('commero::app.order_notifications.copyright', ['year' => now()->year, 'site' => $footerSiteName], $footerEmailLocale) }}</div>
</div>
