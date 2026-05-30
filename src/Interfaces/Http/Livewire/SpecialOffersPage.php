<?php

namespace Commero\Interfaces\Http\Livewire;

use Commero\Application\Catalog\DTOs\CatalogProductCardData;
use Commero\Models\Product;
use Commero\Support\Locales;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SpecialOffersPage extends Component
{
    use WithPagination;

    private const DEFAULT_PER_PAGE = 12;
    private const LOAD_MORE_STEP = 3;

    public string $locale;
    public string $offerType = 'all';
    public int $perPage = self::DEFAULT_PER_PAGE;

    protected $queryString = [
        'offerType' => ['except' => 'all'],
        'perPage' => ['except' => self::DEFAULT_PER_PAGE],
    ];

    public function mount(?string $locale = null): void
    {
        $this->locale = \Commero\Support\Locales::resolve($locale);
        $this->offerType = $this->normalizeOfferType((string) request()->input('offerType', $this->offerType));
    }

    public function loadMore(): void
    {
        $this->perPage = max(self::DEFAULT_PER_PAGE, $this->perPage + self::LOAD_MORE_STEP);
        $this->resetPage();
    }

    public function setOfferType(string $offerType): void
    {
        $this->offerType = $this->normalizeOfferType($offerType);
        $this->resetPage();
    }

    public function render(): View
    {
        $products = $this->getSpecialOfferProducts();
        $offerFilters = $this->getOfferFilters();
        $offerCounts = $this->getOfferCounts();
        $currentFilter = $this->offerType;
        $archiveTitle = __('Special Offers');

        return view('shophats::pages.special-offers', [
            'products' => $products,
            'offerFilters' => $offerFilters,
            'offerCounts' => $offerCounts,
            'currentFilter' => $currentFilter,
            'archiveTitle' => $archiveTitle,
        ])->layout('shophats::layouts.base', [
            'title' => $archiveTitle,
            'meta_description' => __('Special offers and promotions on hats, caps, and accessories at ShopHats.'),
        ]);
    }

    private function getSpecialOfferProducts()
    {
        $query = Product::query()
            ->where('products.status', 'published')
            ->withTranslationsFor($this->locale)
            ->with([
                'brand:id,name',
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'images:id,product_id,path,alt,sort',
                'categories' => fn ($query) => $query->withTranslationsFor($this->locale),
                'attributeValues.attribute' => fn ($query) => $query->withTranslationsFor($this->locale),
                'attributeValues.option' => fn ($query) => $query->withTranslationsFor($this->locale),
                'variants',
            ]);

        // Apply offer type filter
        $query = $this->applyOfferTypeFilter($query, $this->offerType);

        // Order by ID descending (newest first)
        $query->orderByDesc('products.id');

        // Paginate
        $products = $query->paginate($this->perPage);

        // Transform to DTO
        return $products->through(fn (Product $product) => $this->toData($product, $this->locale));
    }

    private function applyOfferTypeFilter($query, string $offerType)
    {
        return match ($offerType) {
            'promotion' => $query->where('products.is_on_sale', true),
            'top' => $query->where('products.is_hit_sales', true),
            'novelty' => $query->where('products.is_new', true),
            'all' => $query->where(function ($q) {
                $q->where('products.is_on_sale', true)
                    ->orWhere('products.is_hit_sales', true)
                    ->orWhere('products.is_new', true);
            }),
            default => $query->where(function ($q) {
                $q->where('products.is_on_sale', true)
                    ->orWhere('products.is_hit_sales', true)
                    ->orWhere('products.is_new', true);
            }),
        };
    }

    private function getOfferFilters(): array
    {
        return [
            'all' => [
                'label' => __('All special offers'),
                'short' => __('All'),
            ],
            'promotion' => [
                'label' => __('Sale'),
                'short' => __('Sale'),
            ],
            'top' => [
                'label' => __('Bestsellers'),
                'short' => __('Bestsellers'),
            ],
            'novelty' => [
                'label' => __('New arrivals'),
                'short' => __('New'),
            ],
        ];
    }

    private function getOfferCounts(): array
    {
        $baseQuery = Product::query()
            ->where('products.status', 'published');

        return [
            'all' => (clone $baseQuery)
                ->where(function ($q) {
                    $q->where('products.is_on_sale', true)
                        ->orWhere('products.is_hit_sales', true)
                        ->orWhere('products.is_new', true);
                })
                ->count(),
            'promotion' => (clone $baseQuery)
                ->where('products.is_on_sale', true)
                ->count(),
            'top' => (clone $baseQuery)
                ->where('products.is_hit_sales', true)
                ->count(),
            'novelty' => (clone $baseQuery)
                ->where('products.is_new', true)
                ->count(),
        ];
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

    private function normalizeOfferType(string $offerType): string
    {
        $allowed = ['all', 'promotion', 'top', 'novelty'];
        return in_array($offerType, $allowed, true) ? $offerType : 'all';
    }
}
