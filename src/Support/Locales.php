<?php

namespace Commero\Support;

class Locales
{
    public static function supported(): array
    {
        return config('app.supported_locales', [config('app.locale')]);
    }

    public static function fallback(): string
    {
        return config('app.fallback_locale', 'en');
    }

    public static function default(): string
    {
        return self::supported()[0] ?? config('app.locale', 'uk');
    }

    public static function additional(): array
    {
        return array_values(array_filter(
            self::supported(),
            fn (string $locale): bool => ! self::isDefault($locale),
        ));
    }

    public static function isDefault(?string $locale): bool
    {
        return $locale === self::default();
    }

    public static function resolve(?string $locale = null): string
    {
        if (is_string($locale) && in_array($locale, self::supported(), true)) {
            return $locale;
        }

        return self::default();
    }

    public static function path(string $path = '/', ?string $locale = null): string
    {
        $normalizedPath = '/'.ltrim($path, '/');
        $resolvedLocale = self::resolve($locale);
        $suffix = $normalizedPath === '/' ? '' : $normalizedPath;

        if (self::isDefault($resolvedLocale)) {
            return $normalizedPath;
        }

        return '/'.$resolvedLocale.$suffix;
    }

    public static function ensureTrailingSlash(string $path): string
    {
        if ($path === '/') {
            return $path;
        }

        preg_match('/^([^?#]*)(.*)$/', $path, $matches);
        $basePath = $matches[1] ?? $path;
        $suffix = $matches[2] ?? '';

        return rtrim($basePath, '/').'/'.$suffix;
    }

    public static function preferred(string $locale): array
    {
        $locales = array_values(array_unique([$locale, self::fallback()]));

        return array_values(array_filter($locales));
    }
}
