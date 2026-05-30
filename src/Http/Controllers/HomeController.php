<?php

namespace Commero\Http\Controllers;

use Commero\Models\Page;
use Commero\Support\HomePageBlockHydrator;
use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(LocalizedSeoResolver $seoResolver, HomePageBlockHydrator $blockHydrator): View|Response
    {
        $locale = Locales::resolve(request()->route('locale') ?? app()->getLocale());
        App::setLocale($locale);

        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_translations')) {
            return response(
                sprintf(
                    '<!DOCTYPE html><html lang="%s"><body>UK</body></html>',
                    e($locale)
                )
            );
        }

        $page = Page::query()
            ->published()
            ->with('translations')
            ->whereHas('translations', fn ($query) => $query->where('slug', 'home'))
            ->firstOrFail();

        $defaultLocale = Locales::default();
        $localizedTranslation = $page->exactTranslation($locale);
        $renderableTranslation = $this->resolveRenderableTranslation($page, $locale, $defaultLocale);
        $pageTitle = filled($localizedTranslation?->title)
            ? $localizedTranslation->title
            : (filled($renderableTranslation?->title) ? $renderableTranslation->title : __('Home'));
        $seo = $seoResolver->forTranslatedContent(
            locale: $locale,
            translations: $page->translations,
            urlForLocale: fn (string $supportedLocale): string => Locales::isDefault($supportedLocale)
                ? route('home')
                : route('localized.home', ['locale' => $supportedLocale]),
            fallback: [
                'title' => $pageTitle,
                'heading' => $pageTitle,
                'description' => $localizedTranslation?->meta_description
                    ?? $renderableTranslation?->meta_description
                    ?? __('Wide selection of hats, caps, berets, scarves and gloves. Fast delivery throughout Ukraine. Quality guarantee.'),
            ],
            availableLocales: $page->translations->pluck('locale')->all(),
        );

        return view('shophats::pages.home', [
            'locale' => $locale,
            'supportedLocales' => Locales::supported(),
            'page' => $page,
            'pageTranslation' => $localizedTranslation ?? $renderableTranslation,
            'pageContentTranslation' => $renderableTranslation,
            'pageBlocks' => $blockHydrator->hydrate($renderableTranslation?->blocks ?? [], $locale),
            'seo' => $seo,
        ]);
    }

    private function resolveRenderableTranslation(Page $page, string $locale, string $defaultLocale): ?object
    {
        $translation = $page->translation($locale);

        if ($this->hasRenderableTranslationContent($translation)) {
            return $translation;
        }

        return $page->translation($defaultLocale);
    }

    private function hasRenderableTranslationContent(?object $translation): bool
    {
        if (! $translation) {
            return false;
        }

        return filled($translation->content ?? null)
            || ! empty($translation->blocks ?? []);
    }
}
