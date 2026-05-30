<?php

namespace Commero\Application\Catalog\Queries;

use Commero\Application\Catalog\DTOs\CatalogProductCardData;
use Commero\Models\ProductAttribute;
use Commero\Models\Product;
use Commero\Models\ProductVariant;
use Commero\Support\Locales;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CatalogProductListQuery
{
    public function handle(string $locale, string $sort = 'popular_desc', array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('products.status', 'published')
            ->withTranslationsFor($locale)
            ->with([
                'brand:id,name',
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'images:id,product_id,path,alt,sort',
                'categories' => fn ($query) => $query->withTranslationsFor($locale),
                'attributeValues.attribute' => fn ($query) => $query->withTranslationsFor($locale),
                'attributeValues.option' => fn ($query) => $query->withTranslationsFor($locale),
                'variants' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
            ]);

        $query = $this->applySorting($query, $sort);
        $query = $this->applyFilters($query, $filters);

        return $query
            ->paginate($perPage)
            ->through(fn (Product $product) => $this->toData($product, $locale));
    }

    public function count(string $locale, array $filters = []): int
    {
        $query = Product::query()
            ->where('products.status', 'published')
            ->withTranslationsFor($locale);

        $query = $this->applyFilters($query, $filters);

        return (clone $query)->distinct('products.id')->count('products.id');
    }

    private function applySorting($query, string $sort)
    {
        $primaryVariantPrice = ProductVariant::query()
            ->select('price')
            ->whereColumn('product_id', 'products.id')
            ->orderBy('sort')
            ->orderBy('id')
            ->limit(1);

        return match ($sort) {
            'popular_desc' => $query
                ->orderByDesc('products.views_count')
                ->orderByDesc('products.id'),
            'price_asc' => $query
                ->orderBy($primaryVariantPrice, 'asc')
                ->orderBy('products.id'),
            'price_desc' => $query
                ->orderBy($primaryVariantPrice, 'desc')
                ->orderByDesc('products.id'),
            default => $query->latest('id'), // date_desc
        };
    }

    private function applyFilters($query, array $filters)
    {
        // Search filter
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('translations', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'like', '%' . $searchTerm . '%');
            });
        }

        // Price filter
        if (isset($filters['price']) && is_array($filters['price'])) {
            if (!empty($filters['price']['from'])) {
                $query->whereHas('variants', function ($q) use ($filters) {
                    $q->where('price', '>=', $filters['price']['from']);
                });
            }
            if (!empty($filters['price']['to'])) {
                $query->whereHas('variants', function ($q) use ($filters) {
                    $q->where('price', '<=', $filters['price']['to']);
                });
            }
        }

        // Categories filter
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('categories.id', $filters['categories']);
            });
        }

        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            $attributeTypes = ProductAttribute::query()
                ->whereIn('code', array_keys($filters['attributes']))
                ->pluck('value_type', 'code');

            foreach ($filters['attributes'] as $attributeCode => $selectedValues) {
                $selectedValues = collect((array) $selectedValues)
                    ->filter(fn ($value) => filled($value) || $value === '0')
                    ->map(fn ($value) => (string) $value)
                    ->values()
                    ->all();

                if ($selectedValues === []) {
                    continue;
                }

                $valueType = $attributeTypes[$attributeCode] ?? 'select';

                $query->whereHas('attributeValues', function ($q) use ($attributeCode, $selectedValues, $valueType) {
                    $q->whereHas('attribute', function ($attributeQuery) use ($attributeCode) {
                        $attributeQuery->where('code', $attributeCode);
                    });

                    match ($valueType) {
                        'integer' => $q->whereIn('value_integer', array_map('intval', $selectedValues)),
                        'numeric' => $q->whereIn('value_numeric', array_map('floatval', $selectedValues)),
                        'boolean' => $q->whereIn('value_boolean', array_map(
                            fn ($value) => in_array($value, ['1', 'true'], true),
                            $selectedValues
                        )),
                        default => $q->where(function ($valueQuery) use ($selectedValues) {
                            $valueQuery
                                ->whereIn('value_string', $selectedValues)
                                ->orWhereHas('option', function ($optionQuery) use ($selectedValues) {
                                    $optionQuery->whereIn('value', $selectedValues);
                                });
                        }),
                    };
                });
            }
        }

        return $query;
    }

    private function toData(Product $product, string $locale): CatalogProductCardData
    {
        $translation = $product->translation($locale);
        $primaryVariant = $product->variants->first();

        // Build gallery with full URLs
        $galleryImages = [];
        foreach ($product->images as $image) {
            $galleryImages[] = [
                'id' => $image->id,
                'full' => Storage::disk('public')->url($image->path),
                'thumb' => Storage::disk('public')->url($image->path),
            ];
        }

        // If no gallery, use primary image
        if (empty($galleryImages) && $product->primaryImage) {
            $galleryImages[] = [
                'id' => $product->primaryImage->id,
                'full' => Storage::disk('public')->url($product->primaryImage->path),
                'thumb' => Storage::disk('public')->url($product->primaryImage->path),
            ];
        }

        // Get price from first variant
        $price = $primaryVariant?->price ?? 0;
        $oldPrice = $primaryVariant?->old_price ?? null;

        return new CatalogProductCardData(
            id: $product->id,
            locale: $translation?->locale ?? $locale,
            name: $translation?->name ?? $product->sku,
            slug: $product->localizedSlug($locale, $product->sku) ?? $product->sku,
            description: $translation?->description,
            sku: $product->sku,
            brand: $product->brand?->name,
            primaryImageUrl: filled($product->primaryImage?->path)
                ? Storage::disk('public')->url($product->primaryImage->path)
                : null,
            primaryImageAlt: $product->primaryImage?->alt,
            price: $price,
            old_price: $oldPrice,
            is_on_sale: (bool) $product->is_on_sale,
            is_hit_sales: (bool) $product->is_hit_sales,
            is_new: (bool) $product->is_new,
            gallery: $galleryImages,
            stock_status: $product->stock_status ?? 'in_stock',
            url: Locales::isDefault($locale)
                ? route('product.show', ['slug' => $product->localizedSlug($locale, $product->sku) ?? $product->sku])
                : route('localized.product.show', ['locale' => $locale, 'slug' => $product->localizedSlug($locale, $product->sku) ?? $product->sku]),
            categories: $product->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->translation($locale)?->name,
                'slug' => $category->localizedSlug($locale, $category->path),
            ])->filter(fn (array $category) => filled($category['name']))->values()->all(),
            attributes: $product->attributeValues->map(function ($value) use ($locale) {
                $attributeTranslation = $value->attribute?->translation($locale);
                $optionTranslation = $value->option?->translation($locale);

                return [
                    'code' => $value->attribute?->code,
                    'name' => $attributeTranslation?->name,
                    'value' => $optionTranslation?->label
                        ?? $value->value_string
                        ?? $value->value_integer
                        ?? $value->value_numeric,
                ];
            })->filter(fn (array $attribute) => filled($attribute['name']) && filled($attribute['value']))->values()->all(),
        );
    }
}
