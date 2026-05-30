<?php

namespace Commero\Interfaces\Filament\Resources\CategoryResource\Pages\Concerns;

use Commero\Models\Category;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithCategoryTranslations
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
    protected function prepareCategoryData(array $data): array
    {
        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = $this->normalizeTranslations($data['translations'] ?? []);

        $this->guardDefaultLocaleTranslation($data['translations']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareCategoryDataForActiveLocale(array $data, ?Category $category = null): array
    {
        if (! $category) {
            return $this->prepareCategoryData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($category->loadMissing('translations'));

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

        $data[static::ACTIVE_LOCALE_FIELD] = $activeLocale;
        $data['translations'] = $translations;

        $this->guardDefaultLocaleTranslation($translations);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeTranslations(array $translations): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations): array {
                $state = Arr::only((array) ($translations[$locale] ?? []), [
                    'name',
                    'slug',
                    'blocks',
                    'meta_title',
                    'meta_description',
                    'robots',
                ]);

                return [
                    $locale => [
                        'locale' => $locale,
                        'name' => $this->normalizeTextValue($state['name'] ?? null, false),
                        'slug' => $this->normalizeTextValue($state['slug'] ?? null, false),
                        'blocks' => array_values(array_filter((array) ($state['blocks'] ?? []))),
                        'meta_title' => $this->normalizeTextValue($state['meta_title'] ?? null),
                        'meta_description' => $this->normalizeTextValue($state['meta_description'] ?? null),
                        'robots' => $this->normalizeRobotsValue($state['robots'] ?? null),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getTranslationsFormState(?Category $category = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $category) {
            return $state;
        }

        foreach ($category->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), [
                    'name',
                    'slug',
                    'blocks',
                    'meta_title',
                    'meta_description',
                    'robots',
                ]),
                ['blocks' => array_values($translation->blocks ?? [])],
            );
        }

        return $state;
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
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(Category $category, array $translations): void
    {
        $existingTranslations = $category->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $category->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => (string) ($translation['name'] ?? ''),
                    'slug' => (string) ($translation['slug'] ?? ''),
                    'blocks' => $translation['blocks'] ?? [],
                    'meta_title' => $translation['meta_title'] ?? null,
                    'meta_description' => $translation['meta_description'] ?? null,
                    'robots' => $translation['robots'] ?? 'index, follow',
                ],
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

        if (blank($defaultTranslation['name'] ?? null) || blank($defaultTranslation['slug'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.name" => __('admin.resources.category.default_locale_required', ['locale' => $defaultLocale]),
                "translations.{$defaultLocale}.slug" => __('admin.resources.category.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    protected function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['name'] ?? null)
            || filled($translation['slug'] ?? null)
            || ! empty($translation['blocks'] ?? [])
            || filled($translation['meta_title'] ?? null)
            || filled($translation['meta_description'] ?? null)
            || ($translation['robots'] ?? 'index, follow') !== 'index, follow';
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

    protected function normalizeRobotsValue(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? $value : 'index, follow';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTranslation(): array
    {
        return [
            'name' => null,
            'slug' => null,
            'blocks' => [],
            'meta_title' => null,
            'meta_description' => null,
            'robots' => 'index, follow',
        ];
    }
}
