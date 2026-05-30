<?php

namespace Commero\Support;

use Commero\Models\Category;
use Commero\Models\Product;
use Illuminate\Support\Facades\Storage;

class HomePageBlockHydrator
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public function hydrate(array $blocks, string $locale): array
    {
        return collect($blocks)
            ->map(function (array $block) use ($locale): array {
                $type = $block['type'] ?? null;
                $data = is_array($block['data'] ?? null) ? $block['data'] : [];

                return [
                    'type' => $type,
                    'data' => match ($type) {
                        'home_product_slider' => $this->hydrateProductSlider($data, $locale),
                        default => $data,
                    },
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function hydrateProductSlider(array $data, string $locale): array
    {
        $sourceType = (string) ($data['source_type'] ?? 'category');
        $limit = max(1, min(24, (int) ($data['products_limit'] ?? 8)));

        if ($sourceType !== 'category') {
            return [
                ...$data,
                'source_type' => $sourceType,
                'category' => null,
                'button_link' => $this->productSliderSourceUrl($sourceType, $locale),
                'products' => $this->productsForSource($sourceType, $locale, $limit),
                'tabs' => [],
            ];
        }

        $categoryId = (int) ($data['source_category_id'] ?? 0);

        if ($categoryId <= 0) {
            return [
                ...$data,
                'source_type' => 'category',
                'category' => null,
                'button_link' => null,
                'products' => [],
                'tabs' => [],
            ];
        }

        $category = Category::query()
            ->with([
                'translations',
                'children' => fn ($query) => $query
                    ->with('translations')
                    ->orderBy('sort')
                    ->orderBy('path'),
            ])
            ->find($categoryId);

        if (! $category) {
            return [
                ...$data,
                'source_type' => 'category',
                'category' => null,
                'button_link' => null,
                'products' => [],
                'tabs' => [],
            ];
        }

        return [
            ...$data,
            'source_type' => 'category',
            'category' => [
                'id' => $category->id,
                'name' => $this->localizedCategoryName($category, $locale),
                'slug' => $category->localizedSlug($locale, $category->path),
            ],
            'button_link' => $this->categoryUrl($category, $locale),
            'products' => $this->productsForCategoryTree($category, $locale, $limit),
            'tabs' => $category->children
                ->map(function (Category $child) use ($locale, $limit): array {
                    $products = $this->productsForCategoryTree($child, $locale, $limit);

                    return [
                        'id' => $child->id,
                        'name' => $this->localizedCategoryName($child, $locale),
                        'slug' => $child->localizedSlug($locale, $child->path),
                        'products' => $products,
                    ];
                })
                ->filter(fn (array $tab): bool => $tab['products'] !== [])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productsForCategoryTree(Category $category, string $locale, int $limit): array
    {
        return $this->baseProductsQuery($locale)
            ->where('status', 'published')
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $this->categoryTreeIds($category)))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Product $product): array => $this->mapProduct($product, $locale))
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function categoryTreeIds(Category $category): array
    {
        return Category::query()
            ->where('id', $category->id)
            ->orWhere('path', 'like', $category->path.'/%')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productsForSource(string $sourceType, string $locale, int $limit): array
    {
        $query = $this->baseProductsQuery($locale)
            ->where('status', 'published');

        match ($sourceType) {
            'popular' => $query->orderByDesc('views_count')->orderByDesc('id'),
            'sale' => $query->where('is_on_sale', true)->latest(),
            'hit' => $query->where('is_hit_sales', true)->latest(),
            'new' => $query->where('is_new', true)->latest(),
            default => $query->latest(),
        };

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (Product $product): array => $this->mapProduct($product, $locale))
            ->all();
    }

    private function baseProductsQuery(string $locale)
    {
        return Product::query()
            ->withTranslationsFor($locale)
            ->with([
                'primaryImage',
                'images',
                'variants' => fn ($query) => $query->orderBy('id'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProduct(Product $product, string $locale): array
    {
        $translation = $product->exactTranslation($locale)
            ?? $product->exactTranslation(Locales::default())
            ?? $product->translations->first();
        $primaryVariant = $product->variants->first();

        $galleryImages = $product->images
            ->map(function ($image): array {
                $imageUrl = Storage::disk('public')->url($image->path);

                return [
                    'id' => $image->id,
                    'full' => $imageUrl,
                    'thumb' => $imageUrl,
                ];
            })
            ->all();

        return [
            'id' => $product->id,
            'name' => $translation?->name ?? 'Product',
            'slug' => $product->localizedSlug($locale, $product->sku) ?? $product->sku,
            'sku' => $product->sku,
            'price' => (float) ($primaryVariant?->price ?? 0),
            'old_price' => $primaryVariant?->old_price !== null ? (float) $primaryVariant->old_price : null,
            'is_on_sale' => (bool) $product->is_on_sale,
            'is_hit_sales' => (bool) $product->is_hit_sales,
            'is_new' => (bool) $product->is_new,
            'image' => $product->primaryImage
                ? Storage::disk('public')->url($product->primaryImage->path)
                : 'images/shophats/products/placeholder.jpg',
            'gallery' => $galleryImages,
            'stock_status' => $product->effectiveStockStatus(),
            'url' => Locales::isDefault($locale)
                ? route('product.show', ['slug' => $product->localizedSlug($locale, $product->sku) ?? $product->sku])
                : route('localized.product.show', ['locale' => $locale, 'slug' => $product->localizedSlug($locale, $product->sku) ?? $product->sku]),
        ];
    }

    private function localizedCategoryName(Category $category, string $locale): string
    {
        return $category->exactTranslation($locale)?->name
            ?? $category->exactTranslation(Locales::default())?->name
            ?? $category->translations->first()?->name
            ?? $category->path;
    }

    private function categoryUrl(Category $category, string $locale): string
    {
        return $category->frontendUrl($locale) ?? Locales::path('/'.$category->path, $locale);
    }

    private function productSliderSourceUrl(string $sourceType, string $locale): ?string
    {
        return match ($sourceType) {
            'popular' => Locales::isDefault($locale)
                ? route('catalog.index')
                : route('localized.catalog.index', ['locale' => $locale]),
            'sale' => Locales::isDefault($locale)
                ? route('sale.index')
                : route('localized.sale.index', ['locale' => $locale]),
            'hit' => Locales::isDefault($locale)
                ? route('special-offers.index', ['offerType' => 'top'])
                : route('localized.special-offers.index', ['locale' => $locale, 'offerType' => 'top']),
            'new' => Locales::isDefault($locale)
                ? route('special-offers.index', ['offerType' => 'novelty'])
                : route('localized.special-offers.index', ['locale' => $locale, 'offerType' => 'novelty']),
            default => null,
        };
    }
}
