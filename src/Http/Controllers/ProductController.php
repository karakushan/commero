<?php

namespace Commero\Http\Controllers;

use Commero\Http\Requests\StoreProductReviewRequest;
use Commero\Models\Product;
use Commero\Models\ProductReview;
use Commero\Models\ProductViewSession;
use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Show single product page
     */
    public function show(\Illuminate\Http\Request $request, LocalizedSeoResolver $seoResolver)
    {
        $locale = App::getLocale();
        $slug = (string) $request->route('slug');

        // Find product by slug in translations
        $product = Product::query()
            ->with([
                'primaryImage',
                'images',
                'variants.attributeValues.attribute',
                'variants.attributeValues.option',
                'brand',
                'categories',
                'attributeValues.attribute',
                'attributeValues.option',
                'faqs',
                'translations',
            ])
            ->whereLocalizedSlug($slug, $locale)
            ->firstOrFail();

        $this->trackProductView($product);

        $translation = $product->translation($locale);
        $primaryVariant = $product->variants->first();
        $purchasesCount = $product->orderItems()
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', ['cancelled', 'returned']))
            ->sum('quantity');
        $currentViewersCount = ProductViewSession::query()
            ->where('product_id', $product->id)
            ->where('last_seen_at', '>=', now()->subMinutes(10))
            ->count();

        // Build gallery images array with proper URLs
        $galleryImages = [];
        foreach ($product->images as $image) {
            $imageUrl = Storage::disk('public')->url($image->path);
            $galleryImages[] = [
                'id' => $image->id,
                'full' => $imageUrl,
                'thumb' => $imageUrl,
            ];
        }

        // Build attributes list
        $attributes = $product->attributeValues
            ->map(function ($value) use ($locale): ?array {
                $attributeName = $value->attribute?->translation($locale)?->name;
                $attributeValue = $this->resolveAttributeValue($value, $locale);

                if (! filled($attributeName) || ! filled($attributeValue)) {
                    return null;
                }

                return [
                    'code' => $value->attribute?->code,
                    'name' => $attributeName,
                    'value' => $attributeValue,
                    'is_priority' => (bool) $value->is_priority,
                ];
            })
            ->filter()
            ->values()
            ->all();
        $priorityAttributes = collect($attributes)
            ->filter(fn (array $attribute): bool => (bool) ($attribute['is_priority'] ?? false))
            ->values()
            ->all();

        // Prepare product data
        $visibleReviews = $this->resolveVisibleReviews($product);

        $productData = [
            'id' => $product->id,
            'name' => $translation?->name ?? 'Product',
            'slug' => $product->localizedSlug($locale, $product->sku) ?? '',
            'sku' => $product->sku ?? '',
            'description' => $translation?->description ?? '',
            'full_description' => $translation?->full_description ?? '',
            'price' => $primaryVariant?->price ?? 0,
            'old_price' => $primaryVariant?->old_price ?? null,
            'image' => $product->primaryImage
                ? Storage::disk('public')->url($product->primaryImage->path)
                : asset('images/shophats/products/placeholder.jpg'),
            'gallery' => $galleryImages,
            'stock_status' => $product->effectiveStockStatus(),
            'is_on_sale' => (bool) $product->is_on_sale,
            'views_count' => (int) $product->views_count,
            'purchases_count' => (int) $purchasesCount,
            'current_viewers_count' => (int) $currentViewersCount,
            'attributes' => $attributes,
            'about_attributes' => $priorityAttributes !== [] ? $priorityAttributes : $attributes,
            'variant_options' => $this->resolveVariantOptions($product, $locale),
            'brand' => $product->brand?->name,
            'categories' => $product->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->translation($locale)?->name,
                'slug' => $category->localizedSlug($locale, $category->path),
            ])->filter(fn (array $category) => filled($category['name'] ?? null))->values()->all(),
            'color_related_products' => $this->resolveColorRelatedProducts($product, $locale),
            'faqs' => $this->resolveFaqs($product, $locale),
            'reviews' => $this->mapReviews($visibleReviews),
            'reviews_count' => $visibleReviews->count(),
            'reviews_average_rating' => round((float) $visibleReviews->avg('rating'), 1),
        ];

        $completeYourLookProducts = $this->resolveBoughtTogetherProducts($product, $locale);

        if ($completeYourLookProducts === []) {
            $completeYourLookProducts = $this->resolveLatestProducts($product, $locale);
        }

        $similarProducts = $this->resolveSimilarProducts($product, $locale);
        $seo = $seoResolver->forTranslatedContent(
            locale: $locale,
            translations: $product->translations,
            urlForLocale: fn (string $supportedLocale): ?string => $this->resolveProductUrl($product, $supportedLocale),
            fallback: [
                'title' => $translation?->name ?? __('Product'),
                'heading' => $translation?->name ?? __('Product'),
                'description' => $translation?->meta_description ?? $translation?->description,
            ],
            availableLocales: $product->translations->pluck('locale')->all(),
        );

        return view('shophats::pages.single-product', [
            'product' => $productData,
            'locale' => $locale,
            'seo' => $seo,
            'completeYourLookProducts' => $completeYourLookProducts,
            'similarProducts' => $similarProducts,
        ]);
    }

    public function storeReview(StoreProductReviewRequest $request): RedirectResponse
    {
        $locale = Locales::resolve($request->route('locale'));
        $slug = (string) $request->route('slug');
        $product = Product::query()
            ->whereLocalizedSlug($slug, $locale)
            ->firstOrFail();

        $validated = $request->validated();

        DB::transaction(function () use ($product, $validated, $request, $locale): void {
            $review = ProductReview::query()->create([
                'product_id' => $product->id,
                'user_id' => $request->user()?->id,
                'locale' => $validated['locale'] ?? $locale,
                'display_name' => $validated['display_name'],
                'email' => $request->user()?->email ?? ($validated['email'] ?? null),
                'author_type' => $request->user() ? 'user' : 'guest',
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'comment' => $validated['comment'],
                'status' => 'pending',
            ]);

            foreach ($request->file('photos', []) as $index => $photo) {
                $review->images()->create([
                    'path' => $photo->store('reviews/photos', 'public'),
                    'alt' => $validated['photo_alts'][$index] ?? null,
                    'sort' => $index,
                ]);
            }
        });

        return redirect(Locales::path('/product/'.$slug, $locale).'#reviews')
            ->with('review_submitted', __('Review submitted and awaits moderation.'));
    }

    private function resolveLatestProducts(Product $product, string $locale, int $limit = 8): array
    {
        $latestProducts = Product::query()
            ->where('products.status', 'published')
            ->whereKeyNot($product->id)
            ->withTranslationsFor($locale)
            ->with([
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'images:id,product_id,path,alt,sort',
                'categories' => fn ($query) => $query->withTranslationsFor($locale),
                'variants',
            ])
            ->latest('products.id')
            ->limit($limit)
            ->get();

        return $this->mapProductCards($latestProducts, $locale);
    }

    private function resolveBoughtTogetherProducts(Product $product, string $locale, int $limit = 8): array
    {
        $products = $product->boughtTogetherProducts()
            ->where('products.status', 'published')
            ->withTranslationsFor($locale)
            ->with([
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'images:id,product_id,path,alt,sort',
                'categories' => fn ($query) => $query->withTranslationsFor($locale),
                'variants',
            ])
            ->limit($limit)
            ->get();

        return $this->mapProductCards($products, $locale);
    }

    private function resolveColorRelatedProducts(Product $product, string $locale): array
    {
        $currentOption = $this->mapProductColorOption($product, $locale, true);

        $relatedOptions = $product->colorRelatedProducts()
            ->where('products.status', 'published')
            ->withTranslationsFor($locale)
            ->with([
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'variants',
            ])
            ->get()
            ->map(fn (Product $relatedProduct): array => $this->mapProductColorOption($relatedProduct, $locale))
            ->all();

        return collect([$currentOption, ...$relatedOptions])
            ->unique('id')
            ->values()
            ->all();
    }

    private function mapProductColorOption(Product $product, string $locale, bool $isCurrent = false): array
    {
        $translation = $product->translation($locale);
        $primaryVariant = $product->variants->first();

        return [
            'id' => $product->id,
            'name' => $translation?->name ?? $product->sku ?? __('Product'),
            'url' => $this->resolveProductUrl($product, $locale),
            'price' => $primaryVariant?->price ?? 0,
            'old_price' => $primaryVariant?->old_price,
            'image' => $product->primaryImage
                ? Storage::disk('public')->url($product->primaryImage->path)
                : asset('images/shophats/products/placeholder.jpg'),
            'is_current' => $isCurrent,
        ];
    }

    private function resolveProductUrl(Product $product, string $locale): string
    {
        $slug = $product->localizedSlug($locale);

        if (! filled($slug)) {
            return route('catalog.index');
        }

        return Locales::isDefault($locale)
            ? route('product.show', ['slug' => $slug])
            : route('localized.product.show', ['locale' => $locale, 'slug' => $slug]);
    }

    private function resolveSimilarProducts(Product $product, string $locale, int $limit = 8): array
    {
        $categoryLevels = [];

        foreach ($product->categories as $category) {
            $currentCategory = $category;
            $level = 0;

            while ($currentCategory) {
                $categoryLevels[$level] ??= [];

                if (! in_array($currentCategory->id, $categoryLevels[$level], true)) {
                    $categoryLevels[$level][] = $currentCategory->id;
                }

                $currentCategory = $currentCategory->parent()->first();
                $level++;
            }
        }

        foreach ($categoryLevels as $categoryIds) {
            $relatedProducts = Product::query()
                ->where('products.status', 'published')
                ->whereKeyNot($product->id)
                ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds))
                ->withTranslationsFor($locale)
                ->with([
                    'primaryImage:id,product_id,path,alt,is_primary,sort',
                    'images:id,product_id,path,alt,sort',
                    'categories' => fn ($query) => $query->withTranslationsFor($locale),
                    'variants',
                ])
                ->latest('products.id')
                ->distinct('products.id')
                ->limit($limit)
                ->get();

            if ($relatedProducts->isNotEmpty()) {
                return $this->mapProductCards($relatedProducts, $locale);
            }
        }

        return [];
    }

    private function resolveAttributeValue($value, string $locale): ?string
    {
        $optionTranslation = $value->option?->translation($locale);
        $resolved = $optionTranslation?->label
            ?? $value->option?->value
            ?? $value->value_string
            ?? $value->value_integer
            ?? $value->value_numeric;

        if ($resolved !== null && $resolved !== '') {
            return (string) $resolved;
        }

        if (! is_null($value->value_boolean)) {
            return $value->value_boolean ? __('Yes') : __('No');
        }

        if (is_array($value->value_json) && $value->value_json !== []) {
            return collect($value->value_json)
                ->flatten()
                ->filter(fn ($item) => filled($item))
                ->map(fn ($item) => (string) $item)
                ->implode(', ');
        }

        return null;
    }

    private function resolveVariantOptions(Product $product, string $locale): array
    {
        $variants = $product->variants
            ->sort(function ($left, $right): int {
                $leftAvailability = $left->status === 'in_stock' ? 0 : 1;
                $rightAvailability = $right->status === 'in_stock' ? 0 : 1;

                return [$leftAvailability, $left->sort ?? PHP_INT_MAX, $left->id]
                    <=> [$rightAvailability, $right->sort ?? PHP_INT_MAX, $right->id];
            })
            ->values();

        if ($variants->count() <= 1) {
            return [];
        }

        $firstAttributeName = $variants
            ->flatMap(fn ($variant) => $variant->attributeValues)
            ->first()?->attribute?->translation($locale)?->name;

        $items = $variants
            ->map(function ($variant) use ($locale): array {
                $attributeLabel = $variant->attributeValues
                    ->map(fn ($value): ?string => $this->resolveAttributeValue($value, $locale))
                    ->filter()
                    ->implode(' / ');

                return [
                    'id' => $variant->id,
                    'label' => filled($attributeLabel) ? $attributeLabel : ($variant->name ?: $variant->sku),
                    'is_available' => $variant->status === 'in_stock',
                ];
            })
            ->filter(fn (array $variant): bool => filled($variant['label']))
            ->values();

        return [
            'label' => filled($firstAttributeName) ? $firstAttributeName : __('Size, cm'),
            'selected_id' => $items->firstWhere('is_available', true)['id'] ?? $items->first()['id'] ?? null,
            'items' => $items->all(),
        ];
    }

    private function mapProductCards(EloquentCollection $products, string $locale): array
    {
        return $products
            ->map(fn (Product $product) => $this->mapProductCard($product, $locale))
            ->all();
    }

    private function mapProductCard(Product $product, string $locale): array
    {
        $translation = $product->translation($locale);
        $primaryVariant = $product->variants->first();
        $gallery = [];

        foreach ($product->images as $image) {
            $imageUrl = Storage::disk('public')->url($image->path);

            $gallery[] = [
                'id' => $image->id,
                'full' => $imageUrl,
                'thumb' => $imageUrl,
            ];
        }

        if ($gallery === [] && $product->primaryImage) {
            $imageUrl = Storage::disk('public')->url($product->primaryImage->path);

            $gallery[] = [
                'id' => $product->primaryImage->id,
                'full' => $imageUrl,
                'thumb' => $imageUrl,
            ];
        }

        return [
            'id' => $product->id,
            'name' => $translation?->name ?? $product->sku,
            'slug' => $product->localizedSlug($locale, $product->sku) ?? $product->sku,
            'sku' => $product->sku,
            'price' => $primaryVariant?->price ?? 0,
            'old_price' => $primaryVariant?->old_price,
            'image' => $product->primaryImage
                ? Storage::disk('public')->url($product->primaryImage->path)
                : asset('images/shophats/products/placeholder.jpg'),
            'gallery' => $gallery,
            'stock_status' => $product->effectiveStockStatus(),
            'categories' => $product->categories
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->translation($locale)?->name,
                    'slug' => $category->localizedSlug($locale, $category->path),
                ])
                ->filter(fn (array $category) => filled($category['name'] ?? null))
                ->values()
                ->all(),
        ];
    }

    private function mapReviews(Collection $reviews): array
    {
        return $reviews->map(function (ProductReview $review): array {
            $reply = $review->children->first();

            return [
                'id' => $review->id,
                'display_name' => $review->display_name,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'images' => $review->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => Storage::disk('public')->url($image->path),
                    'alt' => $image->alt,
                ])->all(),
                'reply' => $reply ? [
                    'display_name' => $reply->display_name,
                    'comment' => $reply->comment,
                    'created_at' => $reply->created_at,
                ] : null,
            ];
        })->all();
    }

    private function resolveVisibleReviews(Product $product): EloquentCollection
    {
        return ProductReview::query()
            ->where('product_id', $product->id)
            ->roots()
            ->where(function ($query): void {
                $query
                    ->approved()
                    ->orWhereHas('children', fn ($childrenQuery) => $childrenQuery->approved());
            })
            ->with([
                'images',
                'children' => fn ($childrenQuery) => $childrenQuery->approved()->orderBy('created_at'),
            ])
            ->latest()
            ->get();
    }

    private function trackProductView(Product $product): void
    {
        if (! request()->hasSession()) {
            return;
        }

        $session = request()->session();
        $sessionId = $session->getId();

        ProductViewSession::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'session_id' => $sessionId,
            ],
            [
                'last_seen_at' => now(),
            ],
        );

        $sessionKey = "product_viewed.{$product->id}";

        if (! $session->has($sessionKey)) {
            $product->increment('views_count');
            $product->refresh();
            $session->put($sessionKey, now()->timestamp);
        }
    }

    private function resolveFaqs(Product $product, string $locale): array
    {
        $preferredLocales = Locales::preferred($locale);
        $faqs = $product->faqs instanceof Collection ? $product->faqs : collect($product->faqs);

        foreach ($preferredLocales as $preferredLocale) {
            $localizedFaqs = $faqs
                ->where('locale', $preferredLocale)
                ->sortBy('sort')
                ->values()
                ->map(fn ($faq) => [
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])
                ->all();

            if ($localizedFaqs !== []) {
                return $localizedFaqs;
            }
        }

        return [];
    }
}
