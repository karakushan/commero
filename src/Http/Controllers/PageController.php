<?php

namespace Commero\Http\Controllers;

use Commero\Models\Page;
use Commero\Support\EntityLinkService;
use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Request $request, LocalizedSeoResolver $seoResolver, EntityLinkService $entityLinkService): View
    {
        $slug = (string) $request->route('slug');
        $defaultLocale = Locales::default();
        $locale = Locales::resolve($request->route('locale') ?? app()->getLocale());
        App::setLocale($locale);

        $page = Page::query()
            ->published()
            ->with('translations')
            ->whereLocalizedSlug($slug, $locale)
            ->firstOrFail();

        $localizedTranslation = $page->exactTranslation($locale);
        $renderableTranslation = $this->resolveRenderableTranslation($page, $locale, $defaultLocale);
        $pageTitle = filled($localizedTranslation?->title)
            ? $localizedTranslation->title
            : (filled($renderableTranslation?->title) ? $renderableTranslation->title : $slug);
        $seo = $seoResolver->forTranslatedContent(
            locale: $locale,
            translations: $page->translations,
            urlForLocale: fn (string $supportedLocale): ?string => $entityLinkService->pageUrl($page, $supportedLocale),
            fallback: [
                'title' => $pageTitle,
                'heading' => $pageTitle,
            ],
            availableLocales: $page->translations->pluck('locale')->all(),
        );

        return view('shophats::pages.cms-page', [
            'page' => $page,
            'pageTranslation' => $localizedTranslation ?? $renderableTranslation,
            'pageContentTranslation' => $renderableTranslation,
            'pageTitle' => $pageTitle,
            'pageBlocks' => $renderableTranslation?->blocks ?? [],
            'seo' => $seo,
            'homeUrl' => $locale === $defaultLocale
                ? route('home')
                : route('localized.home', ['locale' => $locale]),
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
