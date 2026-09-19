@php
    $schemaCategory = $currentCategory ?? null;
@endphp

@if ($schemaCategory)
    @php
        $schemaCategoryItems = collect(
            isset($products) && is_object($products) && method_exists($products, 'items')
                ? $products->items()
                : ($products ?? [])
        )->filter(fn ($item): bool => filled(data_get($item, 'url')) && filled(data_get($item, 'name')))->values();
        $schemaCategoryUrl = $canonical ?? url()->current();

        if (! preg_match('/^https?:\/\//i', $schemaCategoryUrl)) {
            $schemaCategoryUrl = url('/'.ltrim($schemaCategoryUrl, '/'));
        }

        $toAbsoluteSchemaUrl = static fn (?string $href): ?string => filled($href)
            ? (preg_match('/^https?:\/\//i', $href) ? $href : url('/'.ltrim($href, '/')))
            : null;
        $schemaCategoryUrlParts = parse_url($schemaCategoryUrl);
        $schemaCategoryBaseUrl = ($schemaCategoryUrlParts['scheme'] ?? request()->getScheme()).'://'
            .($schemaCategoryUrlParts['host'] ?? request()->getHost())
            .(isset($schemaCategoryUrlParts['port']) ? ':'.$schemaCategoryUrlParts['port'] : '');
        $schemaLocale = $locale ?? app()->getLocale();
        $schemaHomeUrl = \Commero\Support\Locales::path('/', $schemaLocale);
        $schemaCatalogUrl = \Commero\Support\Locales::path('/catalog', $schemaLocale);
        $schemaCategoryBreadcrumbs = collect();
        $schemaCategoryCursor = $schemaCategory;

        while ($schemaCategoryCursor) {
            $schemaCategoryBreadcrumbs->prepend([
                'name' => $schemaCategoryCursor->translation($schemaLocale)?->name ?? $schemaCategoryCursor->path,
                'item' => $schemaCategoryCursor->frontendUrl($schemaLocale)
                    ?? \Commero\Support\Locales::path('/'.$schemaCategoryCursor->path, $schemaLocale),
            ]);
            $schemaCategoryCursor = $schemaCategoryCursor->parent;
        }
        $schemaBreadcrumbs = collect([
            ['name' => __('Home'), 'item' => $schemaHomeUrl],
            ['name' => __('Catalog'), 'item' => $schemaCatalogUrl],
        ])->merge($schemaCategoryBreadcrumbs);

        $schemaFaqItems = collect($categoryBlocks ?? [])
            ->filter(fn (array $block): bool => in_array($block['type'] ?? null, ['faq_section', 'return_faq_section'], true))
            ->flatMap(fn (array $block): array => (array) data_get($block, 'data.items', []))
            ->filter(fn (array $item): bool => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->map(fn (array $item): array => [
                '@'.'type' => 'Question',
                'name' => strip_tags((string) $item['question']),
                'acceptedAnswer' => [
                    '@'.'type' => 'Answer',
                    'text' => trim(strip_tags((string) $item['answer'])),
                ],
            ])
            ->values()
            ->all();
    @endphp

    <script type="application/ld+json">
    {!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@'.'type' => 'CollectionPage',
        'name' => $archiveTitle,
        'url' => $schemaCategoryUrl,
        'inLanguage' => str_replace('_', '-', $seo['html_lang'] ?? $schemaLocale),
        'isPartOf' => ['@'.'id' => $schemaCategoryBaseUrl.'/#website'],
        'breadcrumb' => [
            '@'.'type' => 'BreadcrumbList',
            'itemListElement' => $schemaBreadcrumbs->map(fn (array $item, int $index): array => array_filter([
                '@'.'type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $toAbsoluteSchemaUrl($item['item'] ?? null),
            ], fn ($value): bool => $value !== null))->values()->all(),
        ],
        'mainEntity' => [
            '@'.'type' => 'ItemList',
            'numberOfItems' => $schemaCategoryItems->count(),
            'itemListElement' => $schemaCategoryItems->map(fn ($item, int $index): array => [
                '@'.'type' => 'ListItem',
                'position' => $index + 1,
                'url' => $toAbsoluteSchemaUrl(data_get($item, 'url')),
                'name' => data_get($item, 'name'),
            ])->values()->all(),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    @if ($schemaFaqItems !== [])
        <script type="application/ld+json">
        {!! json_encode([
            '@'.'context' => 'https://schema.org',
            '@'.'type' => 'FAQPage',
            'mainEntity' => $schemaFaqItems,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
@endif
