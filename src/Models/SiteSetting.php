<?php

namespace Commero\Models;

use Commero\Support\Locales;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'site_name_translations',
        'logo_path',
        'logo_path_translations',
        'footer_logo_path',
        'footer_logo_path_translations',
        'favicon_svg_path',
        'favicon_png_path',
        'nova_poshta_api_key',
        'google_maps_api_key',
        'contacts',
        'contacts_translations',
        'addresses',
        'social_links',
        'social_links_translations',
        'multi_currency_enabled',
        'country_source',
        'show_price_decimals',
    ];

    protected $casts = [
        'nova_poshta_api_key' => 'encrypted',
        'google_maps_api_key' => 'encrypted',
        'contacts' => 'array',
        'contacts_translations' => 'array',
        'addresses' => 'array',
        'logo_path_translations' => 'array',
        'footer_logo_path_translations' => 'array',
        'social_links' => 'array',
        'social_links_translations' => 'array',
        'site_name_translations' => 'array',
        'multi_currency_enabled' => 'boolean',
        'show_price_decimals' => 'boolean',
    ];

    public function getSiteNameAttribute(mixed $value): ?string
    {
        return $this->getSiteNameForLocale(app()->getLocale());
    }

    public function getContactsAttribute(mixed $value): array
    {
        return $this->getContactsForLocale(app()->getLocale());
    }

    public function getLogoPathAttribute(mixed $value): ?string
    {
        return $this->getLogoPathForLocale(app()->getLocale());
    }

    public function getSocialLinksAttribute(mixed $value): array
    {
        return $this->getSocialLinksForLocale(app()->getLocale());
    }

    public function getAddressesAttribute(mixed $value): array
    {
        return $this->getAddresses();
    }

    public function getFooterLogoPathAttribute(mixed $value): ?string
    {
        return $this->getFooterLogoPathForLocale(app()->getLocale());
    }

    public function isMultiCurrencyEnabled(): bool
    {
        return (bool) $this->getRawOriginal('multi_currency_enabled');
    }

    public function getCountrySource(): ?string
    {
        return $this->country_source;
    }

    public function shouldDisplayPriceDecimals(): bool
    {
        return (bool) $this->getRawOriginal('show_price_decimals');
    }

    public function getSiteNameForLocale(?string $locale = null, bool $useFallback = true): ?string
    {
        $resolvedLocale = Locales::resolve($locale);
        $defaultValue = $this->normalizeTextValue($this->getRawOriginal('site_name'), false);

        if (Locales::isDefault($resolvedLocale)) {
            return $defaultValue;
        }

        $translations = $this->getRawJsonAttribute('site_name_translations');
        $translatedValue = $this->normalizeTextValue($translations[$resolvedLocale] ?? null, false);

        return $translatedValue ?? ($useFallback ? $defaultValue : null);
    }

    public function getLogoPathForLocale(?string $locale = null, bool $useFallback = true): ?string
    {
        $resolvedLocale = Locales::resolve($locale);
        $defaultValue = $this->normalizeTextValue($this->getRawOriginal('logo_path'), false);

        if (Locales::isDefault($resolvedLocale)) {
            return $defaultValue;
        }

        $translations = $this->getRawJsonAttribute('logo_path_translations');
        $translatedValue = $this->normalizeTextValue($translations[$resolvedLocale] ?? null, false);

        return $translatedValue ?? ($useFallback ? $defaultValue : null);
    }

    public function getFooterLogoPathForLocale(?string $locale = null, bool $useFallback = true): ?string
    {
        $resolvedLocale = Locales::resolve($locale);
        $defaultValue = $this->normalizeTextValue($this->getRawOriginal('footer_logo_path'), false);

        if (Locales::isDefault($resolvedLocale)) {
            return $defaultValue;
        }

        $translations = $this->getRawJsonAttribute('footer_logo_path_translations');
        $translatedValue = $this->normalizeTextValue($translations[$resolvedLocale] ?? null, false);

        return $translatedValue ?? ($useFallback ? $defaultValue : null);
    }

    public function getContactsForLocale(?string $locale = null, bool $useFallback = true): array
    {
        return $this->getLocalizedItemsForLocale(
            locale: $locale,
            defaultItems: $this->normalizeContacts($this->getRawJsonAttribute('contacts')),
            translations: $this->getRawJsonAttribute('contacts_translations'),
            translatableFields: ['label', 'value'],
            useFallback: $useFallback,
        );
    }

    public function getSocialLinksForLocale(?string $locale = null, bool $useFallback = true): array
    {
        return $this->getLocalizedItemsForLocale(
            locale: $locale,
            defaultItems: $this->normalizeSocialLinks($this->getRawJsonAttribute('social_links')),
            translations: $this->getRawJsonAttribute('social_links_translations'),
            translatableFields: ['label', 'url'],
            useFallback: $useFallback,
        );
    }

    public function getEditableContactsForLocale(?string $locale = null): array
    {
        return $this->getEditableLocalizedItemsForLocale(
            locale: $locale,
            defaultItems: $this->normalizeContacts($this->getRawJsonAttribute('contacts')),
            translations: $this->getRawJsonAttribute('contacts_translations'),
            translatableFields: ['label', 'value'],
        );
    }

    public function getEditableSocialLinksForLocale(?string $locale = null): array
    {
        return $this->getEditableLocalizedItemsForLocale(
            locale: $locale,
            defaultItems: $this->normalizeSocialLinks($this->getRawJsonAttribute('social_links')),
            translations: $this->getRawJsonAttribute('social_links_translations'),
            translatableFields: ['label', 'url'],
        );
    }

    public function getAddresses(): array
    {
        return $this->normalizeAddresses($this->getRawJsonAttribute('addresses'));
    }

    public function getEditableAddresses(): array
    {
        return $this->getAddresses();
    }

    public function getAddressesForLocale(?string $locale = null, bool $useFallback = true): array
    {
        $resolvedLocale = Locales::resolve($locale);
        $addresses = collect($this->getAddresses());

        $localized = $addresses
            ->filter(function (array $address) use ($resolvedLocale): bool {
                $addressLocale = $this->normalizeTextValue($address['locale'] ?? null, false);

                return $addressLocale === $resolvedLocale;
            })
            ->values()
            ->all();

        if ($localized !== []) {
            return $localized;
        }

        return $useFallback ? $addresses->values()->all() : [];
    }

    public function getPrimaryAddress(?string $locale = null, bool $useFallback = true): ?array
    {
        return collect($this->getAddressesForLocale($locale, $useFallback))
            ->first(fn (array $address): bool => filled($address['address'] ?? null) || filled($address['coordinates'] ?? null));
    }

    protected function getLocalizedItemsForLocale(
        ?string $locale,
        array $defaultItems,
        array $translations,
        array $translatableFields,
        bool $useFallback = true,
    ): array {
        $resolvedLocale = Locales::resolve($locale);

        if (Locales::isDefault($resolvedLocale)) {
            return array_values($defaultItems);
        }

        $localizedItems = $this->normalizeLocalizedItems($translations[$resolvedLocale] ?? [], $translatableFields);

        if (! $useFallback) {
            return array_values($localizedItems);
        }

        return $this->mergeLocalizedItemsWithFallback($defaultItems, $localizedItems, $translatableFields);
    }

    protected function getEditableLocalizedItemsForLocale(
        ?string $locale,
        array $defaultItems,
        array $translations,
        array $translatableFields,
    ): array {
        $resolvedLocale = Locales::resolve($locale);

        if (Locales::isDefault($resolvedLocale)) {
            return array_values($defaultItems);
        }

        $localizedItems = $this->normalizeLocalizedItems($translations[$resolvedLocale] ?? [], $translatableFields);
        $defaultItemsByIdentifier = collect($defaultItems)->keyBy('identifier');
        $localizedItemsByIdentifier = collect($localizedItems)->keyBy('identifier');
        $editableItems = [];

        foreach ($defaultItemsByIdentifier as $identifier => $defaultItem) {
            $localizedItem = $localizedItemsByIdentifier->get($identifier, []);
            $item = $defaultItem;

            foreach ($translatableFields as $field) {
                $item[$field] = $this->normalizeTextValue($localizedItem[$field] ?? null);
            }

            if (array_key_exists('icon', $item)) {
                $item['icon'] = $localizedItem['icon'] ?? $defaultItem['icon'] ?? null;
            }

            $editableItems[] = $item;
        }

        foreach ($localizedItemsByIdentifier as $identifier => $localizedItem) {
            if ($defaultItemsByIdentifier->has($identifier)) {
                continue;
            }

            $editableItems[] = $localizedItem;
        }

        return array_values($editableItems);
    }

    protected function mergeLocalizedItemsWithFallback(array $defaultItems, array $localizedItems, array $translatableFields): array
    {
        $defaultItemsByIdentifier = collect($defaultItems)->keyBy('identifier');
        $localizedItemsByIdentifier = collect($localizedItems)->keyBy('identifier');
        $mergedItems = [];

        foreach ($defaultItemsByIdentifier as $identifier => $defaultItem) {
            $localizedItem = $localizedItemsByIdentifier->get($identifier, []);
            $item = $defaultItem;

            foreach ($translatableFields as $field) {
                $item[$field] = $this->normalizeTextValue($localizedItem[$field] ?? null)
                    ?? $defaultItem[$field]
                    ?? null;
            }

            if (array_key_exists('icon', $item)) {
                $item['icon'] = $localizedItem['icon'] ?? $defaultItem['icon'] ?? null;
            }

            $mergedItems[] = $item;
        }

        foreach ($localizedItemsByIdentifier as $identifier => $localizedItem) {
            if ($defaultItemsByIdentifier->has($identifier)) {
                continue;
            }

            $mergedItems[] = $localizedItem;
        }

        return array_values($mergedItems);
    }

    protected function normalizeContacts(array $items): array
    {
        return $this->normalizeLocalizedItems($items, ['label', 'value']);
    }

    protected function normalizeSocialLinks(array $items): array
    {
        return $this->normalizeLocalizedItems($items, ['label', 'url']);
    }

    protected function normalizeAddresses(array $items): array
    {
        return collect($items)
            ->map(function (mixed $item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = $this->normalizeTextValue($item['label'] ?? null);
                $address = $this->normalizeTextValue($item['address'] ?? null);
                $coordinates = $this->normalizeTextValue($item['coordinates'] ?? null);
                $locale = Locales::resolve($this->normalizeTextValue($item['locale'] ?? null, false));

                if ($label === null && $address === null && $coordinates === null) {
                    return null;
                }

                return [
                    'label' => $label,
                    'address' => $address,
                    'coordinates' => $coordinates,
                    'locale' => $locale,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeLocalizedItems(array $items, array $translatableFields): array
    {
        return collect($items)
            ->map(function (mixed $item) use ($translatableFields): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $identifier = $this->normalizeTextValue($item['identifier'] ?? null, false);

                if ($identifier === null) {
                    return null;
                }

                $normalized = [
                    'identifier' => $identifier,
                    'icon' => $this->normalizeTextValue($item['icon'] ?? null),
                ];

                foreach ($translatableFields as $field) {
                    $normalized[$field] = $this->normalizeTextValue($item[$field] ?? null);
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function getRawJsonAttribute(string $key): array
    {
        $value = $this->getRawOriginal($key);

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function normalizeTextValue(mixed $value, bool $nullable = true): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return $nullable ? null : null;
        }

        return $value;
    }
}
