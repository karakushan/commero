<?php

use Commero\Models\Category;
use Commero\Models\Currency;
use Commero\Models\Page;
use Commero\Support\EntityLinkService;
use Commero\Support\MenuManager;
use Illuminate\Support\Collection;

if (! function_exists('site_menu')) {
    function site_menu(string $identifier, ?string $locale = null): Collection
    {
        return app(MenuManager::class)->items($identifier, $locale);
    }
}

if (! function_exists('localized_count_label')) {
    function localized_count_label(string $key, int $count, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $language = strtok(str_replace('_', '-', $locale), '-');
        $category = 'other';

        if (in_array($language, ['uk', 'ru'], true)) {
            $mod10 = $count % 10;
            $mod100 = $count % 100;

            $category = match (true) {
                $mod10 === 1 && $mod100 !== 11 => 'one',
                $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => 'few',
                default => 'many',
            };
        } elseif ($count === 1) {
            $category = 'one';
        }

        return __($key.'.'.$category, [
            'count' => number_format($count, 0, '.', ' '),
        ], $locale);
    }
}

if (! function_exists('category_url')) {
    function category_url(Category|int|string $category, ?string $locale = null): ?string
    {
        if ($category instanceof Category) {
            return $category->frontendUrl($locale);
        }

        return app(EntityLinkService::class)->categoryUrl($category, $locale ?? app()->getLocale());
    }
}

if (! function_exists('page_url')) {
    function page_url(Page|int|string $page, ?string $locale = null): ?string
    {
        return app(EntityLinkService::class)->pageUrl($page, $locale ?? app()->getLocale());
    }
}

if (! function_exists('current_country')) {
    function current_country(): ?string
    {
        return app()->bound('current_country') ? app('current_country') : null;
    }
}

if (! function_exists('current_currency')) {
    function current_currency(): ?Currency
    {
        $country = current_country();

        if ($country === null) {
            return Currency::getBase();
        }

        return Currency::findByCountry($country) ?? Currency::getBase();
    }
}

if (! function_exists('convert_price')) {
    function convert_price(float $basePrice): float
    {
        $currency = current_currency();

        if ($currency === null || $currency->is_base) {
            return $basePrice;
        }

        return $currency->convertFromBase($basePrice);
    }
}
