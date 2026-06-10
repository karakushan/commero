<?php

namespace Commero\Interfaces\Filament\Resources\ProductResource\Pages;

use Commero\Application\Catalog\Services\UpsertProductService;
use Commero\Interfaces\Filament\Resources\ProductResource;
use Commero\Interfaces\Filament\Resources\ProductResource\Pages\Concerns\InteractsWithProductTranslations;
use Commero\Interfaces\Filament\Resources\ProductReviewResource;
use Commero\Models\AttributeOption;
use Commero\Models\Currency;
use Commero\Models\Product;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    use InteractsWithProductTranslations;

    protected static string $resource = ProductResource::class;

    public function mount(int|string $record): void
    {
        $this->initializeActiveLocale();

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewProduct')
                ->label(__('commero::admin.product.actions.view_on_site'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => $this->getFrontendProductUrl())
                ->openUrlInNewTab(),
            Action::make('reviews')
                ->label(__('commero::admin.product_review.actions.view_reviews'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn (): string => ProductReviewResource::getUrl('index', [
                    'filters' => [
                        'product_id' => ['value' => (string) $this->getRecord()->id],
                    ],
                ])),
        ];
    }

    private function getFrontendProductUrl(): string
    {
        /** @var Product $product */
        $product = $this->getRecord()->loadMissing('translations');
        $activeLocale = $this->resolveActiveLocale();
        $activeTranslation = $product->translation($activeLocale);

        if (filled($activeTranslation?->slug)) {
            return $this->buildFrontendProductUrl($activeLocale, $activeTranslation->slug);
        }

        $defaultTranslation = $product->translation(Locales::default());

        if (filled($defaultTranslation?->slug)) {
            return $this->buildFrontendProductUrl(
                Locales::isDefault($activeLocale) ? Locales::default() : $activeLocale,
                $defaultTranslation->slug,
            );
        }

        $translationWithSlug = $product->translations
            ->first(fn ($translation): bool => filled($translation->slug));

        if (filled($translationWithSlug?->slug) && filled($translationWithSlug?->locale)) {
            return $this->buildFrontendProductUrl(
                Locales::isDefault($activeLocale) ? $translationWithSlug->locale : $activeLocale,
                $translationWithSlug->slug,
            );
        }

        return route('catalog.index');
    }

    private function buildFrontendProductUrl(string $locale, string $slug): string
    {
        return Locales::isDefault($locale)
            ? route('product.show', ['slug' => $slug])
            : route('localized.product.show', ['locale' => $locale, 'slug' => $slug]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord()->load('translations', 'categories', 'images', 'attributeValues', 'variants.attributeValues', 'faqs', 'colorRelatedProducts', 'boughtTogetherProducts');

        $data = [
            ...$data,
            ...$this->getActiveLocaleContextState(),
        ];
        $data['translations'] = $this->getTranslationsFormState($record);
        $data['gallery_uploads'] = [];
        $data['category_ids'] = $record->categories->pluck('id')->all();
        $data['images'] = $record->images
            ->map(fn ($image) => $image->only(['path', 'alt', 'sort', 'is_primary']))
            ->values()
            ->all();
        $data['faqs'] = $record->faqs
            ->map(fn ($faq) => $faq->only(['locale', 'question', 'answer', 'sort']))
            ->values()
            ->all();
        $data['attribute_values'] = $record->attributeValues
            ->whereNull('variant_id')
            ->sortBy('sort')
            ->map(fn ($value) => $value->only([
                'attribute_id',
                'value_string',
                'value_integer',
                'value_numeric',
                'value_boolean',
                'value_option_id',
                'sort',
                'is_priority',
            ]))
            ->values()
            ->all();
        $data['color_related_product_ids'] = $record->colorRelatedProducts->pluck('id')->map(fn ($id) => (string) $id)->all();
        $data['bought_together_product_ids'] = $record->boughtTogetherProducts->pluck('id')->map(fn ($id) => (string) $id)->all();

        if ($record->type === 'simple') {
            $data['price'] = $record->variants->first()?->price;
            $data['old_price'] = $record->variants->first()?->old_price;
            $data['multi_currency_code'] = $record->variants->first()?->multi_currency_code;
            $data['multi_currency_price'] = $record->variants->first()?->multi_currency_price;
            $data['multi_currency_old_price'] = $record->variants->first()?->multi_currency_old_price;
        }

        if ($record->type === 'variant') {
            $data['variants'] = $record->variants
                ->sortBy('sort')
                ->values()
                ->map(fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'old_price' => $variant->old_price,
                    'status' => $variant->status,
                    'sort' => $variant->sort,
                    'attribute_option_ids' => $variant->attributeValues
                        ->whereNotNull('value_option_id')
                        ->pluck('value_option_id')
                        ->values()
                        ->map(fn ($id) => (string) $id)
                        ->all(),
                ])
                ->all();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Product $record */
        $record = $this->getRecord();
        $data = $this->prepareProductDataForActiveLocale($data, $record);
        $data = $this->convertMultiCurrencyPrices($data);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Product $product */
        $product = app(UpsertProductService::class)->handle($data, $record);

        if (($data['type'] ?? $product->type) === 'simple') {
            $this->syncSimpleProductVariant($product, $data);
        }

        if (($data['type'] ?? $product->type) === 'variant') {
            $this->syncVariants($product, $data['variants'] ?? []);
        }

        return $product->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRedirectUrlParameters(): array
    {
        return [
            'lang' => $this->resolveActiveLocale(),
        ];
    }

    private function syncSimpleProductVariant(Product $product, array $data): void
    {
        $variant = $product->variants()->first() ?? $product->variants()->make();

        $variant->fill([
            'sku' => $data['sku'] ?? $product->sku,
            'barcode' => $variant->barcode,
            'price' => $data['price'],
            'old_price' => $data['old_price'] ?? null,
            'multi_currency_code' => $data['multi_currency_code'] ?? null,
            'multi_currency_price' => $data['multi_currency_price'] ?? null,
            'multi_currency_old_price' => $data['multi_currency_old_price'] ?? null,
            'stock_qty' => $variant->stock_qty ?? 0,
            'status' => $data['stock_status'] ?? 'in_stock',
            'option_snapshot' => $variant->option_snapshot ?? [],
        ]);

        $variant->save();
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $existingIds = collect($variants)->pluck('id')->filter()->values()->all();

        $product->variants()->whereNotIn('id', $existingIds)->delete();

        $variants = array_values($variants);

        foreach ($variants as $index => $variantData) {
            $variant = isset($variantData['id'])
                ? $product->variants()->find($variantData['id']) ?? $product->variants()->make()
                : $product->variants()->make();

            $variant->fill([
                'name' => $variantData['name'] ?? '',
                'sku' => $variantData['sku'] ?? $product->sku.'-'.($index + 1),
                'barcode' => $variant->barcode,
                'price' => $variantData['price'] ?? 0,
                'old_price' => $variantData['old_price'] ?? null,
                'multi_currency_code' => $data['multi_currency_code'] ?? null,
                'multi_currency_price' => $variantData['multi_currency_price'] ?? null,
                'multi_currency_old_price' => $variantData['multi_currency_old_price'] ?? null,
                'stock_qty' => $variant->stock_qty ?? 0,
                'status' => $variantData['status'] ?? 'in_stock',
                'option_snapshot' => $variantData['attribute_option_ids'] ?? [],
                'sort' => $index,
            ]);

            $variant->save();

            $this->syncVariantAttributeValues($product, $variant, $variantData['attribute_option_ids'] ?? []);
        }
    }

    private function syncVariantAttributeValues(Product $product, $variant, array $attributeOptionIds): void
    {
        $variant->attributeValues()->delete();

        foreach ($attributeOptionIds as $index => $optionId) {
            $variant->attributeValues()->create([
                'product_id' => $product->id,
                'attribute_id' => AttributeOption::find($optionId)?->attribute_id,
                'value_option_id' => $optionId,
                'sort' => $index,
                'is_priority' => false,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function convertMultiCurrencyPrices(array $data): array
    {
        $currencyCode = $data['multi_currency_code'] ?? null;

        if (! filled($currencyCode)) {
            return $data;
        }

        if ($currencyCode === Currency::getBaseCode()) {
            return $data;
        }

        $currency = Currency::where('code', $currencyCode)->first();

        if (! $currency) {
            return $data;
        }

        if (array_key_exists('multi_currency_price', $data) && filled($data['multi_currency_price'])) {
            $data['price'] = $currency->convertToBase((float) $data['multi_currency_price']);
        }

        if (array_key_exists('multi_currency_old_price', $data) && filled($data['multi_currency_old_price'])) {
            $data['old_price'] = $currency->convertToBase((float) $data['multi_currency_old_price']);
        }

        if (array_key_exists('variants', $data)) {
            foreach ($data['variants'] as &$variant) {
                if (filled($variant['multi_currency_price'] ?? null)) {
                    $variant['price'] = $currency->convertToBase((float) $variant['multi_currency_price']);
                }

                if (filled($variant['multi_currency_old_price'] ?? null)) {
                    $variant['old_price'] = $currency->convertToBase((float) $variant['multi_currency_old_price']);
                }
            }
            unset($variant);
        }

        return $data;
    }
}
