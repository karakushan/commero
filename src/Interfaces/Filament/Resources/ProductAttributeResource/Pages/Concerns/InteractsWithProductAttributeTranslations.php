<?php

namespace Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages\Concerns;

use Commero\Models\AttributeOption;
use Commero\Models\ProductAttribute;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithProductAttributeTranslations
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareAttributeData(array $data): array
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $options = $this->normalizeOptions($data['options'] ?? []);

        return $this->finalizePreparedAttributeData($data, $translations, $options);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareAttributeDataForActiveLocale(array $data, ?ProductAttribute $attribute = null): array
    {
        if (! $attribute) {
            return $this->prepareAttributeData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($attribute->loadMissing('translations'));
        $options = $this->normalizeOptions($data['options'] ?? []);
        $existingOptions = collect($this->getOptionsFormState($attribute->loadMissing('options.translations')))
            ->filter(fn (array $option): bool => filled($option['id'] ?? null))
            ->keyBy(fn (array $option): string => (string) $option['id']);

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

        foreach ($options as $index => $option) {
            $existingOption = filled($option['id'] ?? null)
                ? $existingOptions->get((string) $option['id'])
                : null;

            if (! $existingOption) {
                continue;
            }

            foreach (AdminLocales::supported() as $locale) {
                if ($locale === $activeLocale) {
                    continue;
                }

                if ($this->hasMeaningfulOptionTranslationData($existingOption['translations'][$locale] ?? [])) {
                    $options[$index]['translations'][$locale] = $this->normalizeOptionTranslations([
                        $locale => $existingOption['translations'][$locale],
                    ])[$locale];

                    continue;
                }

                unset($options[$index]['translations'][$locale]);
            }
        }

        return $this->finalizePreparedAttributeData($data, $translations, $options);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeTranslations(array $translations): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations): array {
                $state = Arr::only((array) ($translations[$locale] ?? []), ['name']);

                return [
                    $locale => [
                        'locale' => $locale,
                        'name' => $this->normalizeTextValue($state['name'] ?? null, false),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeOptions(array $options): array
    {
        return collect($options)
            ->filter(fn (mixed $option): bool => is_array($option))
            ->values()
            ->map(function (array $option): array {
                return [
                    'id' => filled($option['id'] ?? null) ? (int) $option['id'] : null,
                    'value' => (string) $this->normalizeTextValue($option['value'] ?? null, false),
                    'sort' => is_numeric($option['sort'] ?? null) ? (int) $option['sort'] : 0,
                    'translations' => $this->normalizeOptionTranslations($option['translations'] ?? []),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeOptionTranslations(array $translations): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations): array {
                $state = Arr::only((array) ($translations[$locale] ?? []), ['label']);

                return [
                    $locale => [
                        'locale' => $locale,
                        'label' => $this->normalizeTextValue($state['label'] ?? null, false),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getTranslationsFormState(?ProductAttribute $attribute = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $attribute) {
            return $state;
        }

        foreach ($attribute->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), ['name']),
            );
        }

        return $state;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getOptionsFormState(?ProductAttribute $attribute = null): array
    {
        if (! $attribute) {
            return [];
        }

        return $attribute->options
            ->sortBy([
                ['sort', 'asc'],
                ['id', 'asc'],
            ])
            ->map(function (AttributeOption $option): array {
                $translations = collect(AdminLocales::supported())
                    ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyOptionTranslation()])
                    ->all();

                foreach ($option->translations as $translation) {
                    $translations[$translation->locale] = array_merge(
                        $this->emptyOptionTranslation(),
                        Arr::only($translation->toArray(), ['label']),
                    );
                }

                return [
                    'id' => $option->getKey(),
                    'value' => $option->value,
                    'sort' => $option->sort,
                    'translations' => $translations,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getActiveLocaleContextState(): array
    {
        return [
            static::ACTIVE_LOCALE_FIELD => $this->resolveActiveLocale(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $translations
     * @param  array<int, array<string, mixed>>  $options
     * @return array<string, mixed>
     */
    protected function finalizePreparedAttributeData(array $data, array $translations, array $options): array
    {
        $this->guardDefaultLocaleTranslation($translations);

        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = $translations;
        $data['options'] = $options;

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(ProductAttribute $attribute, array $translations): void
    {
        $existingTranslations = $attribute->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $attribute->translations()->updateOrCreate(
                ['locale' => $locale],
                ['name' => (string) ($translation['name'] ?? '')],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    protected function syncOptions(ProductAttribute $attribute, array $options): void
    {
        $existingOptions = $attribute->options()->with('translations')->get()->keyBy('id');
        $keptOptionIds = [];

        foreach ($options as $optionData) {
            $option = filled($optionData['id'] ?? null)
                ? $existingOptions->get((int) $optionData['id'])
                : null;

            if ($option) {
                $option->update([
                    'value' => $optionData['value'],
                    'sort' => $optionData['sort'],
                ]);
            } else {
                $option = $attribute->options()->create([
                    'value' => $optionData['value'],
                    'sort' => $optionData['sort'],
                ]);
            }

            $keptOptionIds[] = $option->getKey();
            $this->syncOptionTranslations($option, $optionData['translations'] ?? []);
        }

        $attribute->options()
            ->whereNotIn('id', $keptOptionIds)
            ->get()
            ->each
            ->delete();
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncOptionTranslations(AttributeOption $option, array $translations): void
    {
        $existingTranslations = $option->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulOptionTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $option->translations()->updateOrCreate(
                ['locale' => $locale],
                ['label' => (string) ($translation['label'] ?? '')],
            );
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function guardDefaultLocaleTranslation(array $translations): void
    {
        $defaultLocale = Locales::default();
        $defaultTranslation = $translations[$defaultLocale] ?? [];

        if (blank($defaultTranslation['name'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.name" => __('admin.resources.product_attribute.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    protected function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['name'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    protected function hasMeaningfulOptionTranslationData(array $translation): bool
    {
        return filled($translation['label'] ?? null);
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

    /**
     * @return array<string, mixed>
     */
    private function emptyTranslation(): array
    {
        return [
            'name' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyOptionTranslation(): array
    {
        return [
            'label' => null,
        ];
    }
}
