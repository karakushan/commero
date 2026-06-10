<?php

namespace Commero\Support\Filament;

use Commero\Support\Locales;

class AdminLocales
{
    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return Locales::supported();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(static::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => static::optionLabel($locale)])
            ->all();
    }

    public static function optionLabel(string $locale): string
    {
        return trim(static::flag($locale).' '.__('commero::admin.locale_names.'.$locale));
    }

    public static function flag(string $locale): string
    {
        return match ($locale) {
            'uk' => '🇺🇦',
            'en' => '🇬🇧',
            'ru' => '🇷🇺',
            'es' => '🇪🇸',
            'pl' => '🇵🇱',
            default => '🏳️',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyTranslation(): array
    {
        return [
            'title' => null,
            'slug' => null,
            'excerpt' => null,
            'background_desktop_color' => null,
            'background_mobile_color' => null,
            'show_breadcrumbs' => true,
            'show_title' => true,
            'meta_title' => null,
            'meta_description' => null,
            'robots' => null,
            'blocks' => [],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaultTranslationsState(): array
    {
        return collect(static::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => static::emptyTranslation()])
            ->all();
    }
}
