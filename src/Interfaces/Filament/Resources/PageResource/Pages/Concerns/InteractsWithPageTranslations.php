<?php

namespace Commero\Interfaces\Filament\Resources\PageResource\Pages\Concerns;

use Commero\Interfaces\Filament\Resources\PageResource;
use Commero\Models\Page;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithPageTranslations
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
    protected function preparePageData(array $data): array
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);

        return $this->finalizePreparedPageData($data, $translations);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePageDataForActiveLocale(array $data, ?Page $page = null): array
    {
        if (! $page) {
            return $this->preparePageData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($page->loadMissing('translations'));

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

        return $this->finalizePreparedPageData($data, $translations);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeTranslations(array $translations): array
    {
        $defaults = AdminLocales::defaultTranslationsState();

        return collect(AdminLocales::supported())
            ->mapWithKeys(function (string $locale) use ($translations, $defaults): array {
                $state = array_merge(
                    $defaults[$locale],
                    Arr::only((array) ($translations[$locale] ?? []), [
                        'title',
                        'slug',
                        'excerpt',
                        'background_desktop_color',
                        'background_mobile_color',
                        'background_desktop_image',
                        'background_mobile_image',
                        'show_breadcrumbs',
                        'show_title',
                        'meta_title',
                        'meta_description',
                        'robots',
                        'blocks',
                    ]),
                );

                return [
                    $locale => [
                        'locale' => $locale,
                        'title' => $this->normalizeTextValue($state['title'] ?? null, false),
                        'slug' => $this->normalizeTextValue($state['slug'] ?? null, false),
                        'excerpt' => $this->normalizeTextValue($state['excerpt'] ?? null),
                        'background_desktop_color' => $this->normalizeTextValue($state['background_desktop_color'] ?? null),
                        'background_mobile_color' => $this->normalizeTextValue($state['background_mobile_color'] ?? null),
                        'background_desktop_image' => $this->normalizeTextValue($state['background_desktop_image'] ?? null),
                        'background_mobile_image' => $this->normalizeTextValue($state['background_mobile_image'] ?? null),
                        'show_breadcrumbs' => $this->normalizeBooleanValue($state['show_breadcrumbs'] ?? true),
                        'show_title' => $this->normalizeBooleanValue($state['show_title'] ?? true),
                        'meta_title' => $this->normalizeTextValue($state['meta_title'] ?? null),
                        'meta_description' => $this->normalizeTextValue($state['meta_description'] ?? null),
                        'robots' => $this->normalizeRobotsValue($state['robots'] ?? null),
                        'blocks' => array_values(array_filter((array) ($state['blocks'] ?? []))),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getTranslationsFormState(?Page $page = null): array
    {
        $state = AdminLocales::defaultTranslationsState();

        if (! $page) {
            return $state;
        }

        foreach ($page->translations as $translation) {
            $state[$translation->locale] = array_merge(
                AdminLocales::emptyTranslation(),
                Arr::only($translation->toArray(), [
                    'title',
                    'slug',
                    'excerpt',
                    'background_desktop_color',
                    'background_mobile_color',
                    'background_desktop_image',
                    'background_mobile_image',
                    'show_breadcrumbs',
                    'show_title',
                    'meta_title',
                    'meta_description',
                    'robots',
                    'blocks',
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
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, mixed>
     */
    protected function finalizePreparedPageData(array $data, array $translations): array
    {
        $translations = $this->ensureGeneratedSlugs($translations, $data['id'] ?? null);

        $this->guardDefaultLocaleTranslation($translations);

        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = $translations;
        $data['search_text'] = collect($translations)
            ->pluck('title')
            ->filter(fn (?string $title): bool => filled($title))
            ->join(' ');

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(Page $page, array $translations): void
    {
        $existingTranslations = $page->translations()->get()->keyBy('locale');

        foreach ($translations as $locale => $translation) {
            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $attributes = [
                'title' => (string) ($translation['title'] ?? ''),
                'slug' => (string) ($translation['slug'] ?? ''),
                'excerpt' => $translation['excerpt'] ?? null,
                'background_desktop_color' => $translation['background_desktop_color'] ?? null,
                'background_mobile_color' => $translation['background_mobile_color'] ?? null,
                'background_desktop_image' => $translation['background_desktop_image'] ?? null,
                'background_mobile_image' => $translation['background_mobile_image'] ?? null,
                'show_breadcrumbs' => $this->normalizeBooleanValue($translation['show_breadcrumbs'] ?? true),
                'show_title' => $this->normalizeBooleanValue($translation['show_title'] ?? true),
                'meta_title' => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'robots' => $translation['robots'] ?? 'index, follow',
                'blocks' => $translation['blocks'] ?? [],
            ];

            $page->translations()->updateOrCreate(
                ['locale' => $locale],
                $attributes,
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

        if (blank($defaultTranslation['title'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.title" => __('commero::admin.resources.page.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function ensureGeneratedSlugs(array $translations, int|string|null $pageId = null): array
    {
        $page = $pageId ? Page::query()->find($pageId) : ($this instanceof \Filament\Resources\Pages\EditRecord ? $this->getRecord() : null);

        foreach ($translations as $locale => $translation) {
            if (filled($translation['slug'] ?? null) || blank($translation['title'] ?? null)) {
                continue;
            }

            $translations[$locale]['slug'] = PageResource::generateUniqueSlug(
                (string) $translation['title'],
                (string) $locale,
                $page instanceof Page ? $page : null,
            );
        }

        return $translations;
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    protected function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['title'] ?? null)
            || filled($translation['slug'] ?? null)
            || filled($translation['excerpt'] ?? null)
            || filled($translation['background_desktop_color'] ?? null)
            || filled($translation['background_mobile_color'] ?? null)
            || filled($translation['background_desktop_image'] ?? null)
            || filled($translation['background_mobile_image'] ?? null)
            || ($translation['show_breadcrumbs'] ?? true) === false
            || ($translation['show_title'] ?? true) === false
            || filled($translation['meta_title'] ?? null)
            || filled($translation['meta_description'] ?? null)
            || ! empty($translation['blocks'] ?? []);
    }

    protected function normalizeTextValue(mixed $value, bool $nullable = true): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return $nullable ? null : '';
        }

        return $value;
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

    protected function normalizeRobotsValue(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? $value : 'index, follow';
    }

    protected function normalizeBooleanValue(mixed $value, bool $default = true): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
