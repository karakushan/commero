<?php

namespace Commero\Application\Catalog\Services;

use Commero\Application\Catalog\Support\LocaleCache;
use Commero\Domain\Catalog\Domain\Contracts\ProductRepositoryInterface;
use Commero\Jobs\SendProductBackInStockNotifications;
use Commero\Models\AttributeOption;
use Commero\Models\Product;
use Commero\Models\ProductAttribute;
use Commero\Support\Locales;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly RebuildProductAttributeSnapshot $rebuildSnapshot,
        private readonly LocaleCache $cache,
    ) {}

    public function handle(array $payload, ?Product $product = null): Product
    {
        $this->guardDefaultLocaleTranslation($payload['translations'] ?? []);

        return DB::transaction(function () use ($payload, $product) {
            $product ??= new Product;
            $previousStockStatus = $product->exists ? $product->stock_status : null;

            $product->fill(Arr::only($payload, ['uuid', 'brand_id', 'type', 'status', 'sku', 'stock_status', 'search_text', 'is_hit_sales', 'is_on_sale', 'is_new']));
            $product = $this->products->save($product);

            if (array_key_exists('category_ids', $payload)) {
                $product->categories()->sync($payload['category_ids'] ?? []);
            }

            $this->syncTranslations($product, $payload['translations'] ?? []);

            if (array_key_exists('images', $payload)) {
                $this->syncImages($product, $payload['images'] ?? []);
            }

            if (array_key_exists('faqs', $payload)) {
                $this->syncFaqs($product, $payload['faqs'] ?? []);
            }

            if (array_key_exists('attribute_values', $payload)) {
                $this->syncAttributeValues($product, $payload['attribute_values'] ?? []);
            }

            if (array_key_exists('color_related_product_ids', $payload)) {
                $this->syncProductRelations($product, 'color', $payload['color_related_product_ids'] ?? []);
            }

            if (array_key_exists('bought_together_product_ids', $payload)) {
                $this->syncProductRelations($product, 'bought_together', $payload['bought_together_product_ids'] ?? []);
            }

            if (array_key_exists('price', $payload)) {
                $this->syncSimpleProductVariant($product, $payload);
            }

            $product->forceFill([
                'search_text' => collect($payload['translations'] ?? [])->pluck('name')->filter()->join(' '),
            ])->save();

            $this->rebuildSnapshot->handle($product);
            $this->cache->invalidate('product-list');
            $this->cache->invalidate('filters');

            if ($previousStockStatus !== 'in_stock' && ($product->stock_status ?? null) === 'in_stock') {
                SendProductBackInStockNotifications::dispatch($product->id)->afterCommit();
            }

            return $product->load('translations', 'categories', 'images', 'primaryImage');
        });
    }

    private function syncImages(Product $product, array $images): void
    {
        $normalizedImages = collect($images)
            ->map(function (array $image, int $index): array {
                return [
                    'path' => trim((string) ($image['path'] ?? '')),
                    'alt' => filled($image['alt'] ?? null) ? trim((string) $image['alt']) : null,
                    'sort' => max(0, (int) ($image['sort'] ?? (($index + 1) * 10))),
                    'is_primary' => (bool) ($image['is_primary'] ?? false),
                ];
            })
            ->filter(fn (array $image): bool => filled($image['path']))
            ->sortBy('sort')
            ->values();

        if ($normalizedImages->isEmpty()) {
            $product->images()->delete();

            return;
        }

        if (! $normalizedImages->contains(fn (array $image): bool => $image['is_primary'])) {
            $normalizedImages[0]['is_primary'] = true;
        }

        $primaryAssigned = false;
        $normalizedImages = $normalizedImages
            ->map(function (array $image) use (&$primaryAssigned): array {
                if (! $image['is_primary'] || $primaryAssigned) {
                    $image['is_primary'] = false;

                    return $image;
                }

                $primaryAssigned = true;

                return $image;
            })
            ->all();

        $product->images()->delete();
        $product->images()->createMany($normalizedImages);
    }

    private function syncSimpleProductVariant(Product $product, array $payload): void
    {
        if (($payload['type'] ?? $product->type) !== 'simple') {
            return;
        }

        $variant = $product->variants()->first() ?? $product->variants()->make();

        $variant->fill([
            'sku' => $payload['sku'] ?? $product->sku,
            'barcode' => $variant->barcode,
            'price' => $payload['price'] !== null && $payload['price'] !== '' ? $payload['price'] : ($variant->exists ? $variant->price : 0),
            'old_price' => array_key_exists('old_price', $payload) ? ($payload['old_price'] !== '' ? $payload['old_price'] : null) : $variant->old_price,
            'multi_currency_code' => $payload['multi_currency_code'] ?? $variant->multi_currency_code,
            'multi_currency_price' => array_key_exists('multi_currency_price', $payload) ? $payload['multi_currency_price'] : $variant->multi_currency_price,
            'multi_currency_old_price' => array_key_exists('multi_currency_old_price', $payload) ? $payload['multi_currency_old_price'] : $variant->multi_currency_old_price,
            'stock_qty' => $variant->stock_qty ?? 0,
            'status' => $payload['stock_status'] ?? 'in_stock',
            'option_snapshot' => $variant->option_snapshot ?? [],
        ]);

        $variant->save();
    }

    private function syncFaqs(Product $product, array $faqs): void
    {
        $normalizedFaqs = collect($faqs)
            ->map(function (array $faq, int $index): array {
                return [
                    'locale' => trim((string) ($faq['locale'] ?? '')),
                    'question' => trim((string) ($faq['question'] ?? '')),
                    'answer' => trim((string) ($faq['answer'] ?? '')),
                    'sort' => max(0, (int) ($faq['sort'] ?? $index)),
                ];
            })
            ->filter(fn (array $faq): bool => filled($faq['locale']) && filled($faq['question']) && filled($faq['answer']))
            ->sortBy('sort')
            ->values()
            ->all();

        $product->faqs()->delete();

        if ($normalizedFaqs === []) {
            return;
        }

        $product->faqs()->createMany($normalizedFaqs);
    }

    private function syncAttributeValues(Product $product, array $attributeValues): void
    {
        $attributes = ProductAttribute::query()
            ->whereIn('id', collect($attributeValues)->pluck('attribute_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $normalizedValues = collect($attributeValues)
            ->map(function (array $value, int $index) use ($attributes): ?array {
                $attributeId = (int) ($value['attribute_id'] ?? 0);
                /** @var ProductAttribute|null $attribute */
                $attribute = $attributes->get($attributeId);

                if (! $attribute) {
                    return null;
                }

                $row = [
                    'attribute_id' => $attribute->id,
                    'variant_id' => null,
                    'value_string' => null,
                    'value_integer' => null,
                    'value_numeric' => null,
                    'value_boolean' => null,
                    'value_option_id' => null,
                    'value_json' => null,
                    'sort' => $index,
                    'is_priority' => (bool) ($value['is_priority'] ?? false),
                ];

                $hasValue = match ($attribute->value_type) {
                    'select', 'option' => $this->fillOptionAttributeValue($row, $attribute, $value['value_option_id'] ?? null),
                    'integer' => $this->fillIntegerAttributeValue($row, $value['value_integer'] ?? null),
                    'numeric' => $this->fillNumericAttributeValue($row, $value['value_numeric'] ?? null),
                    'boolean' => $this->fillBooleanAttributeValue($row, $value['value_boolean'] ?? false),
                    default => $this->fillStringAttributeValue($row, $value['value_string'] ?? null),
                };

                return $hasValue ? $row : null;
            })
            ->filter()
            ->unique('attribute_id')
            ->values()
            ->all();

        $product->attributeValues()->whereNull('variant_id')->delete();

        if ($normalizedValues === []) {
            return;
        }

        $product->attributeValues()->createMany($normalizedValues);
    }

    private function fillOptionAttributeValue(array &$row, ProductAttribute $attribute, mixed $optionId): bool
    {
        $optionId = (int) $optionId;

        if ($optionId <= 0) {
            return false;
        }

        $exists = AttributeOption::query()
            ->whereKey($optionId)
            ->where('attribute_id', $attribute->id)
            ->exists();

        if (! $exists) {
            return false;
        }

        $row['value_option_id'] = $optionId;

        return true;
    }

    private function fillIntegerAttributeValue(array &$row, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $row['value_integer'] = (int) $value;

        return true;
    }

    private function fillNumericAttributeValue(array &$row, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $row['value_numeric'] = (float) $value;

        return true;
    }

    private function fillBooleanAttributeValue(array &$row, mixed $value): bool
    {
        $row['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return true;
    }

    private function fillStringAttributeValue(array &$row, mixed $value): bool
    {
        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        $row['value_string'] = $value;

        return true;
    }

    private function syncProductRelations(Product $product, string $type, array $productIds): void
    {
        $rows = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== (int) $product->id)
            ->unique()
            ->values()
            ->map(fn (int $id, int $index): array => [
                'product_id' => $product->id,
                'related_product_id' => $id,
                'type' => $type,
                'sort' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        DB::table('product_relations')
            ->where('product_id', $product->id)
            ->where('type', $type)
            ->delete();

        if ($rows !== []) {
            DB::table('product_relations')->insert($rows);
        }
    }

    private function syncTranslations(Product $product, array $translations): void
    {
        $existingTranslations = $product->translations()->get()->keyBy('locale');

        foreach ($translations as $translation) {
            $locale = $translation['locale'] ?? null;

            if (! is_string($locale) || $locale === '') {
                continue;
            }

            $existing = $existingTranslations->get($locale);

            if (! $this->hasMeaningfulTranslationData($translation)) {
                $existing?->delete();

                continue;
            }

            $product->translations()->updateOrCreate(
                ['locale' => $locale],
                Arr::only($translation, ['name', 'slug', 'description', 'full_description', 'meta_title', 'meta_description', 'robots']),
            );
        }
    }

    private function hasMeaningfulTranslationData(array $translation): bool
    {
        return filled($translation['name'] ?? null)
            || filled($translation['slug'] ?? null)
            || filled($translation['description'] ?? null)
            || filled($translation['full_description'] ?? null)
            || filled($translation['meta_title'] ?? null)
            || filled($translation['meta_description'] ?? null)
            || ($translation['robots'] ?? 'index, follow') !== 'index, follow';
    }

    private function guardDefaultLocaleTranslation(array $translations): void
    {
        $defaultLocale = Locales::default();

        $hasDefaultTranslation = collect($translations)
            ->contains(fn (array $translation): bool => ($translation['locale'] ?? null) === $defaultLocale && filled($translation['name'] ?? null) && filled($translation['slug'] ?? null));

        if ($hasDefaultTranslation) {
            return;
        }

        throw ValidationException::withMessages([
            'translations' => "A {$defaultLocale} translation is required.",
        ]);
    }
}
