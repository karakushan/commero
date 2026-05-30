<?php

namespace Commero\Support;

use Commero\Models\Menu;
use Commero\Models\MenuItem;
use Illuminate\Support\Collection;

class MenuManager
{
    public function find(string $identifier, ?string $locale = null): ?Menu
    {
        $resolvedLocale = Locales::resolve($locale);

        return Menu::query()
            ->where('identifier', $identifier)
            ->where('is_active', true)
            ->with([
                'items' => fn ($query) => $query
                    ->where('is_active', true)
                    ->withTranslationsFor($resolvedLocale)
                    ->orderBy('sort'),
            ])
            ->first();
    }

    public function items(string $identifier, ?string $locale = null): Collection
    {
        $resolvedLocale = Locales::resolve($locale);
        $menu = $this->find($identifier, $resolvedLocale);

        if (! $menu) {
            return collect();
        }

        return $menu->items
            ->map(function (MenuItem $item) use ($resolvedLocale): ?array {
                $translation = $item->translation($resolvedLocale);

                if (! $translation?->label || ! $translation->url) {
                    return null;
                }

                return [
                    'label' => $translation->label,
                    'url' => $translation->url,
                    'target' => $item->open_in_new_tab ? '_blank' : '_self',
                    'rel' => $item->open_in_new_tab ? 'noopener noreferrer' : null,
                ];
            })
            ->filter()
            ->values();
    }
}
