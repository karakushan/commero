<?php

namespace Commero\Application\Catalog\Queries;

use Commero\Application\Catalog\Support\LocaleCache;
use Commero\Models\Category;
use Commero\Models\ProductAttributeValue;
use Commero\Models\ProductAttribute;
use Commero\Models\ProductVariant;
use Commero\Support\Locales;
use Illuminate\Support\Collection;

class CatalogFiltersQuery
{
    public function __construct(private readonly LocaleCache $cache) {}

    public function handle(string $locale, ?array $categoryIds = null): array
    {
        $normalizedCategoryIds = collect($categoryIds ?? [])
            ->filter(fn ($categoryId) => is_numeric($categoryId))
            ->map(fn ($categoryId) => (int) $categoryId)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $cacheKey = $normalizedCategoryIds === []
            ? 'catalog'
            : 'catalog_category_'.implode('_', $normalizedCategoryIds);

        return $this->cache->remember('filters', $locale, $cacheKey, function () use ($locale, $normalizedCategoryIds) {
            // Get price range
            $priceRange = ProductVariant::query()
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->where('products.status', 'published')
                ->when($normalizedCategoryIds !== [], function ($query) use ($normalizedCategoryIds) {
                    $query->whereExists(function ($subQuery) use ($normalizedCategoryIds) {
                        $subQuery
                            ->selectRaw('1')
                            ->from('product_category')
                            ->whereColumn('product_category.product_id', 'product_variants.product_id')
                            ->whereIn('product_category.category_id', $normalizedCategoryIds);
                    });
                })
                ->selectRaw('MIN(price) as min, MAX(price) as max')
                ->first();

            $optionUsage = ProductAttributeValue::query()
                ->join('products', 'products.id', '=', 'product_attribute_values.product_id')
                ->where('products.status', 'published')
                ->when($normalizedCategoryIds !== [], function ($query) use ($normalizedCategoryIds) {
                    $query->whereExists(function ($subQuery) use ($normalizedCategoryIds) {
                        $subQuery
                            ->selectRaw('1')
                            ->from('product_category')
                            ->whereColumn('product_category.product_id', 'product_attribute_values.product_id')
                            ->whereIn('product_category.category_id', $normalizedCategoryIds);
                    });
                })
                ->whereNotNull('product_attribute_values.value_option_id')
                ->selectRaw('product_attribute_values.value_option_id, COUNT(DISTINCT products.id) as products_count')
                ->groupBy('product_attribute_values.value_option_id')
                ->pluck('products_count', 'product_attribute_values.value_option_id');

            $scalarValueUsage = ProductAttributeValue::query()
                ->join('products', 'products.id', '=', 'product_attribute_values.product_id')
                ->where('products.status', 'published')
                ->when($normalizedCategoryIds !== [], function ($query) use ($normalizedCategoryIds) {
                    $query->whereExists(function ($subQuery) use ($normalizedCategoryIds) {
                        $subQuery
                            ->selectRaw('1')
                            ->from('product_category')
                            ->whereColumn('product_category.product_id', 'product_attribute_values.product_id')
                            ->whereIn('product_category.category_id', $normalizedCategoryIds);
                    });
                })
                ->whereNull('product_attribute_values.value_option_id')
                ->selectRaw('
                    product_attribute_values.attribute_id,
                    product_attribute_values.value_string,
                    product_attribute_values.value_integer,
                    product_attribute_values.value_numeric,
                    product_attribute_values.value_boolean,
                    COUNT(DISTINCT products.id) as products_count
                ')
                ->groupBy([
                    'product_attribute_values.attribute_id',
                    'product_attribute_values.value_string',
                    'product_attribute_values.value_integer',
                    'product_attribute_values.value_numeric',
                    'product_attribute_values.value_boolean',
                ])
                ->get()
                ->groupBy('attribute_id');

            $categories = Category::query()
                ->with([
                    'translations' => fn ($query) => $query->whereIn('locale', $this->preferredCatalogLocales($locale)),
                ])
                ->orderBy('path')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $this->localizedCategoryName($category, $locale),
                    'slug' => $category->localizedSlug($locale),
                ])
                ->filter(fn (array $category) => filled($category['name']))
                ->values()
                ->all();

            $attributes = ProductAttribute::query()
                ->where('is_filterable', true)
                ->with([
                    'translations' => fn ($query) => $query->whereIn('locale', $this->preferredCatalogLocales($locale)),
                    'options.translations' => fn ($query) => $query->whereIn('locale', $this->preferredCatalogLocales($locale)),
                ])
                ->orderBy('sort')
                ->get()
                ->map(function (ProductAttribute $attribute) use ($locale, $optionUsage, $scalarValueUsage) {
                    $options = $attribute->options
                        ->map(fn ($option) => $this->normalizeFilterOption([
                            'id' => $option->id,
                            'label' => $this->localizedOptionLabel($option, $locale),
                            'value' => $option->value,
                            'count' => (int) ($optionUsage[$option->id] ?? 0),
                        ]))
                        ->filter()
                        ->values();

                    if ($options->isEmpty()) {
                        $options = $this->buildScalarOptions(
                            $attribute->value_type,
                            $scalarValueUsage->get($attribute->id, collect())
                        );
                    }

                    return [
                        'id' => $attribute->id,
                        'code' => $attribute->code,
                        'name' => $this->localizedAttributeName($attribute, $locale),
                        'options' => $options->all(),
                    ];
                })
                ->filter(fn (array $attribute) => filled($attribute['name']) && !empty($attribute['options']))
                ->values()
                ->all();

            return [
                'price' => [
                    'min' => round($priceRange->min ?? 0),
                    'max' => round($priceRange->max ?? 1000),
                ],
                'categories' => $categories,
                'attributes' => $attributes,
            ];
        });
    }

    private function buildScalarOptions(string $valueType, Collection $rows): Collection
    {
        return $rows
            ->map(function ($row) use ($valueType) {
                $value = match ($valueType) {
                    'integer' => $row->value_integer,
                    'numeric' => $row->value_numeric,
                    'boolean' => is_null($row->value_boolean) ? null : ((bool) $row->value_boolean ? '1' : '0'),
                    default => $row->value_string,
                };

                if (! filled($value) && $value !== '0') {
                    return null;
                }

                $label = match ($valueType) {
                    'boolean' => $value === '1' ? 'Yes' : 'No',
                    default => (string) $value,
                };

                return $this->normalizeFilterOption([
                    'id' => null,
                    'label' => $label,
                    'value' => (string) $value,
                    'count' => (int) $row->products_count,
                ]);
            })
            ->filter()
            ->unique('value')
            ->values();
    }

    private function normalizeFilterOption(array $option): ?array
    {
        $label = trim((string) ($option['label'] ?? ''));
        $value = trim((string) ($option['value'] ?? ''));
        $count = (int) ($option['count'] ?? 0);

        if ($value === '' || $count <= 0) {
            return null;
        }

        $option['label'] = $label !== '' ? $label : $value;
        $option['value'] = $value;
        $option['count'] = $count;

        return $option;
    }

    private function preferredCatalogLocales(string $locale): array
    {
        return array_values(array_unique(array_filter([
            $locale,
            Locales::default(),
            Locales::fallback(),
        ])));
    }

    private function localizedCategoryName(Category $category, string $locale): ?string
    {
        return $category->exactTranslation($locale)?->name
            ?? $category->exactTranslation(Locales::default())?->name
            ?? $category->translations->first()?->name;
    }

    private function localizedAttributeName(ProductAttribute $attribute, string $locale): ?string
    {
        return $attribute->exactTranslation($locale)?->name
            ?? $attribute->exactTranslation(Locales::default())?->name
            ?? $attribute->translations->first()?->name
            ?? $attribute->code;
    }

    private function localizedOptionLabel(object $option, string $locale): ?string
    {
        return $option->exactTranslation($locale)?->label
            ?? $option->exactTranslation(Locales::default())?->label
            ?? $option->translations->first()?->label
            ?? $option->value;
    }
}
