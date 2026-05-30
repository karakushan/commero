<?php

namespace Commero\Interfaces\Filament\Resources\ProductResource\Pages;

use Commero\Application\Catalog\Services\UpsertProductService;
use Commero\Interfaces\Filament\Resources\ProductResource;
use Commero\Interfaces\Filament\Resources\ProductResource\Pages\Concerns\InteractsWithProductTranslations;
use Commero\Models\AttributeOption;
use Commero\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    use InteractsWithProductTranslations;

    protected static string $resource = ProductResource::class;

    public function mount(): void
    {
        $this->initializeActiveLocale();

        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            ...$this->getActiveLocaleContextState(),
            'gallery_uploads' => [],
            'translations' => $this->getTranslationsFormState(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareProductData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = app(UpsertProductService::class)->handle($data, new Product);

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
        if (($data['type'] ?? $product->type) !== 'simple') {
            return;
        }

        $variant = $product->variants()->first() ?? $product->variants()->make();

        $variant->fill([
            'sku' => $data['sku'] ?? $product->sku,
            'barcode' => $variant->barcode,
            'price' => $data['price'],
            'old_price' => $data['old_price'] ?? null,
            'stock_qty' => $variant->stock_qty ?? 0,
            'status' => $data['stock_status'] ?? 'in_stock',
            'option_snapshot' => $variant->option_snapshot ?? [],
        ]);

        $variant->save();
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $variants = array_values($variants);

        foreach ($variants as $index => $variantData) {
            $variant = $product->variants()->make();

            $variant->fill([
                'name' => $variantData['name'] ?? '',
                'sku' => $variantData['sku'] ?? $product->sku.'-'.($index + 1),
                'barcode' => $variant->barcode,
                'price' => $variantData['price'] ?? 0,
                'old_price' => $variantData['old_price'] ?? null,
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
        foreach ($attributeOptionIds as $index => $optionId) {
            $attributeId = AttributeOption::query()->find($optionId)?->attribute_id;

            if (! $attributeId) {
                continue;
            }

            $variant->attributeValues()->create([
                'product_id' => $product->id,
                'attribute_id' => $attributeId,
                'value_option_id' => $optionId,
                'sort' => $index,
                'is_priority' => false,
            ]);
        }
    }
}
