<?php

namespace Commero\Support\Seo;

use Commero\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class LocalizedSeoResolver
{
    /**
     * @param  iterable<int, object|array<string, mixed>>  $translations
     * @param  Closure(string): ?string  $urlForLocale
     * @param  array<int, string>|null  $availableLocales
     * @return array{
     *     html_lang: string,
     *     title: string,
     *     heading: string,
     *     description: ?string,
     *     robots: string,
     *     canonical: string,
     *     alternates: array<int, array{locale: string, href: string}>,
     *     x_default: ?string
     * }
     */
    public function forTranslatedContent(
        string $locale,
        iterable $translations,
        Closure $urlForLocale,
        array $fallback = [],
        string $headingField = 'title',
        ?array $availableLocales = null,
    ): array {
        $translationMap = $this->translationsByLocale($translations);
        $defaultLocale = Locales::default();
        $currentTranslation = $translationMap->get($locale);
        $availableLocales ??= $translationMap->keys()->all();

        $heading = $this->stringValue(
            $this->getTranslationValue($currentTranslation, $headingField),
            $fallback['heading'] ?? null,
            $fallback['title'] ?? null,
            config('app.name'),
        );

        $title = $this->stringValue(
            $this->getTranslationValue($currentTranslation, 'meta_title'),
            $heading,
        );

        $canonical = $urlForLocale($locale) ?? ($fallback['canonical'] ?? url()->current());
        $defaultUrl = $urlForLocale($defaultLocale) ?? ($fallback['x_default'] ?? null);

        $alternates = collect($availableLocales)
            ->filter(fn (string $supportedLocale): bool => in_array($supportedLocale, Locales::supported(), true))
            ->map(function (string $supportedLocale) use ($urlForLocale): ?array {
                $href = $urlForLocale($supportedLocale);

                if (! filled($href)) {
                    return null;
                }

                return [
                    'locale' => $supportedLocale,
                    'href' => $href,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'html_lang' => $locale,
            'title' => $title,
            'heading' => $heading,
            'description' => $this->nullableValue(
                $this->getTranslationValue($currentTranslation, 'meta_description'),
                $fallback['description'] ?? null,
            ),
            'robots' => $this->stringValue(
                $this->getTranslationValue($currentTranslation, 'robots'),
                $fallback['robots'] ?? 'index, follow',
            ),
            'canonical' => $canonical,
            'alternates' => $alternates,
            'x_default' => $defaultUrl,
        ];
    }

    /**
     * @return array{
     *     html_lang: string,
     *     title: string,
     *     heading: string,
     *     description: ?string,
     *     robots: string,
     *     canonical: string,
     *     alternates: array<int, array{locale: string, href: string}>,
     *     x_default: ?string
     * }
     */
    public function forCurrentRoute(
        Request $request,
        string $locale,
        ?string $title = null,
        ?string $heading = null,
        ?string $description = null,
        ?string $robots = null,
    ): array {
        $route = $request->route();
        $routeName = $route?->getName();
        $parameters = $route?->parametersWithoutNulls() ?? [];
        unset($parameters['locale']);

        $canonical = $request->url();
        $xDefault = null;
        $alternates = [];

        if (filled($routeName)) {
            $baseRouteName = str_starts_with($routeName, 'localized.')
                ? substr($routeName, 10)
                : $routeName;

            foreach (Locales::supported() as $supportedLocale) {
                $localizedRouteName = Locales::isDefault($supportedLocale)
                    ? $baseRouteName
                    : 'localized.'.$baseRouteName;

                if (! Route::has($localizedRouteName)) {
                    continue;
                }

                $routeParameters = Locales::isDefault($supportedLocale)
                    ? $parameters
                    : [...$parameters, 'locale' => $supportedLocale];

                $href = route($localizedRouteName, $routeParameters);

                $alternates[] = [
                    'locale' => $supportedLocale,
                    'href' => $href,
                ];

                if ($supportedLocale === $locale) {
                    $canonical = $href;
                }

                if (Locales::isDefault($supportedLocale)) {
                    $xDefault = $href;
                }
            }
        }

        return [
            'html_lang' => $locale,
            'title' => $this->stringValue($title, config('app.name')),
            'heading' => $this->stringValue($heading, $title, config('app.name')),
            'description' => $this->nullableValue($description),
            'robots' => $this->stringValue($robots, 'index, follow'),
            'canonical' => $canonical,
            'alternates' => $alternates,
            'x_default' => $xDefault,
        ];
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $translations
     * @return Collection<string, object|array<string, mixed>>
     */
    private function translationsByLocale(iterable $translations): Collection
    {
        return collect($translations)
            ->filter(fn ($translation): bool => filled($this->getTranslationValue($translation, 'locale')))
            ->keyBy(fn ($translation): string => (string) $this->getTranslationValue($translation, 'locale'));
    }

    private function getTranslationValue(object|array|null $translation, string $field): mixed
    {
        if ($translation === null) {
            return null;
        }

        if (is_array($translation)) {
            return $translation[$field] ?? null;
        }

        return $translation->{$field} ?? null;
    }

    private function stringValue(mixed ...$values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    private function nullableValue(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
