<?php

namespace Commero\Support\Filament;

use Commero\Models\Category;
use Filament\Forms\Components\TextInput;

class PageContentBlocks
{
    protected static function labelWithIdentifier(string $identifier, string $label): string
    {
        return sprintf('%s (%s)', $label, $identifier);
    }

    protected static function dynamicBlockLabel(
        string $identifier,
        string $fallbackLabel,
        ?array $state,
        string $stateKey = 'title',
    ): string {
        $label = filled($state[$stateKey] ?? null) ? (string) $state[$stateKey] : $fallbackLabel;

        return static::labelWithIdentifier($identifier, $label);
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function spacingFields(
        string $prefix,
        string $mobileTop = '24',
        string $mobileBottom = '0',
        string $desktopTop = '0',
        string $desktopBottom = '0',
    ): array {
        return [
            TextInput::make('marginTopMobile')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_top_mobile'))
                ->numeric()
                ->default($mobileTop),
            TextInput::make('marginBottomMobile')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_bottom_mobile'))
                ->numeric()
                ->default($mobileBottom),
            TextInput::make('marginTopDesktop')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_top_desktop'))
                ->numeric()
                ->default($desktopTop),
            TextInput::make('marginBottomDesktop')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_bottom_desktop'))
                ->numeric()
                ->default($desktopBottom),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function deliveryInfoIcons(): array
    {
        return [
            'branch' => __('admin.content.blocks.delivery_info_section.icons.branch'),
            'parcel-locker' => __('admin.content.blocks.delivery_info_section.icons.parcel_locker'),
            'courier' => __('admin.content.blocks.delivery_info_section.icons.courier'),
            'pickup' => __('admin.content.blocks.delivery_info_section.icons.pickup'),
            'transport' => __('admin.content.blocks.delivery_info_section.icons.transport'),
            'card' => __('admin.content.blocks.delivery_info_section.icons.card'),
            'cash' => __('admin.content.blocks.delivery_info_section.icons.cash'),
            'cod' => __('admin.content.blocks.delivery_info_section.icons.cod'),
            'bank' => __('admin.content.blocks.delivery_info_section.icons.bank'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function homeShortcutIcons(): array
    {
        return [
            'men' => __('admin.content.blocks.home_top_section.icons.men'),
            'women' => __('admin.content.blocks.home_top_section.icons.women'),
            'children' => __('admin.content.blocks.home_top_section.icons.children'),
            'promo' => __('admin.content.blocks.home_top_section.icons.promo'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected static function categoryOptions(): array
    {
        $defaultLocale = \Commero\Support\Locales::default();
        $currentLocale = app()->getLocale();

        return Category::query()
            ->with('translations')
            ->orderBy('sort')
            ->orderBy('path')
            ->get()
            ->mapWithKeys(function (Category $category) use ($currentLocale, $defaultLocale): array {
                $name = $category->exactTranslation($currentLocale)?->name
                    ?? $category->exactTranslation($defaultLocale)?->name
                    ?? $category->translations->first()?->name
                    ?? $category->path;

                return [$category->id => $name];
            })
            ->all();
    }
}
