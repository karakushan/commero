<?php

namespace Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages\Concerns;

use Commero\Interfaces\Filament\Resources\CityCategoryResource;
use Commero\Models\CityCategory;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait InteractsWithCityCategoryTranslations
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

    protected function prepareCityCategoryData(array $data): array
    {
        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = $this->normalizeTranslations($data['translations'] ?? []);
        $data['translations'] = $this->assignUniqueSlugs($data['translations']);
        $data['display_category_ids'] = $this->normalizeDisplayCategoryIds($data['display_category_ids'] ?? []);

        $this->guardDefaultLocaleTranslation($data['translations']);

        return $data;
    }

    protected function prepareCityCategoryDataForActiveLocale(array $data, ?CityCategory $cityCategory = null): array
    {
        if (! $cityCategory) {
            return $this->prepareCityCategoryData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($cityCategory->loadMissing('translations'));

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
        $data['translations'] = $this->assignUniqueSlugs($translations, $cityCategory);
        $data['display_category_ids'] = $this->normalizeDisplayCategoryIds($data['display_category_ids'] ?? []);

        $this->guardDefaultLocaleTranslation($data['translations']);

        return $data;
    }

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

    protected function getTranslationsFormState(?CityCategory $cityCategory = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $cityCategory) {
            return $state;
        }

        foreach ($cityCategory->translations as $translation) {
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

    protected function getActiveLocaleContextState(): array
    {
        return [
            static::ACTIVE_LOCALE_FIELD => $this->resolveActiveLocale(),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function assignUniqueSlugs(array $translations, ?CityCategory $cityCategory = null): array
    {
        $assignedSlugs = [];
        $defaultLocale = Locales::default();
        $defaultSlugFallbackSource = $translations[$defaultLocale]['slug']
            ?: ($translations[$defaultLocale]['name'] ?? null);

        foreach (AdminLocales::supported() as $locale) {
            $translation = $translations[$locale] ?? null;

            if (! is_array($translation)) {
                continue;
            }

            $slugSource = $translation['slug'] ?: ($translation['name'] ?? null);

            if (
                blank($slugSource)
                && $locale !== $defaultLocale
                && $this->hasMeaningfulTranslationData($translation)
                && filled($defaultSlugFallbackSource)
            ) {
                $slugSource = "{$defaultSlugFallbackSource}-{$locale}";
            }

            $slug = CityCategoryResource::generateUniqueSiteSlug(
                is_string($slugSource) ? $slugSource : null,
                $cityCategory,
                $assignedSlugs,
            );

            $translations[$locale]['slug'] = $slug;

            if (filled($slug)) {
                $assignedSlugs[] = $slug;
            }
        }

        return $translations;
    }

    protected function syncTranslations(CityCategory $cityCategory, array $translations): void
    {
        $existingTranslations = $cityCategory->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $cityCategory->translations()->updateOrCreate(
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

    protected function syncDisplayCategories(CityCategory $cityCategory, array $categoryIds): void
    {
        $syncData = collect($categoryIds)
            ->values()
            ->mapWithKeys(fn (int $categoryId, int $index): array => [$categoryId => ['sort' => $index]])
            ->all();

        $cityCategory->categories()->sync($syncData);
    }

    protected function mutatePathData(array $data, ?CityCategory $cityCategory = null): array
    {
        $defaultLocale = Locales::default();
        $parentId = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;
        $parent = $parentId ? CityCategory::query()->find($parentId) : null;
        $baseSlug = (string) data_get($data, "translations.{$defaultLocale}.slug", '');
        $basePath = trim((string) Str::slug($baseSlug), '/');

        if ($basePath === '') {
            return $data;
        }

        $path = $parent ? trim($parent->path.'/'.$basePath, '/') : $basePath;
        $originalPath = $path;
        $suffix = 2;

        while ($this->cityCategoryPathExists($path, $cityCategory)) {
            $path = $originalPath.'-'.$suffix;
            $suffix++;
        }

        $data['path'] = $path;
        $data['depth'] = $parent ? ((int) $parent->depth + 1) : 0;

        return $data;
    }

    protected function guardDefaultLocaleTranslation(array $translations): void
    {
        $defaultLocale = Locales::default();
        $defaultTranslation = $translations[$defaultLocale] ?? [];

        if (blank($defaultTranslation['name'] ?? null) || blank($defaultTranslation['slug'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.name" => __('admin.resources.city_category.default_locale_required', ['locale' => $defaultLocale]),
                "translations.{$defaultLocale}.slug" => __('admin.resources.city_category.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

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

    private function normalizeDisplayCategoryIds(array $categoryIds): array
    {
        return collect($categoryIds)
            ->filter(fn ($categoryId) => is_numeric($categoryId))
            ->map(fn ($categoryId) => (int) $categoryId)
            ->unique()
            ->values()
            ->all();
    }

    private function cityCategoryPathExists(string $path, ?CityCategory $cityCategory = null): bool
    {
        return CityCategory::query()
            ->where('path', $path)
            ->when($cityCategory?->getKey(), fn ($query, $cityCategoryId) => $query->whereKeyNot($cityCategoryId))
            ->exists();
    }

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
