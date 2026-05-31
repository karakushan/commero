<?php

namespace Commero\Interfaces\Filament\Resources\ProductResource\Pages\Concerns;

use Commero\Interfaces\Filament\Resources\ProductResource;
use Commero\Models\Product;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithProductTranslations
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
    protected function prepareProductData(array $data): array
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);

        return $this->finalizePreparedProductData($data, $translations);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareProductDataForActiveLocale(array $data, ?Product $product = null): array
    {
        if (! $product) {
            return $this->prepareProductData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($product->loadMissing('translations'));

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

        return $this->finalizePreparedProductData($data, $translations);
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
                    'description',
                    'full_description',
                    'meta_title',
                    'meta_description',
                    'robots',
                ]);

                return [
                    $locale => [
                        'locale' => $locale,
                        'name' => $this->normalizeTextValue($state['name'] ?? null, false),
                        'slug' => $this->normalizeTextValue($state['slug'] ?? null, false),
                        'description' => $this->normalizeTextValue($state['description'] ?? null),
                        'full_description' => $this->normalizeRichContentValue($state['full_description'] ?? null),
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
    protected function getTranslationsFormState(?Product $product = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $product) {
            return $state;
        }

        foreach ($product->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), [
                    'name',
                    'slug',
                    'description',
                    'full_description',
                    'meta_title',
                    'meta_description',
                    'robots',
                ]),
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
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, mixed>
     */
    protected function finalizePreparedProductData(array $data, array $translations): array
    {
        $translations = $this->assignUniqueSlugs($translations, $data['id'] ?? null);
        $this->guardDefaultLocaleTranslation($translations);
        $data = $this->mergeBulkGalleryUploadsIntoImages($data);

        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = array_values($translations);
        $data['search_text'] = collect($translations)
            ->pluck('name')
            ->filter(fn (?string $name): bool => filled($name))
            ->join(' ');

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function assignUniqueSlugs(array $translations, mixed $productId = null): array
    {
        $product = $productId ? Product::query()->find($productId) : ($this->getRecord() instanceof Product ? $this->getRecord() : null);
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

            $slug = ProductResource::generateUniqueSiteSlug(
                is_string($slugSource) ? $slugSource : null,
                $product,
                $assignedSlugs,
            );

            $translations[$locale]['slug'] = $slug;

            if (filled($slug)) {
                $assignedSlugs[] = $slug;
            }
        }

        return $translations;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeBulkGalleryUploadsIntoImages(array $data): array
    {
        $galleryUploads = collect(Arr::wrap($data['gallery_uploads'] ?? []))
            ->filter(fn (mixed $path): bool => filled($path))
            ->values();

        unset($data['gallery_uploads']);

        if ($galleryUploads->isEmpty()) {
            return $data;
        }

        $images = collect($data['images'] ?? [])
            ->filter(fn (mixed $image): bool => is_array($image))
            ->values();

        $nextSort = $images
            ->pluck('sort')
            ->filter(fn (mixed $sort): bool => is_numeric($sort))
            ->map(fn (mixed $sort): int => (int) $sort)
            ->max() ?? 0;

        foreach ($galleryUploads as $path) {
            $nextSort += 10;

            $images->push([
                'path' => (string) $path,
                'alt' => null,
                'sort' => $nextSort,
                'is_primary' => false,
            ]);
        }

        $data['images'] = $images->all();

        return $data;
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
                "translations.{$defaultLocale}.name" => __('commero::admin.resources.product.default_locale_required', ['locale' => $defaultLocale]),
                "translations.{$defaultLocale}.slug" => __('commero::admin.resources.product.default_locale_required', ['locale' => $defaultLocale]),
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
            || filled($translation['description'] ?? null)
            || filled($translation['full_description'] ?? null)
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

    /**
     * @return array<string, mixed>|string|null
     */
    protected function normalizeRichContentValue(mixed $value): array | string | null
    {
        if (is_array($value)) {
            return $value;
        }

        return $this->normalizeTextValue($value);
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
            'description' => null,
            'full_description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'robots' => 'index, follow',
        ];
    }
}
