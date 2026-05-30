<?php

namespace Commero\Application\Catalog\Services;

use Commero\Models\Product;
use Commero\Models\ProductAttributeValue;

class RebuildProductAttributeSnapshot
{
    public function handle(Product $product): Product
    {
        $snapshot = $product->attributeValues()
            ->with(['attribute:id,code', 'option:id,value'])
            ->get()
            ->map(function (ProductAttributeValue $value): array {
                return [
                    'attribute_id' => $value->attribute_id,
                    'code' => $value->attribute?->code,
                    'variant_id' => $value->variant_id,
                    'option_id' => $value->value_option_id,
                    'option_value' => $value->option?->value,
                    'value' => $value->value_string
                        ?? $value->value_integer
                        ?? $value->value_numeric
                        ?? $value->value_boolean
                        ?? $value->value_json,
                ];
            })
            ->values()
            ->all();

        $product->forceFill(['attribute_snapshot' => $snapshot])->save();

        return $product->refresh();
    }
}
