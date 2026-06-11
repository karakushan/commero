<?php

namespace Commero\Services;

use Commero\Models\Currency;
use Commero\Models\Product;
use Commero\Models\ProductVariant;
use Commero\Support\Locales;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart.items';

    private const DEFAULT_CURRENCY_SYMBOL = '₴';

    public function getCart(?Product $recommendedProduct = null, ?string $lastAddedLineId = null): array
    {
        $items = [];
        $totalNumeric = 0.0;

        foreach ($this->sessionItems() as $lineId => $line) {
            $quantity = max(0, (int) ($line['quantity'] ?? 0));

            if ($quantity <= 0) {
                continue;
            }

            $variant = ProductVariant::query()
                ->with([
                    'product.primaryImage:id,product_id,path,alt,is_primary,sort',
                    'product.translations',
                    'attributeValues.attribute.translations',
                    'attributeValues.option.translations',
                ])
                ->find($line['variant_id'] ?? null);

            $sessionProduct = is_array($line['product'] ?? null) ? $line['product'] : [];
            $sessionVariant = is_array($line['variant'] ?? null) ? $line['variant'] : [];
            $product = $variant?->product;

            if (! $variant instanceof ProductVariant || ! $product instanceof Product) {
                continue;
            }

            if (
                $product->status !== 'published'
                || $product->effectiveStockStatus() !== 'in_stock'
                || $variant->status !== 'in_stock'
            ) {
                continue;
            }

            ['price' => $unitPrice, 'old_price' => $oldPrice] = $this->resolveDisplayPrices($variant);
            $lineTotal = $unitPrice * $quantity;
            $totalNumeric += $lineTotal;

            $items[] = [
                'line_id' => (string) $lineId,
                'product' => [
                    'id' => $product->id,
                    'name' => $this->productName($product),
                    'url' => $this->productUrl($product),
                    'image' => filled($product->primaryImage?->path)
                        ? Storage::disk('public')->url($product->primaryImage->path)
                        : ($sessionProduct['image'] ?? asset('images/shophats/products/placeholder.jpg')),
                    'sku' => $product->sku ?? ($sessionProduct['sku'] ?? ''),
                ],
                'variant' => [
                    'id' => $variant->id,
                    'name' => $variant->name ?: ($sessionVariant['name'] ?? __('Default')),
                    'sku' => $variant->sku ?? ($sessionVariant['sku'] ?? ''),
                    'attributes' => $this->variantAttributes($variant),
                ],
                'quantity' => $quantity,
                'unit_price' => $this->formatMoney($unitPrice),
                'unit_price_numeric' => normalize_price($unitPrice),
                'old_price_numeric' => $oldPrice !== null ? normalize_price($oldPrice) : null,
                'total' => $this->formatMoney($lineTotal),
            ];
        }

        if (count($items) !== count($this->sessionItems())) {
            $this->syncSessionItems($items);
        }

        return [
            'count' => array_sum(array_column($items, 'quantity')),
            'items' => array_values($items),
            'total' => $this->formatMoney($totalNumeric),
            'total_numeric' => normalize_price($totalNumeric),
            'last_added_line_id' => $lastAddedLineId,
            'recommended_products' => $this->resolveRecommendedProducts($recommendedProduct),
        ];
    }

    public function count(): int
    {
        return (int) collect($this->sessionItems())
            ->sum(fn (array $item): int => max(0, (int) ($item['quantity'] ?? 0)));
    }

    public function add(int $productId, ?int $variantId = null, int $qty = 1): array
    {
        $product = Product::query()
            ->with(['variants'])
            ->find($productId);

        if (! $product instanceof Product || $product->status !== 'published') {
            throw ValidationException::withMessages([
                'product_id' => __('Product was not found.'),
            ]);
        }

        if ($product->effectiveStockStatus() !== 'in_stock') {
            throw ValidationException::withMessages([
                'product_id' => __('This product is currently unavailable.'),
            ]);
        }

        $variant = $variantId
            ? $product->variants->firstWhere('id', $variantId)
            : $product->variants->first();

        if (! $variant instanceof ProductVariant) {
            throw ValidationException::withMessages([
                'variant_id' => __('Product variant was not found.'),
            ]);
        }

        if ($variant->status !== 'in_stock') {
            throw ValidationException::withMessages([
                'variant_id' => __('This product variant is currently unavailable.'),
            ]);
        }

        $quantity = max(1, $qty);
        $items = $this->sessionItems();
        $lineId = $this->lineId($product->id, $variant->id);
        $existingQuantity = (int) ($items[$lineId]['quantity'] ?? 0);

        $items[$lineId] = [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => $existingQuantity + $quantity,
            'product' => [
                'id' => $product->id,
                'name' => $this->productName($product),
                'url' => $this->productUrl($product),
                'image' => filled($product->primaryImage?->path)
                    ? Storage::disk('public')->url($product->primaryImage->path)
                    : asset('images/shophats/products/placeholder.jpg'),
                'sku' => $product->sku ?? '',
            ],
            'variant' => [
                'id' => $variant->id,
                'name' => $variant->name ?: __('Default'),
                'sku' => $variant->sku ?? '',
                'attributes' => $this->variantAttributes(
                    $variant->loadMissing([
                        'attributeValues.attribute.translations',
                        'attributeValues.option.translations',
                    ])
                ),
            ],
            ...$this->serializeDisplayPrices($variant),
        ];

        session()->put(self::SESSION_KEY, $items);

        return $this->getCart($product, $lineId);
    }

    public function update(string $lineId, int $qty): array
    {
        $items = $this->sessionItems();

        if (! array_key_exists($lineId, $items)) {
            throw ValidationException::withMessages([
                'line_id' => __('Cart item was not found.'),
            ]);
        }

        if ($qty <= 0) {
            unset($items[$lineId]);
        } else {
            $items[$lineId]['quantity'] = $qty;
        }

        session()->put(self::SESSION_KEY, $items);

        return $this->getCart();
    }

    public function remove(string $lineId): array
    {
        $items = $this->sessionItems();
        unset($items[$lineId]);
        session()->put(self::SESSION_KEY, $items);

        return $this->getCart();
    }

    public function clear(): array
    {
        session()->forget(self::SESSION_KEY);

        return $this->getCart();
    }

    private function sessionItems(): array
    {
        $items = session()->get(self::SESSION_KEY, []);

        return is_array($items) ? $items : [];
    }

    private function lineId(int $productId, int $variantId): string
    {
        return sprintf('%d:%d', $productId, $variantId);
    }

    private function formatMoney(float $amount): string
    {
        return format_money_amount($amount, current_currency()?->symbol ?? self::DEFAULT_CURRENCY_SYMBOL);
    }

    /**
     * @return array{price: float, old_price: ?float}
     */
    private function resolveDisplayPrices(ProductVariant $variant): array
    {
        $currency = current_currency();

        if (! $currency instanceof Currency || $currency->is_base) {
            return [
                'price' => normalize_price($variant->price),
                'old_price' => $variant->old_price !== null ? normalize_price($variant->old_price) : null,
            ];
        }

        if ($variant->multi_currency_code === $currency->code && $variant->multi_currency_price !== null) {
            return [
                'price' => normalize_price($variant->multi_currency_price),
                'old_price' => $variant->multi_currency_old_price !== null
                    ? normalize_price($variant->multi_currency_old_price)
                    : null,
            ];
        }

        return [
            'price' => normalize_price(convert_price((float) $variant->price)),
            'old_price' => $variant->old_price !== null
                ? normalize_price(convert_price((float) $variant->old_price))
                : null,
        ];
    }

    /**
     * @return array{unit_price_numeric: float, old_price_numeric: ?float}
     */
    private function serializeDisplayPrices(ProductVariant $variant): array
    {
        ['price' => $price, 'old_price' => $oldPrice] = $this->resolveDisplayPrices($variant);

        return [
            'unit_price_numeric' => normalize_price($price),
            'old_price_numeric' => $oldPrice !== null ? normalize_price($oldPrice) : null,
        ];
    }

    private function productName(Product $product): string
    {
        return $product->translation(App::getLocale())?->name
            ?? $product->sku
            ?? __('Product');
    }

    private function productUrl(Product $product): string
    {
        $slug = $product->localizedSlug(App::getLocale(), $product->sku) ?? $product->sku;
        $routeName = Locales::isDefault(App::getLocale())
            ? 'product.show'
            : 'localized.product.show';
        $params = Locales::isDefault(App::getLocale())
            ? ['slug' => $slug]
            : ['locale' => App::getLocale(), 'slug' => $slug];

        return route($routeName, $params);
    }

    private function syncSessionItems(array $hydratedItems): void
    {
        $items = [];

        foreach ($hydratedItems as $item) {
            $items[$item['line_id']] = [
                'product_id' => $item['product']['id'],
                'variant_id' => $item['variant']['id'],
                'quantity' => $item['quantity'],
                'product' => $item['product'],
                'variant' => $item['variant'],
                'unit_price_numeric' => $item['unit_price_numeric'] ?? null,
                'old_price_numeric' => $item['old_price_numeric'] ?? null,
            ];
        }

        session()->put(self::SESSION_KEY, $items);
    }

    private function variantAttributes(ProductVariant $variant): array
    {
        return $variant->attributeValues
            ->map(function ($value): ?array {
                $attributeName = $value->attribute?->translation(App::getLocale())?->name;
                $optionLabel = $value->option?->translation(App::getLocale())?->label ?? $value->option?->value;

                if (! filled($attributeName) || ! filled($optionLabel)) {
                    return null;
                }

                return [
                    'attribute' => $attributeName,
                    'value' => $optionLabel,
                    'label' => $attributeName.': '.$optionLabel,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveRecommendedProducts(?Product $product = null, int $limit = 8): array
    {
        $locale = App::getLocale();

        $query = Product::query()
            ->where('products.status', 'published')
            ->whereHas('variants', fn ($variantsQuery) => $variantsQuery->where('status', 'in_stock'))
            ->withTranslationsFor($locale)
            ->with([
                'primaryImage:id,product_id,path,alt,is_primary,sort',
                'translations',
                'variants',
            ]);

        if ($product instanceof Product) {
            $product->loadMissing('categories');

            $query->whereKeyNot($product->id);
            $categoryIds = $product->categories->pluck('id')->all();

            if ($categoryIds !== []) {
                $categoryProducts = (clone $query)
                    ->whereHas('categories', fn ($categoriesQuery) => $categoriesQuery->whereIn('categories.id', $categoryIds))
                    ->latest('products.id')
                    ->limit($limit)
                    ->get();

                if ($categoryProducts->isNotEmpty()) {
                    return $this->mapRecommendedProducts($categoryProducts);
                }
            }
        }

        return $this->mapRecommendedProducts(
            $query
                ->latest('products.id')
                ->limit($limit)
                ->get()
        );
    }

    private function mapRecommendedProducts($products): array
    {
        return $products
            ->map(fn (Product $product): array => $this->mapRecommendedProduct($product))
            ->all();
    }

    private function mapRecommendedProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $this->productName($product),
            'url' => $this->productUrl($product),
            'image' => filled($product->primaryImage?->path)
                ? Storage::disk('public')->url($product->primaryImage->path)
                : asset('images/shophats/products/placeholder.jpg'),
        ];
    }
}
