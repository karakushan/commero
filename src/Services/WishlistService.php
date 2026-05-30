<?php

namespace Commero\Services;

use Commero\Models\Product;
use Commero\Models\User;
use Commero\Support\Locales;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    private const SESSION_KEY = 'wishlist.product_ids';

    public function getWishlist(): array
    {
        return $this->hydrateWishlist($this->storedProductIds(), Auth::user());
    }

    public function count(): int
    {
        return (int) ($this->getWishlist()['count'] ?? 0);
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->storedProductIds(), true);
    }

    public function add(int $productId): array
    {
        $product = $this->resolveWishlistProduct($productId);

        if (Auth::check() && $this->hasWishlistTable()) {
            Auth::user()?->wishlistItems()->firstOrCreate([
                'product_id' => $product->id,
            ]);
        } else {
            $ids = $this->storedProductIds();

            if (! in_array($product->id, $ids, true)) {
                array_unshift($ids, $product->id);
            }

            $this->replaceStoredProductIds($ids);
        }

        return $this->getWishlist();
    }

    public function remove(int $productId): array
    {
        if (Auth::check() && $this->hasWishlistTable()) {
            Auth::user()?->wishlistItems()->where('product_id', $productId)->delete();
        } else {
            $ids = array_values(array_filter(
                $this->storedProductIds(),
                fn (int $id): bool => $id !== $productId,
            ));

            $this->replaceStoredProductIds($ids);
        }

        return $this->getWishlist();
    }

    public function toggle(int $productId): array
    {
        return $this->has($productId)
            ? $this->remove($productId)
            : $this->add($productId);
    }

    public function clear(): array
    {
        if (Auth::check() && $this->hasWishlistTable()) {
            Auth::user()?->wishlistItems()->delete();
        } else {
            session()->forget(self::SESSION_KEY);
        }

        return $this->getWishlist();
    }

    public function mergeSessionToUser(User $user): array
    {
        if (! $this->hasWishlistTable()) {
            return $this->getWishlist();
        }

        $sessionProductIds = $this->sessionProductIds();

        if ($sessionProductIds === []) {
            return $this->getWishlistForUser($user);
        }

        $validProductIds = Product::query()
            ->whereIn('id', $sessionProductIds)
            ->where('status', 'published')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $existingProductIds = $user->wishlistItems()
            ->whereIn('product_id', $validProductIds)
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $newProductIds = array_values(array_diff($validProductIds, $existingProductIds));

        if ($newProductIds !== []) {
            $now = now();
            $user->wishlistItems()->insert(
                array_map(
                    fn (int $productId): array => [
                        'user_id' => $user->id,
                        'product_id' => $productId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $newProductIds,
                ),
            );
        }

        session()->forget(self::SESSION_KEY);

        return $this->getWishlistForUser($user);
    }

    public function getWishlistForUser(User $user): array
    {
        if (! $this->hasWishlistTable()) {
            return $this->emptyWishlist();
        }

        $sourceIds = $user->wishlistItems()
            ->orderByDesc('created_at')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $this->hydrateWishlist($sourceIds, $user);
    }

    private function resolveWishlistProduct(int $productId): Product
    {
        $product = Product::query()
            ->whereKey($productId)
            ->where('status', 'published')
            ->first();

        if (! $product instanceof Product) {
            throw ValidationException::withMessages([
                'product_id' => __('Product was not found.'),
            ]);
        }

        return $product;
    }

    private function storedProductIds(): array
    {
        if (Auth::check() && $this->hasWishlistTable()) {
            return $this->storedProductIdsForUser(Auth::user());
        }

        return $this->sessionProductIds();
    }

    private function storedProductIdsForUser(?User $user): array
    {
        return $user?->wishlistItems()
            ->orderByDesc('created_at')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all() ?? [];
    }

    private function sessionProductIds(): array
    {
        $ids = session()->get(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($id): int => (int) $id, $ids),
            fn (int $id): bool => $id > 0,
        )));
    }

    private function replaceStoredProductIds(array $productIds): void
    {
        $productIds = array_values(array_unique(array_map(
            fn ($id): int => (int) $id,
            $productIds,
        )));

        if (Auth::check() && $this->hasWishlistTable()) {
            $user = Auth::user();

            if (! $user instanceof User) {
                return;
            }

            $existingIds = $user->wishlistItems()
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $toDelete = array_diff($existingIds, $productIds);
            $toInsert = array_diff($productIds, $existingIds);

            if ($toDelete !== []) {
                $user->wishlistItems()->whereIn('product_id', $toDelete)->delete();
            }

            if ($toInsert !== []) {
                $now = now();

                $user->wishlistItems()->insert(
                    array_map(
                        fn (int $productId): array => [
                            'user_id' => $user->id,
                            'product_id' => $productId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        $toInsert,
                    ),
                );
            }

            return;
        }

        if ($productIds === []) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        session()->put(self::SESSION_KEY, $productIds);
    }

    private function hydrateWishlist(array $sourceIds, ?User $user = null): array
    {
        if ($sourceIds === []) {
            return $this->emptyWishlist();
        }

        $locale = App::getLocale();
        $products = Product::query()
            ->whereIn('id', $sourceIds)
            ->where('status', 'published')
            ->withTranslationsFor($locale)
            ->with([
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'images:id,product_id,path,alt,sort',
                'variants',
            ])
            ->get()
            ->keyBy('id');

        $validIds = [];
        $items = [];

        foreach ($sourceIds as $productId) {
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                continue;
            }

            $validIds[] = $product->id;
            $items[] = $this->mapProductCard($product, $locale);
        }

        if ($validIds !== $sourceIds) {
            if ($user instanceof User) {
                $existingIds = $this->storedProductIdsForUser($user);
                $toDelete = array_diff($existingIds, $validIds);

                if ($toDelete !== []) {
                    $user->wishlistItems()->whereIn('product_id', $toDelete)->delete();
                }
            } else {
                $this->replaceStoredProductIds($validIds);
            }
        }

        return [
            'count' => count($items),
            'items' => $items,
            'product_ids' => $validIds,
        ];
    }

    private function mapProductCard(Product $product, string $locale): array
    {
        $translation = $product->translation($locale);
        $defaultLocale = Locales::default();
        $defaultTranslation = $this->exactTranslation($product, $defaultLocale);
        $primaryVariant = $product->variants->sortBy('sort')->first() ?? $product->variants->first();
        $gallery = [];

        foreach ($product->images as $image) {
            $imageUrl = Storage::disk('public')->url($image->path);

            $gallery[] = [
                'id' => $image->id,
                'full' => $imageUrl,
                'thumb' => $imageUrl,
            ];
        }

        if ($gallery === [] && filled($product->primaryImage?->path)) {
            $imageUrl = Storage::disk('public')->url($product->primaryImage->path);

            $gallery[] = [
                'id' => $product->primaryImage->id,
                'full' => $imageUrl,
                'thumb' => $imageUrl,
            ];
        }

        return [
            'id' => $product->id,
            'name' => $translation?->name ?? $defaultTranslation?->name ?? $product->sku ?? __('Product'),
            'slug' => $translation?->slug ?? $defaultTranslation?->slug ?? $product->sku,
            'sku' => $product->sku,
            'price' => (float) ($primaryVariant?->price ?? 0),
            'old_price' => $primaryVariant?->old_price !== null ? (float) $primaryVariant->old_price : null,
            'image' => filled($product->primaryImage?->path)
                ? Storage::disk('public')->url($product->primaryImage->path)
                : asset('images/shophats/products/placeholder.jpg'),
            'gallery' => $gallery,
            'stock_status' => $product->stock_status ?? 'in_stock',
            'url' => $this->productUrl($product, $locale),
        ];
    }

    private function productUrl(Product $product, string $locale): string
    {
        $localizedSlug = $this->exactTranslation($product, $locale)?->slug;

        if (filled($localizedSlug)) {
            return Locales::isDefault($locale)
                ? route('product.show', ['slug' => $localizedSlug])
                : route('localized.product.show', ['locale' => $locale, 'slug' => $localizedSlug]);
        }

        $defaultLocale = Locales::default();
        $defaultSlug = $this->exactTranslation($product, $defaultLocale)?->slug;

        if (filled($defaultSlug)) {
            return route('product.show', ['slug' => $defaultSlug]);
        }

        $fallbackSlug = $product->translations
            ->pluck('slug')
            ->first(fn (?string $slug): bool => filled($slug));

        return filled($fallbackSlug)
            ? route('product.show', ['slug' => $fallbackSlug])
            : route('home');
    }

    private function exactTranslation(Product $product, string $locale): ?object
    {
        if ($product->relationLoaded('translations')) {
            $loadedTranslation = $product->translations->firstWhere('locale', $locale);

            if ($loadedTranslation) {
                return $loadedTranslation;
            }
        }

        return $product->translations()
            ->where('locale', $locale)
            ->first();
    }

    private function emptyWishlist(): array
    {
        return [
            'count' => 0,
            'items' => [],
            'product_ids' => [],
        ];
    }

    private function hasWishlistTable(): bool
    {
        static $hasWishlistTable;

        return $hasWishlistTable ??= Schema::hasTable('wishlist_items');
    }
}
