<?php

namespace Commero\Interfaces\Http\Livewire;

use Commero\Application\Catalog\Queries\CatalogFiltersQuery;
use Commero\Application\Catalog\Queries\CatalogProductListQuery;
use Commero\Models\Product;
use Commero\Models\ProductVariant;
use Commero\Support\Locales;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SaleProductsPage extends Component
{
    use WithPagination;

    private const DEFAULT_PER_PAGE = 12;
    private const LOAD_MORE_STEP = 3;
    private const ALLOWED_SORTS = [
        'popular_desc',
        'date_desc',
        'price_asc',
        'price_desc',
    ];

    public string $locale;
    public string $sort = 'popular_desc';
    public int $perPage = self::DEFAULT_PER_PAGE;
    public array $filters = [];

    protected function queryString(): array
    {
        return [
            'sort' => ['except' => 'popular_desc'],
            'perPage' => ['except' => self::DEFAULT_PER_PAGE],
            'filters' => ['except' => []],
        ];
    }

    public function mount(?string $locale = null): void
    {
        $this->locale = Locales::resolve($locale);
        $this->sort = $this->normalizeSort((string) request()->input('sort', $this->sort));
        $this->perPage = $this->normalizePerPage((int) request()->input('perPage', $this->perPage));
        $this->filters = $this->normalizeFilters((array) request()->input('filters', $this->filters));
    }

    public function loadMore(): void
    {
        $this->perPage = $this->normalizePerPage($this->perPage + self::LOAD_MORE_STEP);
    }

    public function updatedSort(string $value): void
    {
        $this->sort = $this->normalizeSort($value);
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $normalized = $this->normalizeFilters($this->filters);

        if ($normalized !== $this->filters) {
            $this->filters = $normalized;
        }

        $this->resetPage();
    }

    public function setSort(string $sort): void
    {
        $this->sort = $this->normalizeSort($sort);
        $this->resetPage();
    }

    public function applyFilters(array $filters): void
    {
        $this->filters = $this->normalizeFilters($filters);
        $this->resetPage();
    }

    public function applyPriceRange(int|string|null $from = null, int|string|null $to = null): void
    {
        $bounds = $this->defaultPriceRange();
        $min = (int) ($bounds['min'] ?? 0);
        $max = (int) ($bounds['max'] ?? 1000);

        $from = max($min, min($max, (int) ($from ?? $min)));
        $to = max($min, min($max, (int) ($to ?? $max)));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from === $min && $to === $max) {
            unset($this->filters['price']);
        } else {
            $this->filters['price'] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->resetPage();
    }

    public function removePriceFilter(): void
    {
        unset($this->filters['price']);
        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeCategoryFilter(string|int $category): void
    {
        $categories = collect((array) ($this->filters['categories'] ?? []))
            ->reject(fn ($value) => (string) $value === (string) $category)
            ->values()
            ->all();

        if ($categories === []) {
            unset($this->filters['categories']);
        } else {
            $this->filters['categories'] = $categories;
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeCategoryFilterAtIndex(int $index): void
    {
        unset($this->filters['categories'][$index]);
        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeAttributeFilter(string $attributeCode, string $value): void
    {
        $selectedValues = collect((array) data_get($this->filters, "attributes.{$attributeCode}", []))
            ->reject(fn ($selectedValue) => (string) $selectedValue === $value)
            ->values()
            ->all();

        if ($selectedValues === []) {
            unset($this->filters['attributes'][$attributeCode]);
        } else {
            $this->filters['attributes'][$attributeCode] = $selectedValues;
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeAttributeFilterAtIndex(string $attributeCode, int $index): void
    {
        unset($this->filters['attributes'][$attributeCode][$index]);
        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function toggleAttributeFilter(string $attributeCode, string $value, bool $checked): void
    {
        $selectedValues = collect((array) data_get($this->filters, "attributes.{$attributeCode}", []))
            ->map(fn ($selectedValue) => (string) $selectedValue);

        if ($checked) {
            $selectedValues->push($value);
        } else {
            $selectedValues = $selectedValues
                ->reject(fn ($selectedValue) => $selectedValue === $value);
        }

        $selectedValues = $selectedValues
            ->unique()
            ->values()
            ->all();

        if ($selectedValues === []) {
            unset($this->filters['attributes'][$attributeCode]);
        } else {
            $this->filters['attributes'][$attributeCode] = $selectedValues;
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function render(CatalogProductListQuery $products, CatalogFiltersQuery $filters): View
    {
        // Get sale products (products with old_price > 0)
        $saleProducts = $this->getSaleProducts($products);

        $filterOptions = $filters->handle($this->locale);

        $archiveTitle = __('Розпродаж');

        return view('shophats::pages.sale-products', [
            'products' => $saleProducts,
            'filterOptions' => $filterOptions,
            'archiveTitle' => $archiveTitle,
            'currentSort' => $this->sort,
            'currentFilters' => $this->filters,
        ])->layout('shophats::layouts.base', [
            'title' => $archiveTitle,
            'meta_description' => __('Розпродаж головних уборів ShopHats - шапки, кепки, берети, шарфи та рукавички зі знижками.'),
        ]);
    }

    private function getSaleProducts(CatalogProductListQuery $products)
    {
        $query = Product::query()
            ->where('products.status', 'published')
            ->whereHas('variants', function ($query) {
                $query->where('old_price', '>', 0);
            })
            ->withTranslationsFor($this->locale)
            ->with([
                'brand:id,name',
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'images:id,product_id,path,alt,sort',
                'categories' => fn ($query) => $query->withTranslationsFor($this->locale),
                'attributeValues.attribute' => fn ($query) => $query->withTranslationsFor($this->locale),
                'attributeValues.option' => fn ($query) => $query->withTranslationsFor($this->locale),
                'variants' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
            ]);

        // Apply sorting
        $query = $this->applySorting($query, $this->sort);

        // Apply filters
        $query = $this->applyFiltersToQuery($query, $this->filters);

        // Paginate
        $products = $query->paginate($this->perPage);

        // Transform to DTO
        return $products->through(fn (Product $product) => $this->toData($product, $this->locale));
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

    private function applyFiltersToQuery($query, array $filters)
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

        return $query;
    }

    private function toData(Product $product, string $locale)
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

        return new \Commero\Application\Catalog\DTOs\CatalogProductCardData(
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

    private function normalizeSort(string $sort): string
    {
        return in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'popular_desc';
    }

    private function normalizePerPage(int $perPage): int
    {
        return max(self::DEFAULT_PER_PAGE, $perPage);
    }

    private function normalizeFilters(array $filters): array
    {
        $normalized = $filters;

        if (isset($normalized['price']) && is_array($normalized['price'])) {
            $price = $normalized['price'];
            $from = data_get($price, 'from');
            $to = data_get($price, 'to');

            if ($from === '' || $from === null) {
                unset($price['from']);
            } else {
                $price['from'] = (int) $from;
            }

            if ($to === '' || $to === null) {
                unset($price['to']);
            } else {
                $price['to'] = (int) $to;
            }

            if ($price === []) {
                unset($normalized['price']);
            } else {
                $normalized['price'] = $price;
            }
        }

        if (isset($normalized['categories'])) {
            $normalized['categories'] = collect((array) $normalized['categories'])
                ->filter(fn ($value) => filled($value) || $value === '0' || $value === 0)
                ->map(fn ($value) => is_numeric($value) ? (int) $value : (string) $value)
                ->unique()
                ->values()
                ->all();

            if ($normalized['categories'] === []) {
                unset($normalized['categories']);
            }
        }

        if (isset($normalized['attributes']) && is_array($normalized['attributes'])) {
            foreach ($normalized['attributes'] as $attributeCode => $values) {
                $attributeValues = collect((array) $values)
                    ->filter(fn ($value) => filled($value) || $value === '0' || $value === 0)
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->values()
                    ->all();

                if ($attributeValues === []) {
                    unset($normalized['attributes'][$attributeCode]);
                    continue;
                }

                $normalized['attributes'][$attributeCode] = $attributeValues;
            }

            if (($normalized['attributes'] ?? []) === []) {
                unset($normalized['attributes']);
            }
        }

        return $normalized;
    }

    private function defaultPriceRange(): array
    {
        return app(CatalogFiltersQuery::class)->handle($this->locale)['price'] ?? [
            'min' => 0,
            'max' => 1000,
        ];
    }
}
