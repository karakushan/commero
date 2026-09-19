<?php

namespace Commero\Support\Seo;

use Commero\Support\Locales;
use Illuminate\Http\Request;

final class CatalogPagination
{
    public static function apply(array $seo, Request $request, int $page): array
    {
        $seo['canonical'] = self::forUrl((string) ($seo['canonical'] ?? $request->url()), $page);
        $seo['alternates'] = collect($seo['alternates'] ?? [])
            ->map(function (array $alternate) use ($page): array {
                return [
                    ...$alternate,
                    'locale' => self::hreflang((string) ($alternate['locale'] ?? '')),
                    'href' => self::forUrl((string) $alternate['href'], $page),
                ];
            })
            ->all();

        if (filled($seo['x_default'] ?? null)) {
            $seo['x_default'] = self::forUrl((string) $seo['x_default'], $page);
        }

        if ($page <= 1) {
            return $seo;
        }

        $brand = trim((string) config('app.name', 'ShopHats'));
        $baseTitle = self::withoutBrand((string) ($seo['title'] ?? ''), $brand);

        $seo['title'] = trim($baseTitle.' '.__('Pagination page title suffix', ['page' => $page])
            .' | '.$brand);
        $seo['heading'] = trim((string) ($seo['heading'] ?? '').' '
            .__('Pagination page title suffix', ['page' => $page]));

        if (filled($seo['description'] ?? null)) {
            $description = rtrim((string) $seo['description']);
            $seo['description'] = trim($description.' '.__('Pagination page description suffix', ['page' => $page]));
        }

        return $seo;
    }

    public static function forRequest(Request $request, int $page): string
    {
        $query = $request->query();
        unset($query['page']);

        if ($page > 1) {
            $query['page'] = $page;
        }

        return self::buildUrl($request->url(), $query);
    }

    public static function forUrl(string $url, int $page): string
    {
        $parts = parse_url($url);
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        unset($query['page']);

        if ($page > 1) {
            $query['page'] = $page;
        }

        $baseUrl = $url;

        if ($parts !== false && isset($parts['scheme'], $parts['host'])) {
            $baseUrl = $parts['scheme'].'://'.$parts['host']
                .(isset($parts['port']) ? ':'.$parts['port'] : '')
                .($parts['path'] ?? '');
        } elseif ($parts !== false && isset($parts['path'])) {
            $baseUrl = $parts['path'];
        }

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            $baseUrl = url($baseUrl);
        }

        return self::buildUrl($baseUrl, $query);
    }

    public static function hreflang(string $locale): string
    {
        $locale = str_replace('_', '-', strtolower(trim($locale)));

        if ($locale === '' || str_contains($locale, '-')) {
            return $locale;
        }

        return $locale.'-UA';
    }

    private static function buildUrl(string $url, array $query): string
    {
        $url = Locales::ensureTrailingSlash($url);

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private static function withoutBrand(string $title, string $brand): string
    {
        if ($brand === '') {
            return trim($title);
        }

        return trim((string) preg_replace(
            '/\\s*[|–—-]\\s*'.preg_quote($brand, '/').'\\s*$/u',
            '',
            trim($title),
        ));
    }
}
