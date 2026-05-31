<?php

namespace Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages\Concerns;

use Commero\Interfaces\Filament\Resources\PostCategoryResource;
use Commero\Models\PostCategory;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithPostCategoryTranslations
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
    protected function preparePostCategoryData(array $data): array
    {
        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = $this->normalizeTranslations($data['translations'] ?? []);

        $this->guardDefaultLocaleTranslation($data['translations']);
        $data['path'] = $this->resolvePathFromTranslations($data['translations']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePostCategoryDataForActiveLocale(array $data, ?PostCategory $postCategory = null): array
    {
        if (! $postCategory) {
            return $this->preparePostCategoryData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? [], $postCategory);
        $existingTranslations = $this->getTranslationsFormState($postCategory->loadMissing('translations'));

        foreach (AdminLocales::supported() as $locale) {
            if ($locale === $activeLocale) {
                continue;
            }

            if ($this->hasMeaningfulTranslationData($existingTranslations[$locale] ?? [])) {
                $translations[$locale] = $this->normalizeTranslations([
                    $locale => $existingTranslations[$locale],
                ], $postCategory)[$locale];

                continue;
            }

            unset($translations[$locale]);
        }

        $data[static::ACTIVE_LOCALE_FIELD] = $activeLocale;
        $data['translations'] = $translations;

        $this->guardDefaultLocaleTranslation($translations);
        $data['path'] = $this->resolvePathFromTranslations($translations);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeTranslations(array $translations, ?PostCategory $postCategory = null): array
    {
        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations, $postCategory): array {
                $state = Arr::only((array) ($translations[$locale] ?? []), [
                    'name',
                    'slug',
                    'meta_title',
                    'meta_description',
                    'robots',
                ]);

                $name = $this->normalizeTextValue($state['name'] ?? null, false);
                $sourceSlug = $this->normalizeTextValue($state['slug'] ?? null, false);
                $slug = PostCategoryResource::generateUniqueSlug(
                    filled($sourceSlug) ? $sourceSlug : $name,
                    $locale,
                    $postCategory,
                ) ?? '';

                return [
                    $locale => [
                        'locale' => $locale,
                        'name' => $name,
                        'slug' => $slug,
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
    protected function getTranslationsFormState(?PostCategory $postCategory = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $postCategory) {
            return $state;
        }

        foreach ($postCategory->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), [
                    'name',
                    'slug',
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
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(PostCategory $postCategory, array $translations): void
    {
        $existingTranslations = $postCategory->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $postCategory->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => (string) ($translation['name'] ?? ''),
                    'slug' => (string) ($translation['slug'] ?? ''),
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

        if (blank($defaultTranslation['name'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.name" => __('commero::admin.resources.post_category.default_locale_required', ['locale' => $defaultLocale]),
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
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function resolvePathFromTranslations(array $translations): string
    {
        return (string) ($translations[Locales::default()]['slug'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTranslation(): array
    {
        return [
            'name' => null,
            'slug' => null,
            'meta_title' => null,
            'meta_description' => null,
            'robots' => 'index, follow',
        ];
    }
}
