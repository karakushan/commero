@props([
    'resolvedSeo' => null,
    'siteSetting' => null,
    'structuredData' => [],
])

@php
    $resolvedSeo = is_array($resolvedSeo) ? $resolvedSeo : [
        'html_lang' => str_replace('_', '-', app()->getLocale()),
    ];
    $structuredData = is_array($structuredData) ? $structuredData : [];
    $schemaProductContext = is_array($structuredData['product'] ?? null)
        ? $structuredData['product']
        : null;
    $schemaCatalogContext = is_array($structuredData['catalog'] ?? null)
        ? $structuredData['catalog']
        : null;
@endphp

@include('commero::partials.structured-data', [
    'resolvedSeo' => $resolvedSeo,
    'siteSetting' => $siteSetting,
])

@if ($schemaProductContext)
    @php
        $schemaProductData = is_array($schemaProductContext['product'] ?? null)
            ? $schemaProductContext['product']
            : [];
        $schemaProductLocale = \Commero\Support\Locales::resolve($schemaProductContext['locale'] ?? app()->getLocale());
        $schemaProductSeo = is_array($schemaProductContext['seo'] ?? null)
            ? $schemaProductContext['seo']
            : [];
        $schemaPrimaryCategory = collect($schemaProductData['categories'] ?? [])
            ->filter(fn ($category): bool => is_array($category) && filled($category['name'] ?? null))
            ->first();
        $schemaPrimaryCategoryUrl = $schemaPrimaryCategory && filled($schemaPrimaryCategory['slug'] ?? null)
            ? \Commero\Support\Locales::path('/'.$schemaPrimaryCategory['slug'], $schemaProductLocale)
            : null;
    @endphp

    @if ($schemaProductData !== [])
        @include('commero::partials.product-structured-data', [
            'product' => $schemaProductData,
            'locale' => $schemaProductLocale,
            'canonical' => $schemaProductSeo['canonical'] ?? url()->current(),
            'homeUrl' => \Commero\Support\Locales::path('/', $schemaProductLocale),
            'catalogUrl' => \Commero\Support\Locales::path('/catalog', $schemaProductLocale),
            'primaryCategory' => $schemaPrimaryCategory,
            'primaryCategoryUrl' => $schemaPrimaryCategoryUrl,
        ])
    @endif
@endif

@if ($schemaCatalogContext)
    @include('commero::partials.catalog-structured-data', $schemaCatalogContext)
@endif
