<?php

namespace Commero\Application\Catalog\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class LocaleCache
{
    public function remember(string $segment, string $locale, string $suffix, Closure $callback, int $seconds = 3600): mixed
    {
        $version = Cache::get($this->versionKey($segment), 1);

        return Cache::remember($this->itemKey($segment, $locale, $suffix, $version), $seconds, $callback);
    }

    public function invalidate(string $segment): void
    {
        $key = $this->versionKey($segment);

        if (! Cache::has($key)) {
            Cache::forever($key, 2);

            return;
        }

        Cache::increment($key);
    }

    private function versionKey(string $segment): string
    {
        return "catalog-cache-version:{$segment}";
    }

    private function itemKey(string $segment, string $locale, string $suffix, int $version): string
    {
        return "catalog:{$segment}:{$locale}:v{$version}:{$suffix}";
    }
}
