<?php

namespace Commero\Interfaces\Filament\Resources\PostResource\Pages\Concerns;

use Commero\Interfaces\Filament\Resources\PostResource;
use Commero\Models\Post;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithPostTranslations
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
    protected function preparePostData(array $data): array
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);

        return $this->finalizePreparedPostData($data, $translations);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePostDataForActiveLocale(array $data, ?Post $post = null): array
    {
        if (! $post) {
            return $this->preparePostData($data);
        }

        $activeLocale = $this->resolveActiveLocale();
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $existingTranslations = $this->getTranslationsFormState($post->loadMissing('translations'));

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

        return $this->finalizePreparedPostData($data, $translations);
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
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'meta_title',
                    'meta_description',
                    'robots',
                ]);

                return [
                    $locale => [
                        'locale' => $locale,
                        'title' => $this->normalizeTextValue($state['title'] ?? null, false),
                        'slug' => $this->normalizeTextValue($state['slug'] ?? null, false),
                        'excerpt' => $this->normalizeTextValue($state['excerpt'] ?? null),
                        'content' => $this->normalizeTextValue($state['content'] ?? null),
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
    protected function getTranslationsFormState(?Post $post = null): array
    {
        $state = collect(AdminLocales::supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => $this->emptyTranslation()])
            ->all();

        if (! $post) {
            return $state;
        }

        foreach ($post->translations as $translation) {
            $state[$translation->locale] = array_merge(
                $this->emptyTranslation(),
                Arr::only($translation->toArray(), [
                    'title',
                    'slug',
                    'excerpt',
                    'content',
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
    protected function finalizePreparedPostData(array $data, array $translations): array
    {
        $translations = $this->assignUniqueSlugs($translations, $data['id'] ?? null);
        $this->guardDefaultLocaleTranslation($translations);

        $data[static::ACTIVE_LOCALE_FIELD] = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);
        $data['translations'] = array_values($translations);
        $data['search_text'] = collect($translations)
            ->pluck('title')
            ->filter(fn (?string $title): bool => filled($title))
            ->join(' ');

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, mixed>>
     */
    protected function assignUniqueSlugs(array $translations, mixed $postId = null): array
    {
        $post = $postId ? Post::query()->find($postId) : ($this->getRecord() instanceof Post ? $this->getRecord() : null);
        $defaultLocale = Locales::default();
        $defaultSlugFallbackSource = $translations[$defaultLocale]['slug']
            ?: ($translations[$defaultLocale]['title'] ?? null);

        foreach (AdminLocales::supported() as $locale) {
            $translation = $translations[$locale] ?? null;

            if (! is_array($translation)) {
                continue;
            }

            $slugSource = $translation['slug'] ?: ($translation['title'] ?? null);

            if (
                blank($slugSource)
                && $locale !== $defaultLocale
                && $this->hasMeaningfulTranslationData($translation)
                && filled($defaultSlugFallbackSource)
            ) {
                $slugSource = "{$defaultSlugFallbackSource}-{$locale}";
            }

            $slug = PostResource::generateUniqueSlug(
                is_string($slugSource) ? $slugSource : null,
                $locale,
                $post,
            );

            $translations[$locale]['slug'] = $slug;
        }

        return $translations;
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(Post $post, array $translations): void
    {
        $existingTranslations = $post->translations()->get()->keyBy('locale');

        foreach ($translations as $translation) {
            $locale = (string) ($translation['locale'] ?? '');

            if (! in_array($locale, AdminLocales::supported(), true)) {
                continue;
            }

            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $post->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => (string) ($translation['title'] ?? ''),
                    'slug' => (string) ($translation['slug'] ?? ''),
                    'excerpt' => $translation['excerpt'] ?? null,
                    'content' => $translation['content'] ?? null,
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

        if (blank($defaultTranslation['title'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$defaultLocale}.title" => __('admin.resources.post.default_locale_required', ['locale' => $defaultLocale]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $translation
     */
    protected function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['title'] ?? null)
            || filled($translation['slug'] ?? null)
            || filled($translation['excerpt'] ?? null)
            || filled($translation['content'] ?? null)
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
        $allowed = [
            'index, follow',
            'noindex, follow',
            'index, nofollow',
            'noindex, nofollow',
        ];

        return in_array($value, $allowed, true) ? $value : 'index, follow';
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyTranslation(): array
    {
        return [
            'title' => null,
            'slug' => null,
            'excerpt' => null,
            'content' => null,
            'meta_title' => null,
            'meta_description' => null,
            'robots' => 'index, follow',
        ];
    }
}
