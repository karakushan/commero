<?php

namespace Commero\Support;

use Commero\Models\Category;
use Commero\Models\CategoryTranslation;
use Commero\Models\CityCategory;
use Commero\Models\CityCategoryTranslation;
use Commero\Models\Link;
use Commero\Models\Page;
use Commero\Models\PageTranslation;

class EntityLinkService
{
    public function resolve(string $slug, string $locale): ?Link
    {
        return Link::query()
            ->forLocale($locale)
            ->where('slug', $slug)
            ->first()
            ?? $this->resolveDefaultLocaleFallback($slug, $locale);
    }

    public function categoryUrl(Category|int|string $category, string $locale): ?string
    {
        $categoryId = $category instanceof Category ? $category->getKey() : $category;

        return $this->urlFor(Link::ENTITY_CATEGORY, $categoryId, $locale);
    }

    public function pageUrl(Page|int|string $page, string $locale): ?string
    {
        $pageId = $page instanceof Page ? $page->getKey() : $page;

        return $this->urlFor(Link::ENTITY_PAGE, $pageId, $locale);
    }

    public function cityCategoryUrl(CityCategory|int|string $cityCategory, string $locale): ?string
    {
        $cityCategoryId = $cityCategory instanceof CityCategory ? $cityCategory->getKey() : $cityCategory;

        return $this->urlFor(Link::ENTITY_CITY_CATEGORY, $cityCategoryId, $locale);
    }

    public function urlFor(string $entityType, int|string $entityId, string $locale): ?string
    {
        $link = Link::query()
            ->forEntity($entityType, $entityId)
            ->forLocale($locale)
            ->first();

        if (! $link && ! Locales::isDefault($locale)) {
            $link = Link::query()
                ->forEntity($entityType, $entityId)
                ->forLocale(Locales::default())
                ->first();
        }

        if (! $link) {
            return null;
        }

        return Locales::path('/'.$link->slug, $locale);
    }

    public function syncCategoryTranslation(CategoryTranslation $translation): void
    {
        $this->sync(
            entityType: Link::ENTITY_CATEGORY,
            entityId: $translation->category_id,
            locale: $translation->locale,
            slug: $translation->slug,
        );
    }

    public function syncPageTranslation(PageTranslation $translation): void
    {
        $this->sync(
            entityType: Link::ENTITY_PAGE,
            entityId: $translation->page_id,
            locale: $translation->locale,
            slug: $translation->slug,
        );
    }

    public function syncCityCategoryTranslation(CityCategoryTranslation $translation): void
    {
        $this->sync(
            entityType: Link::ENTITY_CITY_CATEGORY,
            entityId: $translation->city_category_id,
            locale: $translation->locale,
            slug: $translation->slug,
        );
    }

    public function deleteCategoryTranslation(CategoryTranslation $translation): void
    {
        $this->delete(Link::ENTITY_CATEGORY, $translation->category_id, $translation->locale);
    }

    public function deletePageTranslation(PageTranslation $translation): void
    {
        $this->delete(Link::ENTITY_PAGE, $translation->page_id, $translation->locale);
    }

    public function deleteCityCategoryTranslation(CityCategoryTranslation $translation): void
    {
        $this->delete(Link::ENTITY_CITY_CATEGORY, $translation->city_category_id, $translation->locale);
    }

    private function sync(string $entityType, int|string $entityId, string $locale, ?string $slug): void
    {
        $slug = Link::generateUniqueSlug($slug, $locale, $entityType, $entityId);

        if (blank($slug)) {
            $this->delete($entityType, $entityId, $locale);

            return;
        }

        Link::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'locale' => $locale,
            ],
            [
                'slug' => $slug,
            ],
        );
    }

    private function delete(string $entityType, int|string $entityId, string $locale): void
    {
        Link::query()
            ->forEntity($entityType, $entityId)
            ->forLocale($locale)
            ->delete();
    }

    private function resolveDefaultLocaleFallback(string $slug, string $locale): ?Link
    {
        if (Locales::isDefault($locale)) {
            return null;
        }

        return Link::query()
            ->forLocale(Locales::default())
            ->where('slug', $slug)
            ->first();
    }
}
