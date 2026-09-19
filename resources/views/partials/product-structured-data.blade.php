@php
    $schemaProductName = trim((string) ($product['name'] ?? ''));
    $schemaProductUrl = $canonical ?? url()->current();

    if (! preg_match('/^https?:\/\//i', $schemaProductUrl)) {
        $schemaProductUrl = url('/'.ltrim($schemaProductUrl, '/'));
    }

    $toAbsoluteSchemaUrl = static fn (?string $href): ?string => filled($href)
        ? (preg_match('/^https?:\/\//i', $href) ? $href : url('/'.ltrim($href, '/')))
        : null;
    $schemaProductUrlParts = parse_url($schemaProductUrl);
    $schemaProductBaseUrl = ($schemaProductUrlParts['scheme'] ?? request()->getScheme()).'://'
        .($schemaProductUrlParts['host'] ?? request()->getHost())
        .(isset($schemaProductUrlParts['port']) ? ':'.$schemaProductUrlParts['port'] : '');
    $schemaProductImages = collect($product['gallery'] ?? [])
        ->filter(fn ($image): bool => is_array($image))
        ->pluck('full')
        ->merge([$product['image'] ?? null])
        ->map(fn ($url): ?string => $toAbsoluteSchemaUrl(is_string($url) ? $url : null))
        ->filter()
        ->unique()
        ->values()
        ->all();
    $schemaRichTextToString = static function (mixed $value) use (&$schemaRichTextToString): string {
        if (! is_array($value)) {
            return is_scalar($value) ? (string) $value : '';
        }

        $parts = [];

        foreach ($value as $key => $part) {
            if ($key === 'text' && is_string($part)) {
                $parts[] = $part;
                continue;
            }

            if (is_array($part)) {
                $text = $schemaRichTextToString($part);

                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return implode(' ', $parts);
    };
    $schemaDescriptionValue = $product['full_description'] ?? null;
    $schemaDescription = trim(strip_tags($schemaRichTextToString($schemaDescriptionValue)));

    if ($schemaDescription === '') {
        $schemaDescription = trim(strip_tags($schemaRichTextToString($product['description'] ?? null)));
    }

    $schemaProductDescription = $schemaDescription;
    $schemaProductAttributes = collect($product['attributes'] ?? [])
        ->filter(fn ($attribute): bool => is_array($attribute) && filled($attribute['name'] ?? null) && filled($attribute['value'] ?? null))
        ->map(fn (array $attribute): array => [
            '@'.'type' => 'PropertyValue',
            'name' => (string) $attribute['name'],
            'value' => (string) $attribute['value'],
        ])
        ->values()
        ->all();
    $schemaPrice = (float) convert_price((float) ($product['price'] ?? 0));
    $schemaReviews = collect($product['reviews'] ?? [])
        ->filter(fn ($review): bool => is_array($review))
        ->values();
    $schemaProduct = array_filter([
        '@'.'context' => 'https://schema.org',
        '@'.'type' => 'Product',
        'name' => $schemaProductName !== '' ? $schemaProductName : null,
        'sku' => filled($product['sku'] ?? null) ? (string) $product['sku'] : null,
        'image' => $schemaProductImages !== [] ? $schemaProductImages : null,
        'description' => $schemaProductDescription !== '' ? $schemaProductDescription : null,
        'brand' => filled($product['brand'] ?? null) ? ['@'.'type' => 'Brand', 'name' => $product['brand']] : null,
        'category' => filled($product['categories'][0]['name'] ?? null) ? $product['categories'][0]['name'] : null,
        'additionalProperty' => $schemaProductAttributes !== [] ? $schemaProductAttributes : null,
        'offers' => $schemaPrice > 0 ? [
            '@'.'type' => 'Offer',
            'url' => $schemaProductUrl,
            'priceCurrency' => current_currency()?->code ?? 'UAH',
            'price' => rtrim(rtrim(number_format($schemaPrice, 2, '.', ''), '0'), '.'),
            'availability' => ($product['stock_status'] ?? 'in_stock') === 'in_stock'
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@'.'id' => $schemaProductBaseUrl.'/#organization'],
        ] : null,
        'aggregateRating' => $schemaReviews->isNotEmpty() ? [
            '@'.'type' => 'AggregateRating',
            'ratingValue' => (string) round((float) $schemaReviews->avg('rating'), 1),
            'reviewCount' => $schemaReviews->count(),
        ] : null,
        'review' => $schemaReviews->isNotEmpty() ? $schemaReviews->map(fn (array $review): array => array_filter([
            '@'.'type' => 'Review',
            'author' => filled($review['display_name'] ?? null) ? ['@'.'type' => 'Person', 'name' => $review['display_name']] : null,
            'reviewRating' => filled($review['rating'] ?? null) ? [
                '@'.'type' => 'Rating',
                'ratingValue' => (string) $review['rating'],
                'bestRating' => '5',
            ] : null,
            'reviewBody' => filled($review['comment'] ?? null) ? strip_tags((string) $review['comment']) : null,
        ], fn ($value): bool => $value !== null))->values()->all() : null,
    ], fn ($value): bool => $value !== null && $value !== []);
@endphp

<script type="application/ld+json">
{!! json_encode($schemaProduct, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

@php
    $schemaProductBreadcrumbs = collect([
        ['name' => __('Home'), 'item' => $homeUrl ?? \Commero\Support\Locales::path('/', app()->getLocale())],
        ['name' => __('Catalog'), 'item' => $catalogUrl ?? \Commero\Support\Locales::path('/catalog', app()->getLocale())],
    ]);

    if (! empty($primaryCategory)) {
        $schemaProductBreadcrumbs->push([
            'name' => $primaryCategory['name'],
            'item' => $primaryCategoryUrl ?? null,
        ]);
    }

    $schemaProductBreadcrumbs->push(['name' => $schemaProductName]);
@endphp

<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@'.'type' => 'BreadcrumbList',
    'itemListElement' => $schemaProductBreadcrumbs->map(fn (array $item, int $index): array => array_filter([
        '@'.'type' => 'ListItem',
        'position' => $index + 1,
        'name' => $item['name'],
        'item' => $toAbsoluteSchemaUrl($item['item'] ?? null),
    ], fn ($value): bool => $value !== null))->values()->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
