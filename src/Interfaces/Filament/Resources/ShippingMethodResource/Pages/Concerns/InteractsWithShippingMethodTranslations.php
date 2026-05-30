<?php

namespace Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages\Concerns;

use Commero\Models\ShippingMethod;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithShippingMethodTranslations
{
    protected const ACTIVE_LOCALE_FIELD = 'active_locale_context';

    public string $activeLocale = '';

    protected function initializeActiveLocale(): void
    {
        $this->activeLocale = $this->resolveActiveLocale();
    }

    public function updatedActiveLocale(?string $locale): void
    {
        $this->activeLocale = in_array($locale, AdminLocales::supported(), true)
            ? (string) $locale
            : Locales::default();

        data_set($this, 'data.'.static::ACTIVE_LOCALE_FIELD, $this->activeLocale);
    }

    protected function prepareShippingMethodData(array $data): array
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);

        return $this->finalizePreparedShippingMethodData($data, $translations);
    }

    protected function prepareShippingMethodDataForActiveLocale(array $data, ?ShippingMethod $shippingMethod = null): array
    {
        if (! $shippingMethod) {
            return $this->prepareShippingMethodData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($shippingMethod->loadMissing('translations'));

        foreach (AdminLocales::supported() as $locale) {
            if ($locale === $activeLocale) {
                continue;
            }

            if ($this->hasMeaningfulTranslationData($existingTranslations[$locale] ?? [])) {
                $translations[$locale] = $this->normalizeTranslations([
                    $locale => $existingTranslations[$locale],
                ])[$locale];

                continue;
            }

            unset($translations[$locale]);
        }

        return $this->finalizePreparedShippingMethodData($data, $translations);
    }

    protected function normalizeTranslations(array $translations): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations): array {
                $state = Arr::only((array) ($translations[$locale] ?? []), ['name', 'description']);

                return [
                    $locale => [
                        'locale' => $locale,
                        'name' => $this->normalizeTextValue($state['name'] ?? null, false),
                        'description' => $this->normalizeTextValue($state['description'] ?? null),
                    ],
                ];
            })
            ->all();
    }

    protected function getTranslationsFormState(?ShippingMethod $shippingMethod = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $shippingMethod) {
            return $state;
        }

        foreach ($shippingMethod->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), ['name', 'description']),
            );
        }

        $defaultLocale = Locales::default();

        if (blank($state[$defaultLocale]['name'] ?? null) && filled($shippingMethod->getRawOriginal('name'))) {
            $state[$defaultLocale]['name'] = $shippingMethod->getRawOriginal('name');
        }

        if (blank($state[$defaultLocale]['description'] ?? null) && filled($shippingMethod->getRawOriginal('description'))) {
            $state[$defaultLocale]['description'] = $shippingMethod->getRawOriginal('description');
        }

        return $state;
    }

    protected function getActiveLocaleContextState(): array
    {
        return [
            static::ACTIVE_LOCALE_FIELD => $this->resolveActiveLocale(),
        ];
    }

    protected function finalizePreparedShippingMethodData(array $data, array $translations): array
    {
        $this->guardDefaultLocaleTranslation($translations);

        $defaultLocale = Locales::default();
        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['name'] = (string) ($translations[$defaultLocale]['name'] ?? '');
        $data['description'] = $translations[$defaultLocale]['description'] ?? null;
        $data['translations'] = $translations;

        return $data;
    }

    protected function syncTranslations(ShippingMethod $shippingMethod, array $translations): void
    {
        $existingTranslations = $shippingMethod->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $shippingMethod->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => (string) ($translation['name'] ?? ''),
                    'description' => $translation['description'] ?? null,
                ],
            );
        }
    }

    protected function guardDefaultLocaleTranslation(array $translations): void
    {
        $defaultLocale = Locales::default();
        $defaultTranslation = $translations[$defaultLocale] ?? [];

        if (blank($defaultTranslation['name'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.name" => __('admin.resources.shipping_method.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

    protected function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['name'] ?? null) || filled($translation['description'] ?? null);
    }

    protected function resolveActiveLocale(?string $locale = null): string
    {
        $locale = $locale
            ?? data_get($this, 'data.'.static::ACTIVE_LOCALE_FIELD)
            ?? request()->query('lang')
            ?? $this->activeLocale;

        if (! in_array($locale, AdminLocales::supported(), true)) {
            $locale = Locales::default();
        }

        return $locale;
    }

    protected function normalizeTextValue(mixed $value, bool $nullable = true): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return $nullable ? null : '';
        }

        return $value;
    }

    private function emptyTranslation(): array
    {
        return [
            'name' => null,
            'description' => null,
        ];
    }
}
