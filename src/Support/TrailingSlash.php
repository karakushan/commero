<?php

namespace Commero\Support;

class TrailingSlash
{
    /**
     * Paths managed by external packages or infra should keep their native shape.
     *
     * @var list<string>
     */
    private const EXCLUDED_PREFIXES = [
        'admin',
        'livewire',
        'up',
    ];

    public static function normalize(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        if (self::shouldSkip($path)) {
            return $path;
        }

        return rtrim($path, '/').'/';
    }

    public static function shouldSkip(string $path): bool
    {
        $trimmedPath = trim($path, '/');

        if ($trimmedPath === '') {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if ($trimmedPath === $prefix || str_starts_with($trimmedPath, $prefix.'/')) {
                return true;
            }
        }

        return pathinfo($trimmedPath, PATHINFO_EXTENSION) !== '';
    }
}
