@php
    $schemaCanonical = $canonical ?? url()->current();

    if (! preg_match('/^https?:\/\//i', $schemaCanonical)) {
        $schemaCanonical = url('/'.ltrim($schemaCanonical, '/'));
    }

    $schemaUrlParts = parse_url($schemaCanonical);
    $schemaBaseUrl = ($schemaUrlParts['scheme'] ?? request()->getScheme()).'://'
        .($schemaUrlParts['host'] ?? request()->getHost())
        .(isset($schemaUrlParts['port']) ? ':'.$schemaUrlParts['port'] : '');
    $schemaOrganizationId = $schemaBaseUrl.'/#organization';
    $schemaWebsiteId = $schemaBaseUrl.'/#website';
    $schemaLocale = str_replace('_', '-', $resolvedSeo['html_lang'] ?? app()->getLocale());
    $schemaSetting = $siteSetting ?? \Commero\Models\SiteSetting::query()->first();
    $schemaSettingLocale = app()->getLocale();
    $schemaName = $schemaSetting?->getSiteNameForLocale($schemaSettingLocale) ?: config('app.name', 'Commero');
    $schemaLogoPath = $schemaSetting?->getFooterLogoPathForLocale($schemaSettingLocale)
        ?: $schemaSetting?->getLogoPathForLocale($schemaSettingLocale);
    $schemaLogo = filled($schemaLogoPath) ? asset('storage/'.ltrim($schemaLogoPath, '/')) : null;
    $schemaContacts = collect($schemaSetting?->contacts ?? []);
    $schemaPhoneContact = $schemaContacts->first(function (array $contact): bool {
        $identifier = mb_strtolower(trim((string) ($contact['identifier'] ?? '')));
        $label = mb_strtolower(trim((string) ($contact['label'] ?? '')));

        return in_array($identifier, ['phone', 'telephone'], true)
            || in_array($label, ['телефон', 'phone', 'telephone'], true);
    });
    $schemaSocialLinks = collect($schemaSetting?->social_links ?? [])
        ->pluck('url')
        ->filter(fn ($url): bool => is_string($url) && preg_match('/^https?:\/\//i', $url))
        ->values()
        ->all();
    $schemaSearchUrl = \Commero\Support\Locales::path('/search', app()->getLocale());
    $schemaSearchUrl = preg_match('/^https?:\/\//i', $schemaSearchUrl)
        ? $schemaSearchUrl
        : url('/'.ltrim($schemaSearchUrl, '/'));
@endphp

<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@'.'graph' => [
        array_filter([
            '@'.'type' => 'Organization',
            '@'.'id' => $schemaOrganizationId,
            'name' => $schemaName,
            'url' => $schemaBaseUrl.'/',
            'logo' => $schemaLogo,
            'contactPoint' => filled($schemaPhoneContact['value'] ?? null) ? [
                '@'.'type' => 'ContactPoint',
                'telephone' => $schemaPhoneContact['value'],
                'contactType' => 'sales',
                'areaServed' => 'UA',
                'availableLanguage' => [$schemaLocale],
            ] : null,
            'sameAs' => $schemaSocialLinks !== [] ? $schemaSocialLinks : null,
        ], fn ($value): bool => $value !== null && $value !== []),
        [
            '@'.'type' => 'WebSite',
            '@'.'id' => $schemaWebsiteId,
            'url' => $schemaBaseUrl.'/',
            'name' => $schemaName,
            'inLanguage' => $schemaLocale,
            'publisher' => ['@'.'id' => $schemaOrganizationId],
            'potentialAction' => [
                '@'.'type' => 'SearchAction',
                'target' => [
                    '@'.'type' => 'EntryPoint',
                    'urlTemplate' => rtrim($schemaSearchUrl, '/').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
